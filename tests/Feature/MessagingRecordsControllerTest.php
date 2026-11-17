<?php
namespace Funseco\Admin\Tests\Feature;

use Funseco\Admin\Tests\AdminTestCase;
use Illuminate\Support\Facades\Event;
use Funseco\Admin\BannedIps;
use Funseco\Admin\Commands\CallCommand;
use Funseco\Core\Models\Map;
use Funseco\Core\Models\Player;
use Funseco\Core\Servers\TmForever\Values\Checkpoint;
use Funseco\Core\Servers\TmForever\Values\CheckpointCollection;
use Funseco\Core\Services\Cache\CurrentMap;
use Funseco\Core\SharedKernel\Events\ControllerShutdownRequested;
use Funseco\Core\SharedKernel\Events\PrivateMessageSent;
use Funseco\Core\SharedKernel\LastTrackList;
use Funseco\Core\SharedKernel\MuteList;
use Funseco\Rankings\Time;
use Funseco\Records\Infrastructure\LocalRecord;
use Funseco\Records\Infrastructure\LocalRecords;

class MessagingRecordsControllerTest extends AdminTestCase
{
    private const BY = '{#server}>> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s{#admin} ';

    private function cps(int $score): CheckpointCollection
    {
        return new CheckpointCollection([new Checkpoint($score, 0)]);
    }

    private function windows(): array
    {
        return array_map(fn ($c) => $c[1], array_values(array_filter($this->query->calls, fn ($c) => $c[0] === 'SendDisplayManialinkPageToLogin')));
    }

    // --- messaging ---

    public function test_wall_shows_a_window_to_everyone_and_nothing_in_chat()
    {
        $this->online('joe', 'Joe');
        $this->chat('/admin wall ' . str_repeat('word ', 12));

        $windows = $this->windows();
        $this->assertCount(2, $windows);
        $this->assertEqualsCanonicalizing(['boss', 'joe'], array_column($windows, 0));
        $this->assertStringContainsString('Goddess', $windows[0][1]);
        $this->assertSame([], $this->broadcasts());

        $this->chat('/admin mta');
        $this->assertToldTo('boss', '{#server}> {#error}No message!');
    }

    public function test_pm_goes_to_other_pm_capable_staff_only()
    {
        $this->online('op', 'Op', 'operator');           // operators have no pm ability
        $other = $this->online('adm2', 'Other', 'admin');
        $this->online('joe', 'Joe');
        Event::fake([PrivateMessageSent::class]);

        $this->chat('/admin pm hello there');

        $sent = array_filter($this->query->calls, fn ($c) => $c[0] === 'ChatSendServerMessageToLogin');
        $byLogin = [];
        foreach ($sent as $c) {
            $byLogin[$c[1][1]] = $c[1][0];
        }
        $this->assertSame(['adm2', 'boss'], array_keys($byLogin));
        $this->assertSame(format_colors('{#error}-pm-$g[$f00Boss$z$s$i->{#logina}Admins$g]$i {#interact}hello there'), $byLogin['adm2']);
        $this->assertSame(format_colors('{#error}-pm-$g[$f00Boss$z$s$i->Other$z$s$i]$i {#interact}hello there'), $byLogin['boss']);
        Event::assertDispatched(PrivateMessageSent::class, 2);
    }

    public function test_pm_skips_staff_who_muted_the_sender_and_reports_nobody()
    {
        $other = $this->online('adm2', 'Other', 'admin');
        $this->app->instance(MuteList::class, new class ($other) implements MuteList {
            public function __construct(private Player $muter) {}
            public function isMuted(Player $listener, Player $speaker): bool { return $listener->login === $this->muter->login; }
            public function isGloballyMuted(Player $speaker): bool { return false; }
        });

        $this->chat('/admin pm anybody?');

        $this->assertToldTo('boss', '{#server}> {#error}No other admins currectly available!');
        $this->chat('/admin pm');
        $this->assertToldTo('boss', '{#server}> {#error}No message!');
    }

