<?php
namespace Funseco\Admin\Tests\Feature;

use Funseco\Admin\Tests\AdminTestCase;
use Funseco\Core\Services\Cache\ServerInfo;
use Funseco\Admin\Commands\ReplayMapCommand;
use Funseco\Core\Services\Chat\Facades\Chat;

class TrackControlTest extends AdminTestCase
{
    private const BY = '{#server}>> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s{#admin} ';

    private function tracks(): array
    {
        return [[
            ['FileName' => 'A.Gbx', 'Name' => '$f00Alpha', 'UId' => 'a'],
            ['FileName' => 'B.Gbx', 'Name' => '$0f0Beta', 'UId' => 'b'],
            ['FileName' => 'C.Gbx', 'Name' => 'Gamma', 'UId' => 'c'],
        ]];
    }

    public function test_help_lists_command_names_for_role()
    {
        $this->chat('/admin help');

        $msg = $this->messagesTo('boss')[0];
        $this->assertStringContainsString('Currently supported Admin commands:', $msg);
        $this->assertStringContainsString('nextmap/next/skipmap/skip', $msg);
        $this->assertStringNotContainsString('setservername', $msg, 'masteradmin-only command hidden from admin');
    }

    public function test_operator_help_and_helpall_window()
    {
        $op = $this->online('op', 'Op', 'operator');
        $this->chat('/admin helpall', $op);

        $window = collect($this->query->calls)->firstWhere(0, 'SendDisplayManialinkPageToLogin');
        $this->assertNotNull($window);
        $this->assertStringContainsString('Currently supported Operator commands:', $window[1][1]);
        $this->assertStringContainsString('Shows all available /admin commands', $window[1][1]);

        // 15 per page: check the operator's full list, not only page 1.
        $names = array_column(Chat::commands($op, 'admin'), 'name');
        $this->assertContains('admin warn', $names);
        $this->assertNotContains('admin kick', $names);
    }

    public function test_players_cannot_use_admin_help()
    {
        $p = $this->online('joe');
        $this->chat('/admin help', $p);
        $this->assertDenied($p);
    }

    public function test_next_and_aliases()
    {
        foreach (['next', 'skip', 'nextmap', 'skipmap'] as $alias) {
            $this->query->calls = [];
            $this->chat("/admin $alias");
            $this->assertSame([['NextChallenge', []]], $this->serverCalls());
            $this->assertBroadcast(self::BY . 'skips challenge!');
        }
    }

    public function test_operator_cannot_skip()
    {
        $op = $this->online('op', 'Op', 'operator');
        $this->chat('/admin skip', $op);
        $this->assertDenied($op);
    }

    public function test_restart()
    {
        $this->chat('/admin res');
        $this->assertSame([['ChallengeRestart', []]], $this->serverCalls());
        $this->assertBroadcast(self::BY . 'restarts challenge!');
    }

    public function test_endround()
    {
        $this->chat('/admin er');
        $this->assertSame([['ForceEndRound', []]], $this->serverCalls());
        $this->assertBroadcast(self::BY . 'forces round end!');
    }

    public function test_replay_queues_current_map_once_per_map()
    {
        // The jukebox queues it at the front; it picks it at the end of the race.
        $map = $this->map();
        $this->chat('/admin replay');
        $this->assertSame([], $this->serverCalls());
        $this->assertSame($map->uid, app(\Funseco\Jukebox\Queue::class)->entries()[0]['uid']);
        $this->assertBroadcast(self::BY . 'queues challenge for replay!');

        $this->chat('/admin replay');
        $this->assertCount(1, app(\Funseco\Jukebox\Queue::class)->entries());
        $this->assertToldTo('boss', '{#server}> {#error}Track is already getting replayed!');
    }

    public function test_replay_without_jukebox_chooses_current_map_as_next()
    {
        \Illuminate\Support\Facades\Event::forget(\Funseco\Core\SharedKernel\Events\ReplayRequested::class);
        $this->map();
        $this->chat('/admin replay');
        $this->assertSame([['ChooseNextChallenge', ['Challenges/Speed.Challenge.Gbx']]], $this->serverCalls());

        $this->query->calls = [];
        $this->chat('/admin replay');
        $this->assertSame([], $this->serverCalls());
        $this->assertToldTo('boss', '{#server}> {#error}Track is already getting replayed!');

        app(ReplayMapCommand::class)->mapStarted();
        $this->query->calls = [];
        $this->chat('/admin replaymap');
        $this->assertCalled('ChooseNextChallenge', ['Challenges/Speed.Challenge.Gbx']);
    }

    public function test_previous_chooses_track_before_current()
    {
        $this->query->responses['GetCurrentChallengeIndex'] = [2];
        $this->query->responses['GetChallengeList'] = fn ($size, $offset) => [[$this->tracks()[0][$offset]]];

        $this->chat('/admin prev');

        $this->assertSame([
            ['GetCurrentChallengeIndex', []],
            ['GetChallengeList', [1, 1]],
            ['ChooseNextChallenge', ['B.Gbx']],
            ['NextChallenge', []],
        ], $this->serverCalls());
        $this->assertBroadcast(self::BY . 'revisits previous challenge!');
    }

