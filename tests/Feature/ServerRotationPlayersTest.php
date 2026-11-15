<?php
namespace Funseco\Admin\Tests\Feature;

use Funseco\Admin\Tests\AdminTestCase;
use Illuminate\Support\Facades\Event;
use Funseco\Core\Services\Cache\CurrentSpectators;
use Funseco\Core\Services\Cache\ServerInfo;
use Funseco\Core\SharedKernel\Events\JukeboxHistoryClearRequested;
use Funseco\Core\SharedKernel\Events\TrackAdded;
use Funseco\Core\SharedKernel\LastTrackList;

/** /admin server settings, track rotation and player commands from the long tail. */
class ServerRotationPlayersTest extends AdminTestCase
{
    private const BY = '{#server}>> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s{#admin} ';
    private string $tracks;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tracks = sys_get_temp_dir() . '/funseco-test-' . uniqid();
        mkdir($this->tracks . '/Challenges/Downloaded', 0777, true);
        ServerInfo::setTracksDirectory($this->tracks);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->tracks));
        parent::tearDown();
    }

    public function test_setrefpwd_with_and_without_value()
    {
        $this->chat('/admin setrefpwd secret');
        $this->assertCalled('SetRefereePassword', ['secret']);
        $this->assertToldTo('boss', '{#server}> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s{#admin} sets referee password to {#highlite}secret');

        $this->chat('/admin setrefpwd');
        $this->assertToldTo('boss', '{#server}> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s{#admin} disables referee password');
    }

    public function test_setrefmode()
    {
        $this->chat('/admin setrefmode 1');
        $this->assertCalled('SetRefereeMode', [1]);
        $this->assertBroadcast(self::BY . 'sets referee mode to {#highlite}1{#admin} !');

        $this->chat('/admin setrefmode x');
        $this->assertToldTo('boss', '{#server}> {#error}Invalid referee mode {#highlite}$i X$z$s {#error}!');

        $this->query->responses['GetRefereeMode'] = [0];
        $this->chat('/admin setrefmode');
        $this->assertToldTo('boss', '{#server}> {#admin}Referee mode is set to {#highlite}Top-3');
        $this->assertCount(1, $this->broadcasts(), 'no-argument answer is private');
    }

    public function test_setgamemode_pending_and_same_mode()
    {
        ServerInfo::setGameMode(1);

        $this->chat('/admin setgamemode ta');
        $this->assertToldTo('boss', '{#server}> Same game mode {#highlite}TA');
        $this->assertNotContains('SetGameMode', array_column($this->query->calls, 0));

        $this->chat('/admin setgamemode Rounds');
        $this->assertCalled('SetGameMode', [0]);
        $this->assertBroadcast(self::BY . 'sets next game mode to {#highlite}ROUNDS{#admin} !');

        $this->chat('/admin setgamemode nope');
        $this->assertToldTo('boss', '{#server}> {#error}Invalid game mode {#highlite}$i NOPE$z$s {#error}!');

        $this->chat('/admin setgamemode');
        $this->assertToldTo('boss', '{#server}> {#error}Unknown admin command or missing parameter(s): {#highlite}$i setgamemode ');
    }

    public function test_acdl_disablerespawn_forceshowopp()
    {
        $master = $this->online('master', 'Master');
        $this->chat('/admin acdl on', $master);
        $this->assertCalled('AllowChallengeDownload', [true]);
        $this->assertToldTo('master', '{#server}> {#admin}AllowChallengeDownload set to Enabled');

        $this->query->responses['IsChallengeDownloadAllowed'] = [false];
        $this->chat('/admin acdl', $master);
        $this->assertToldTo('master', '{#server}> {#admin}AllowChallengeDownload is currently Disabled');

        $this->chat('/admin disablerespawn off');
        $this->assertCalled('SetDisableRespawn', [false]);
        $this->assertBroadcast('{#server}>> {#admin}DisableRespawn set to Disabled on the next track');
        $this->query->responses['GetDisableRespawn'] = [['CurrentValue' => true, 'NextValue' => false]];
        $this->chat('/admin disablerespawn');
        $this->assertToldTo('boss', '{#server}> {#admin}DisableRespawn is currently Enabled');

        $this->chat('/admin forceshowopp 4');
        $this->assertCalled('SetForceShowAllOpponents', [4]);
        $this->assertBroadcast('{#server}>> {#admin}ForceShowAllOpponents set to {#highlite}4{#admin} on the next track');
        $this->chat('/admin forceshowopp all');
        $this->assertBroadcast('{#server}>> {#admin}ForceShowAllOpponents set to {#highlite}Enabled{#admin} on the next track');
        $this->query->responses['GetForceShowAllOpponents'] = [['CurrentValue' => 1]];
        $this->chat('/admin forceshowopp 1');
        $this->assertToldTo('boss', '{#server}> {#admin}ForceShowAllOpponents is set to: {#highlite}All');
    }

    public function test_scorepanel_and_roundsfinish_flags()
    {
        $this->chat('/admin scorepanel off');
        $this->assertBroadcast('{#server}>> {#admin}Automatic ScorePanel set to Disabled');
        $ui = collect($this->query->calls)->firstWhere(0, 'SendDisplayManialinkPage');
        $this->assertStringContainsString('<scoretable visible="false"/>', $ui[1][0]);
        $this->chat('/admin scorepanel');
        $this->assertToldTo('boss', '{#server}> {#admin}Automatic ScorePanel is currently Disabled');

        $this->chat('/admin roundsfinish off');
        $this->assertBroadcast('{#server}>> {#admin}Rounds Finishpanel set to Disabled');
        $this->chat('/admin roundsfinish');
        $this->assertToldTo('boss', '{#server}> {#admin}Rounds Finishpanel is currently Disabled');
    }

    public function test_nextenv_is_refused_on_a_single_environment_server()
    {
        $this->query->responses['GetChallengeList'] = [[
            ['FileName' => 'A.Gbx', 'Name' => 'A', 'UId' => 'a', 'Environnement' => 'Stadium'],
            ['FileName' => 'B.Gbx', 'Name' => 'B', 'UId' => 'b', 'Environnement' => 'Stadium'],
        ]];
        $this->chat('/admin nextenv');
        $this->assertToldTo('boss', '{#server}> {#error}Command only available on TMU Forever!');
    }

    public function test_nextenv_picks_next_track_of_the_same_environment()
    {
        $this->map(['uid' => 'a']);
        $this->query->responses['GetChallengeList'] = [[
            ['FileName' => 'A.Gbx', 'Name' => 'A', 'UId' => 'a', 'Environnement' => 'Bay'],
            ['FileName' => 'B.Gbx', 'Name' => 'B', 'UId' => 'b', 'Environnement' => 'Coast'],
            ['FileName' => 'C.Gbx', 'Name' => 'C', 'UId' => 'c', 'Environnement' => 'Bay'],
        ]];
        $this->query->responses['ChooseNextChallenge'] = [true];
        $this->chat('/admin nextenv');
        $this->assertCalled('ChooseNextChallenge', ['C.Gbx']);
        $this->assertCalled('NextChallenge', []);
        $this->assertBroadcast(self::BY . 'skips to next {#highlite}Bay{#admin} challenge!');
    }

    public function test_clearhist_asks_the_jukebox_via_event()
    {
        Event::listen(JukeboxHistoryClearRequested::class, function (JukeboxHistoryClearRequested $e) {
            $e->handled = true;
            $e->size = 5;
            $e->removed = $e->amount === null ? 5 : min(abs($e->amount), 5);
        });

        $this->chat('/admin clearhist 2');
        $this->assertBroadcast(self::BY . 'clears newest {#highlite}2{#admin} tracks from history!');
        $this->chat('/admin clearhist -1');
        $this->assertBroadcast(self::BY . 'clears oldest {#highlite}1{#admin} track from history!');
        $this->chat('/admin clearhist all');
        $this->assertBroadcast(self::BY . 'clears entire track history!');
        $this->chat('/admin clearhist');
        $this->assertToldTo('boss', '{#server}> {#admin}The track history contains {#highlite}5{#admin} tracks');
    }

    public function test_clearhist_really_trims_the_jukebox_history()
    {
        $queue = app(\Funseco\Jukebox\Queue::class);
        foreach (['u1', 'u2', 'u3'] as $uid) {
            $queue->started(new \Funseco\Core\Models\Map(['uid' => $uid, 'name' => $uid]));
        }
        $this->chat('/admin clearhist 1');
        $this->assertSame(2, $queue->historySize());
        $this->chat('/admin clearhist -1');
        $this->assertSame(1, $queue->historySize());
        $this->assertSame(['u2'], $queue->history(5));
    }

    public function test_djb_and_cjb_aliases()
    {
        app(\Funseco\Jukebox\Queue::class)->push(['uid' => 'x', 'file' => 'X.Gbx', 'name' => 'X'], $this->admin, 'Jukebox');
        $this->chat('/admin djb 1');
        $this->assertSame([], app(\Funseco\Jukebox\Queue::class)->entries());

        app(\Funseco\Jukebox\Queue::class)->push(['uid' => 'x', 'file' => 'X.Gbx', 'name' => 'X'], $this->admin, 'Jukebox');
        $this->chat('/admin cjb');
        $this->assertSame([], app(\Funseco\Jukebox\Queue::class)->entries());
    }

    public function test_addthis_says_already_permanent_for_a_normal_track()
    {
        $this->map(['name' => '$f00Speed']);
        $this->chat('/admin addthis');
        $this->assertToldTo('boss', '{#server}> {#error}Current track {#highlite}$i Speed {#error}already permanently in track list!');
    }

    public function test_addthis_moves_a_temporary_track()
    {
        mkdir($this->tracks . '/Challenges/TMXtmp', 0777, true);
        file_put_contents($this->tracks . '/Challenges/TMXtmp/t.Challenge.Gbx', 'x');
        $map = \Funseco\Core\Models\Map::factory()->create(['uid' => 'tmp1', 'name' => 'Temp', 'file_name' => 'Challenges/TMXtmp/t.Challenge.Gbx']);
        \Funseco\Core\Services\Cache\CurrentMap::set($map);
        $this->query->responses['RemoveChallenge'] = [true];
        $this->query->responses['AddChallenge'] = [true];

        $this->chat('/admin addthis');

        $this->assertFileExists($this->tracks . '/Challenges/TMX/t.Challenge.Gbx');
        $this->assertCalled('AddChallenge', ['Challenges/TMX/t.Challenge.Gbx']);
        $this->assertBroadcast('{#server}>> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s {#admin}permanently adds current track: {#highlite}Temp');
        $this->assertSame('Challenges/TMX/t.Challenge.Gbx', $map->fresh()->file_name);
    }

    public function test_addlocal_validation_and_success()
    {
        $this->chat('/admin addlocal');
        $this->assertToldTo('boss', '{#server}> {#error}You must include a local track filename!');

        $this->chat('/admin addlocal nothere');
        $this->assertToldTo('boss', '{#server}> {#highlite}Challenges/Downloaded/nothere.Challenge.gbx{#error} not found!');

        file_put_contents($this->tracks . '/Challenges/Downloaded/Big.Challenge.gbx', str_repeat('x', 300 * 1024));
        $this->chat('/admin addlocal big');
        $this->assertToldTo('boss', '{#server}> {#error}Track is too large ({#highlite}300$i{#error} > 256 KB) - not added!');

        file_put_contents($this->tracks . '/Challenges/Downloaded/Fine.Challenge.gbx', 'x');
        $this->query->faults['CheckChallengeForCurrentServerParams'] = 'wrong environment';
        $this->chat('/admin addlocal FINE');
        $this->assertToldTo('boss', '{#server}> {#emotic}Track {#highlite}Fine.Challenge.gbx{#emotic} ignored: {#error}wrong environment');

        unset($this->query->faults['CheckChallengeForCurrentServerParams']);
        $this->query->responses['CheckChallengeForCurrentServerParams'] = [true];
        $this->query->responses['AddChallenge'] = [true];
        $this->query->responses['GetChallengeInfo'] = [['UId' => 'loc1', 'Name' => '$0f0Local', 'FileName' => 'Challenges/Downloaded/Fine.Challenge.gbx',
            'Author' => 'me', 'Environnement' => 'Stadium', 'Mood' => 'Day', 'BronzeTime' => 3, 'SilverTime' => 2, 'GoldTime' => 1, 'AuthorTime' => 1,
            'CopperPrice' => 1, 'LapRace' => false, 'NbLaps' => 1, 'NbCheckpoints' => 2]];
        Event::fake([TrackAdded::class]);
        $this->chat('/admin addlocal fine');

        $this->assertCalled('AddChallenge', ['Challenges/Downloaded/Fine.Challenge.gbx']);
        $this->assertBroadcast('{#server}>> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s {#admin}adds & jukeboxes track: {#highlite}Local');
        Event::assertDispatched(TrackAdded::class, fn ($e) => $e->map->uid === 'loc1' && $e->source === 'local');
    }

    public function test_listdupes_and_erase_use_the_dupe_numbers()
    {
        $this->query->responses['GetChallengeList'] = [[
            ['FileName' => 'A.Gbx', 'Name' => 'Alpha', 'UId' => 'a'],
            ['FileName' => 'A2.Gbx', 'Name' => '$f00Alpha', 'UId' => 'a'],
            ['FileName' => 'B.Gbx', 'Name' => 'Beta', 'UId' => 'b'],
        ]];
        $this->chat('/admin listdupes');

        $window = collect($this->query->calls)->firstWhere(0, 'SendDisplayManialinkPageToLogin');
        $this->assertStringContainsString('Duplicate Tracks On This Server:', $window[1][1]);
        $this->assertStringContainsString('001.', $window[1][1]);
        $this->assertSame('A2.Gbx', app(LastTrackList::class)->at($this->admin, 1)['file']);

        file_put_contents($this->tracks . '/A2.Gbx', 'x');
        $this->query->responses['RemoveChallenge'] = [true];
        $this->chat('/admin erase 1');
        $this->assertCalled('RemoveChallenge', ['A2.Gbx']);
        $this->assertFileDoesNotExist($this->tracks . '/A2.Gbx');
        $this->assertBroadcast('{#server}>> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s {#admin}erases track: {#highlite}Alpha');
    }

    public function test_listdupes_without_dupes()
    {
        $this->query->responses['GetChallengeList'] = [[['FileName' => 'A.Gbx', 'Name' => 'A', 'UId' => 'a']]];
        $this->chat('/admin listdupes');
        $this->assertToldTo('boss', '{#server}> {#error}No duplicate track(s) found!');
    }

    public function test_erase_when_file_is_missing_is_a_plain_remove_and_erasethis()
    {
        $map = $this->map(['file_name' => 'Gone.Gbx', 'name' => 'Gone']);
        $this->query->responses['RemoveChallenge'] = [true];
        $this->chat('/admin erasethis');
        $this->assertBroadcast('{#server}>> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s {#admin}removes current track: {#highlite}Gone');

        file_put_contents($this->tracks . '/Gone.Gbx', 'x');
        $this->chat('/admin erasethis');
        $this->assertFileDoesNotExist($this->tracks . '/Gone.Gbx');
        $this->assertBroadcast('{#server}>> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s {#admin}erases current track: {#highlite}Gone');
    }

    public function test_kickghost_announces_then_kicks()
    {
        $this->chat('/admin kickghost ghosty');
        $this->assertSame('Kick', $this->serverCalls()[0][0]);
        $this->assertCalled('Kick', ['ghosty']);
        $this->assertBroadcast(self::BY . 'kicked ghost {#highlite}ghosty$z$s{#admin} !');
    }

    public function test_forceteam()
    {
        $target = $this->online('red1', 'Redy');

        $this->chat('/admin forceteam red1 blue');
        $this->assertToldTo('boss', '{#server}> {#error}Command only available in {#highlite}$i Team {#error}mode!');

        ServerInfo::setGameMode(2);
        $this->query->responses['GetPlayerInfo'] = [['TeamId' => 1]];
        $this->chat('/admin forceteam red1');
        $this->assertToldTo('boss', '{#server}> {#admin}Player {#highlite}Redy{#admin} is in $f00Red{#admin} team');
        $this->chat('/admin forceteam red1 r');
        $this->assertToldTo('boss', '{#server}> {#admin}Player {#highlite}Redy{#admin} is already in $f00Red{#admin} team');
        $this->chat('/admin forceteam red1 green');
        $this->assertToldTo('boss', '{#server}> {#highlite}green$z$s{#error} is not a valid team!');
        $this->chat('/admin forceteam red1 bl');
        $this->assertCalled('ForcePlayerTeam', ['red1', 0]);
        $this->assertBroadcast(self::BY . 'forces {#highlite}Redy$z$s{#admin} into $00fBlue{#admin} team!');
    }

    public function test_specfree()
    {
        $this->online('racer', 'Racer');
        $this->chat('/admin specfree racer');
        $this->assertToldTo('boss', '{#server}> {#highlite}Racer {#error}is not a spectator!');

        CurrentSpectators::add(\Funseco\Core\Services\Cache\CurrentPlayers::get('racer'));
        $this->chat('/admin specfree racer');
        $this->assertCalled('ForceSpectatorTarget', ['racer', '', 2]);
        $this->assertBroadcast(self::BY . 'forces spectator free mode on {#highlite}Racer$z$s{#admin} !');
    }
}