    public function test_pmlog_shows_logged_messages()
    {
        $this->chat('/admin pmlog');
        $this->assertToldTo('boss', '{#server}> {#error}No PM history found!');

        $this->chat('/admin pm remember the milk');
        $this->query->calls = [];
        $this->chat('/admin pmlog');

        $xml = $this->windows()[0][1];
        $this->assertStringContainsString('Recent Admin PM history:', $xml);
        $this->assertStringContainsString('remember the milk', $xml);
        $this->assertStringContainsString('Boss', $xml);
    }

    // --- records maintenance ---

    private function recordsOn(Map $map, array $scores): array
    {
        $players = [];
        foreach ($scores as $login => $score) {
            $player = Player::create(['login' => $login, 'nickname' => ucfirst($login)]);
            LocalRecord::create(['map_id' => $map->id, 'player_id' => $player->id, 'score' => $score, 'checkpoints' => $this->cps($score)]);
            Time::create(['map_id' => $map->id, 'player_id' => $player->id, 'score' => $score, 'checkpoints' => [$score], 'created_at' => now()]);
            $players[$login] = $player;
        }

        return $players;
    }

    public function test_delrec_deletes_record_and_times_and_updates_the_live_list()
    {
        $map = Map::factory()->create(['uid' => 'm1', 'name' => 'Speed']);
        CurrentMap::set($map);
        LocalRecords::clear();
        $players = $this->recordsOn($map, ['alice' => 30000, 'bob' => 31000, 'carol' => 32000]);
        $master = $this->online('master', 'Master');

        $this->chat('/admin delrec 2', $master);

        $this->assertSame(['alice', 'carol'], LocalRecord::where('map_id', $map->id)->ordered()->get()->map->player->pluck('login')->all());
        $this->assertSame(0, Time::where('player_id', $players['bob']->id)->count());
        $this->assertSame(2, Time::count(), 'other players keep their times');
        $this->assertSame(['alice', 'carol'], LocalRecords::all()->map->player->pluck('login')->all());
        $this->assertBroadcast('{#server}>> {#admin}God$z$s {#highlite}Master$z$s {#admin}removes record {#highlite}2{#admin} by {#highlite}Bob');

        $this->chat('/admin delrec 9', $master);
        $this->assertToldTo('master', '{#server}> {#error}No such record {#highlite}$i 9 {#error}!');
        $this->chat('/admin delrec', $master);
        $this->assertToldTo('master', '{#server}> {#error}Unknown admin command or missing parameter(s): {#highlite}$i delrec ');
    }

    public function test_delrec_refills_the_last_place_when_the_list_was_full()
    {
        $map = Map::factory()->create(['uid' => 'm1', 'name' => 'Speed']);
        CurrentMap::set($map);
        LocalRecords::clear();
        $max = \Funseco\Records\Commands\RecordCommands::maxRecs();
        $scores = [];
        for ($i = 1; $i <= $max; $i++) {
            $scores["p$i"] = 20000 + $i;
        }
        $this->recordsOn($map, $scores);
        // someone slower than everybody with a stored time but no record
        $slow = Player::create(['login' => 'slow', 'nickname' => 'Slow']);
        Time::create(['map_id' => $map->id, 'player_id' => $slow->id, 'score' => 99000, 'checkpoints' => [99000], 'created_at' => now()]);
        $master = $this->online('master', 'Master');

        $this->chat('/admin delrec 1', $master);

        $logins = LocalRecords::all()->map->player->pluck('login')->all();
        $this->assertCount($max, $logins);
        $this->assertSame('slow', end($logins));
        $this->assertSame($max, LocalRecord::where('map_id', $map->id)->count());
    }

    public function test_prunerecs_deletes_everything_for_a_listed_track()
    {
        $map = Map::factory()->create(['uid' => 'm2', 'name' => '$f00Old']);
        $other = Map::factory()->create(['uid' => 'm3']);
        $this->recordsOn($map, ['alice' => 30000]);
        $this->recordsOn($other, ['dave' => 25000]);
        app(LastTrackList::class)->show($this->admin, [['uid' => 'zz', 'file' => 'Z.Gbx', 'name' => 'Nope'], ['uid' => 'm2', 'file' => 'O.Gbx', 'name' => '$f00Old']]);
        $master = $this->online('master', 'Master');
        app(LastTrackList::class)->show($master, [['uid' => 'zz', 'file' => 'Z.Gbx', 'name' => 'Nope'], ['uid' => 'm2', 'file' => 'O.Gbx', 'name' => '$f00Old']]);

        $this->chat('/admin prunerecs 2', $master);

        $this->assertSame(0, LocalRecord::where('map_id', $map->id)->count());
        $this->assertSame(0, Time::where('map_id', $map->id)->count());
        $this->assertSame(1, LocalRecord::where('map_id', $other->id)->count());
        $this->assertToldTo('master', '{#server}> {#admin}Deleted all records & times for track: {#highlite}Old');

        $this->chat('/admin prunerecs 1', $master);
        $this->assertToldTo('master', '{#server}> {#error}Can\'t find ChallengeId for track: {#highlite}$i Nope / zz');
        $this->chat('/admin prunerecs 5', $master);
        $this->assertToldTo('master', '{#server}> {#orange}Track_ID not found - Type {#highlite}/list{#orange} to see all tracks.');
    }