    public function test_previous_from_first_track_wraps_to_last()
    {
        $this->query->responses['GetCurrentChallengeIndex'] = [0];
        $this->query->responses['GetChallengeList'] = $this->tracks();

        $this->chat('/admin previous');

        $this->assertCalled('GetChallengeList', [5000, 0]);
        $this->assertCalled('ChooseNextChallenge', ['C.Gbx']);
    }

    /** The admin was last shown the three tracks (jukebox plugin's /list). */
    private function shown(): void
    {
        app(\Funseco\Core\SharedKernel\LastTrackList::class)->show($this->admin, [
            ['uid' => 'a', 'file' => 'A.Gbx', 'name' => '$f00Alpha'],
            ['uid' => 'b', 'file' => 'B.Gbx', 'name' => '$0f0Beta'],
            ['uid' => 'c', 'file' => 'C.Gbx', 'name' => 'Gamma'],
        ]);
    }

    public function test_remove_by_track_number()
    {
        $this->shown();
        $this->query->responses['RemoveChallenge'] = [true];

        $this->chat('/admin remove 2');

        $this->assertCalled('RemoveChallenge', ['B.Gbx']);
        $this->assertBroadcast('{#server}>> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s {#admin}removes track: {#highlite}Beta');
    }

    public function test_remove_unknown_number_and_missing_arg()
    {
        $this->shown();

        $this->chat('/admin remove 9');
        $this->assertToldTo('boss', '{#server}> {#orange}Track_ID not found - Type {#highlite}/list{#orange} to see all tracks.');

        $this->chat('/admin remove');
        $this->assertStringContainsString('You have to include a Track_ID', end($this->query->calls)[1][0]);
        $this->assertNotContains('RemoveChallenge', array_column($this->query->calls, 0));
    }

    public function test_remove_without_a_shown_list_asks_for_list()
    {
        $this->chat('/admin remove 1');

        $this->assertToldTo('boss', '{#server}> {#orange}Use {#highlite}/list{#orange} first - Type {#highlite}/list help{#orange} for more info.');
        $this->assertNotContains('RemoveChallenge', array_column($this->query->calls, 0));
    }

    public function test_remove_failure_is_reported()
    {
        $this->shown();
        $this->query->responses['RemoveChallenge'] = [];

        $this->chat('/admin remove 1');

        $this->assertToldTo('boss', '{#server}> {#error}Error removing track {#highlite}$i A.Gbx {#error}!');
    }

    public function test_removethis()
    {
        $this->map();
        $this->query->responses['RemoveChallenge'] = [true];

        $this->chat('/admin removethis');

        $this->assertCalled('RemoveChallenge', ['Challenges/Speed.Challenge.Gbx']);
        $this->assertBroadcast('{#server}>> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s {#admin}removes current track: {#highlite}Speed');
    }

    public function test_shuffle_readds_all_but_current()
    {
        $this->map(['file_name' => 'B.Gbx']);
        $this->query->responses['GetChallengeList'] = $this->tracks();

        $this->chat('/admin shuffle');

        $removed = collect($this->query->calls)->firstWhere(0, 'RemoveChallengeList')[1][0];
        $added = collect($this->query->calls)->firstWhere(0, 'AddChallengeList')[1][0];
        $this->assertEqualsCanonicalizing(['A.Gbx', 'C.Gbx'], $removed);
        $this->assertEqualsCanonicalizing(['A.Gbx', 'C.Gbx'], $added);
        $this->assertBroadcast(self::BY . 'shuffled track list with {#highlite}3{#admin} tracks!');
    }

    public function test_writetracklist_saves_and_adds_random_filter()
    {
        $dir = sys_get_temp_dir() . '/admin-test-' . uniqid();
        mkdir("$dir/MatchSettings", 0777, true);
        ServerInfo::setTracksDirectory($dir);
        $this->query->responses['SaveMatchSettings'] = function ($file) use ($dir) {
            file_put_contents("$dir/$file", "<playlist><gameinfos></gameinfos></playlist>");
            return [true];
        };

        $this->chat('/admin writetracklist mine');

        $this->assertCalled('SaveMatchSettings', ['MatchSettings/mine.txt']);
        $this->assertStringContainsString('<random_map_order>1</random_map_order>', file_get_contents("$dir/MatchSettings/mine.txt"));
        $this->assertToldTo('boss', '{#server}> {#highlite}mine.txt{#admin} written');
        exec('rm -rf ' . escapeshellarg($dir));
    }

    public function test_readtracklist()
    {
        $dir = sys_get_temp_dir() . '/admin-test-' . uniqid();
        mkdir("$dir/MatchSettings", 0777, true);
        touch("$dir/MatchSettings/tracklist.txt");
        ServerInfo::setTracksDirectory($dir);
        $this->query->responses['LoadMatchSettings'] = [12];

        $this->chat('/admin readtracklist');
        $this->assertCalled('LoadMatchSettings', ['MatchSettings/tracklist.txt']);
        $this->assertToldTo('boss', '{#server}> {#highlite}tracklist.txt{#admin} read with {#highlite}12{#admin} tracks');

        $this->chat('/admin readtracklist nope');
        $this->assertToldTo('boss', '{#server}> {#error}Cannot find {#highlite}$i nope.txt {#error}!');
        exec('rm -rf ' . escapeshellarg($dir));
    }
}