    public function test_delrec_and_prunerecs_are_master_only_by_config()
    {
        $this->chat('/admin delrec 1');
        $this->assertDenied($this->admin);
    }

    // --- list files ---

    public function test_server_list_files()
    {
        $this->query->responses['SaveBlackList'] = [true];
        $this->chat('/admin writeblacklist');
        $this->assertCalled('SaveBlackList', ['blacklist.txt']);
        $this->assertToldTo('boss', '{#server}> {#highlite}blacklist.txt {#admin}written');

        $this->query->responses['LoadGuestList'] = [];
        $this->chat('/admin readguestlist mine.txt');
        $this->assertCalled('LoadGuestList', ['mine.txt']);
        $this->assertToldTo('boss', '{#server}> {#highlite}mine.txt {#error}not found, or error loading!');

        $this->query->responses['LoadBlackList'] = [true];
        $this->chat('/admin readblacklist');
        $this->assertToldTo('boss', '{#server}> {#highlite}blacklist.txt {#admin}read');
    }

    public function test_ip_list_save_clean_and_read()
    {
        config(['admin.bannedips_file' => sys_get_temp_dir() . '/ips-' . uniqid() . '.json']);
        BannedIps::add('1.2.3.4');

        $this->chat('/admin writeiplist');
        $this->assertToldTo('boss', '{#server}> {#highlite}' . basename(config('admin.bannedips_file')) . ' {#admin}written');
        $this->chat('/admin readiplist');
        $this->assertToldTo('boss', '{#server}> {#highlite}' . basename(config('admin.bannedips_file')) . ' {#admin}read');

        $master = $this->online('master', 'Master');
        $this->chat('/admin cleaniplist', $master);
        $this->assertSame([], BannedIps::all());
        $this->assertToldTo('master', '{#server}> {#admin}Cleaned banned IPs list!');

        $this->chat('/admin cleanblacklist', $master);
        $this->assertCalled('CleanBlackList', []);
        $this->chat('/admin cleanbanlist', $master);
        $this->chat('/admin cleanguestlist', $master);
        $this->assertCalled('CleanBanList', []);
        $this->assertCalled('CleanGuestList', []);
        @unlink(config('admin.bannedips_file'));
    }

    // --- server account & info ---

    public function test_coppers_requires_a_united_server()
    {
        $this->query->responses['GetMainServerPlayerInfo'] = [['Login' => 'srv']];
        $this->query->responses['GetServerOptions'] = [['Name' => 'My$z Server']];
        $this->query->responses['GetServerCoppers'] = [1234];

        $this->query->responses['GetDetailedPlayerInfo'] = [['OnlineRights' => 0]];
        $this->chat('/admin coppers');
        $this->assertToldTo('boss', '{#server}> {#error}This requires a TM United Forever server!');

        $this->query->responses['GetDetailedPlayerInfo'] = [['OnlineRights' => 3]];
        $this->chat('/admin coppers');
        $this->assertToldTo('boss', '{#server}> Server {#highlite}My$z Server$z$s {#server}owns {#highlite}1234 {#server}coppers!');
    }

    public function test_relays()
    {
        $this->query->responses['IsRelayServer'] = [true];
        $this->query->responses['GetPlayerList'] = [[]];
        $this->query->responses['GetMainServerPlayerInfo'] = [['Login' => 'master1', 'NickName' => 'Main']];
        $this->chat('/admin relays');
        $this->assertToldTo('boss', '{#server}> This server relays master server: {#highlite}master1{#server} ({#highlite}Main{#server})');

        $this->query->responses['IsRelayServer'] = [false];
        $this->query->responses['GetPlayerList'] = [[['Login' => 'boss', 'NickName' => 'B', 'Flags' => 0]]];
        $this->chat('/admin relays');
        $this->assertToldTo('boss', '{#server}> {#error}No relay servers connected');

        $this->query->responses['GetPlayerList'] = [[['Login' => 'boss', 'NickName' => 'B', 'Flags' => 0], ['Login' => 'relay1', 'NickName' => 'Relay One', 'Flags' => 100000]]];
        $this->chat('/admin relays');
        $this->assertStringContainsString('Relay One', $this->windows()[0][1]);
    }

    public function test_server_info_window()
    {
        $this->query->responses = [
            'GetVersion' => [['Name' => 'TmForever', 'Version' => '2.11.26']],
            'GetSystemInfo' => [['ServerHostName' => 'box', 'IsDedicated' => true]],
            'IsRelayServer' => [false],
            'GetMainServerPlayerInfo' => [['Login' => 'srv']],
            'GetServerCoppers' => [50],
            'GetServerPackMask' => ['United'],
            'GetLadderServerLimits' => [['LadderServerLimitMin' => 0, 'LadderServerLimitMax' => 50000]],
            'GetServerOptions' => [['Name' => 'My Server', 'Comment' => 'hidden', 'Password' => '', 'NextMaxPlayers' => 8, 'CurrentMaxPlayers' => 4]],
            'GetCurrentGameInfo' => [['GameMode' => 1, 'ChatTime' => 10000]],
            'GetNetworkStats' => [['Uptime' => 5, 'PlayerNetInfos' => [['x' => 1]]]],
            'GetCallVoteRatios' => [[['Command' => 'Kick', 'Ratio' => 0.5555]]],
            'GetPlayerList' => [[]],
            'GetDetailedPlayerInfo' => [['OnlineRights' => 3]],
        ];

        $this->chat('/admin server');

        $xml = $this->windows()[0][1];
        $this->assertStringContainsString('System info for: My Server', $xml);
        $this->assertStringContainsString('GetVersion:', $xml);
        $this->assertStringContainsString('2.11.26', $xml);
        $this->assertStringContainsString('True', $xml);
        $this->assertStringContainsString('Coppers', $xml);
        $this->assertStringNotContainsString('hidden', $xml, 'Comment is left out');

        $pages = state()->get('window:boss')['pages'];
        $this->assertCount(3, $pages, 'system / options / game info');
        $flat = json_encode($pages);
        $this->assertStringNotContainsString('hidden', $flat);
        $this->assertStringNotContainsString('NextMaxPlayers', $flat);
        $this->assertStringContainsString('CurrentMaxPlayers', json_encode($pages[1]));
        $this->assertStringContainsString('(TimeAttack)', json_encode($pages[2]));
        $this->assertStringContainsString('0.56', json_encode($pages[2]));
    }

    // --- controller ---

    public function test_call_is_masteradmin_only_and_parses_arguments()
    {
        $this->chat('/admin call GetVersion');
        $this->assertDenied($this->admin);

        $this->assertSame(['SetThing', true, 12, 'two words', 'x'], array_merge(['SetThing'], CallCommand::parse(['true', '12', '"two', 'words"', 'x'])));
        $this->assertCount(5, CallCommand::parse(['1', '2', '3', '4', '5', '6']));
        $this->assertSame(['*GetVersion :', '   Name = TmForever', '   Long = aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', '         bbb'],
            array_map('rtrim', CallCommand::flatten('GetVersion', ['Name' => 'TmForever', 'Long' => str_repeat('a', 32) . 'bbb'])));
    }

    public function test_call_runs_the_method_and_shows_results()
    {
        $master = $this->online('master', 'Master');
        $this->query->responses['GetVersion'] = [['Name' => 'TmForever']];
        $this->query->responses['system_listMethods'] = [['GetVersion', 'GetNothing']];

        $this->chat('/admin call GetVersion', $master);

        $this->assertCalled('GetVersion', []);
        $this->assertStringContainsString('Method results for:', $this->windows()[0][1]);
        $this->assertStringContainsString('TmForever', $this->windows()[0][1]);

        $this->query->calls = [];
        $this->query->faults['GetNothing'] = 'unknown method';
        $this->chat('/admin call GetNothing', $master);
        $this->assertToldTo('master', '{#server}> {#error}Method error for {#highlite}$i GetNothing{#error}: unknown method');

        $this->chat('/admin call __construct', $master);   // not a server method: never reaches our Query object
        $this->assertToldTo('master', '{#server}> {#error}No such method {#highlite}$i __construct {#error}!');

        $this->chat('/admin call', $master);
        $this->assertToldTo('master', '{#server}> {#error}No call specified - see {#highlite}$i /admin call help{#error} and {#highlite}$i /admin call list{#error}!');
    }

    public function test_call_list_and_help_for_unknown_method()
    {
        $master = $this->online('master', 'Master');
        $this->query->responses['system_listMethods'] = [['GetVersion', 'GetSystemInfo']];

        $this->chat('/admin call list', $master);
        $this->assertStringContainsString('GetSystemInfo', $this->windows()[0][1]);

        $this->chat('/admin call help Nope', $master);
        $this->assertToldTo('master', '{#server}> {#error}No such method {#highlite}$i Nope {#error}!');
    }

    public function test_unlock_with_empty_password_is_always_invalid()
    {
        $this->chat('/admin unlock anything');
        $this->assertToldTo('boss', '{#server}> {#error}Invalid password!');

        config(['admin.lock_password' => 'sesame']);
        $this->chat('/admin unlock sesame');
        $this->assertToldTo('boss', '{#server}> {#admin}Password accepted: admin commands unlocked!');
        $this->assertTrue(\Funseco\Admin\Commands\UnlockCommand::unlocked($this->admin));
    }

    public function test_a_configured_lock_password_blocks_every_admin_command_until_unlocked()
    {
        $this->online('bad', 'Bad');
        config(['admin.lock_password' => 'sesame']);

        $this->chat('/admin kick bad');
        $this->assertSame([], $this->serverCalls());
        $this->assertContains(format_colors("{#error}You don't have the required admin rights to do that!"), $this->messagesTo('boss'));

        $this->chat('/admin unlock wrong');
        $this->chat('/admin kick bad');
        $this->assertSame([], $this->serverCalls(), 'a wrong password does not unlock');

        $this->chat('/admin unlock sesame');
        $this->chat('/admin kick bad');
        $this->assertNotEmpty($this->serverCalls());
    }

    public function test_the_unlock_lasts_only_while_the_admin_is_connected()
    {
        $this->online('bad', 'Bad');
        config(['admin.lock_password' => 'sesame']);
        $this->chat('/admin unlock sesame');
        $this->assertTrue(\Funseco\Admin\Commands\UnlockCommand::unlocked($this->admin));

        event(new \Funseco\Core\SharedKernel\Events\PlayerDisconnected($this->admin));
        $this->assertFalse(\Funseco\Admin\Commands\UnlockCommand::unlocked($this->admin));

        $this->chat('/admin kick bad');
        $this->assertSame([], $this->serverCalls(), 'locked again after reconnecting');
    }

    public function test_debug_toggle()
    {
        $master = $this->online('master', 'Master');
        $this->chat('/admin debug', $master);
        $this->assertToldTo('master', '{#server}> Debug is now enabled');
        $this->chat('/admin debug', $master);
        $this->assertToldTo('master', '{#server}> Debug is now disabled');
    }

    public function test_shutdown_only_fires_the_event()
    {
        Event::fake([ControllerShutdownRequested::class]);
        $master = $this->online('master', 'Master');

        $this->chat('/admin shutdown', $master);
        Event::assertDispatched(ControllerShutdownRequested::class, fn ($e) => $e->stopServer === false);
        $this->assertSame([], $this->broadcasts());

        $this->chat('/admin shutdownall', $master);
        Event::assertDispatched(ControllerShutdownRequested::class, fn ($e) => $e->stopServer === true);
        $this->assertBroadcast('{#server}>> {#error}$wShutting down server now!');

        $this->chat('/admin shutdown');
        Event::assertDispatchedTimes(ControllerShutdownRequested::class, 2);
    }
}
