<?php
namespace Funseco\Admin\Tests\Feature;

use Funseco\Admin\Tests\AdminTestCase;
use Funseco\Admin\BannedIps;
use Funseco\Core\Models\Player;
use Funseco\Core\Servers\TmForever\Values\PlayerInfo;
use Funseco\Core\SharedKernel\Events\PlayerConnected;

class ModerationTest extends AdminTestCase
{
    private const BY = '{#server}>> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s{#admin} ';
    private const BY1 = '{#server}> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s{#admin} ';

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.bannedips_file' => sys_get_temp_dir() . '/bannedips-' . uniqid() . '.json']);
        $this->online('bad', '$wBad$zGuy');
    }

    protected function tearDown(): void
    {
        @unlink(config('admin.bannedips_file'));
        parent::tearDown();
    }

    private function windows(): array
    {
        return array_map(fn ($c) => $c[1][1], array_values(array_filter($this->query->calls, fn ($c) => $c[0] === 'SendDisplayManialinkPageToLogin')));
    }

    public function test_warn_shows_window_to_target_and_announces()
    {
        $op = $this->online('op', 'Op', 'operator');
        $this->chat('/admin warn bad', $op);

        $window = collect($this->query->calls)->firstWhere(0, 'SendDisplayManialinkPageToLogin');
        $this->assertSame('bad', $window[1][0]);
        $this->assertStringContainsString('This is an administrative warning.', $window[1][1]);
        $this->assertBroadcast('{#server}>> {#admin}$F93R$F85O$F67X$F49A$F29N$F09E$z$s {#highlite}Op$z$s{#admin} warned {#highlite}Bad$zGuy$z$s{#admin} !');
    }

    public function test_kick_by_login_and_by_players_number()
    {
        $this->chat('/admin kick bad');
        $this->assertCalled('Kick', ['bad']);
        $this->assertBroadcast(self::BY . 'kicked {#highlite}Bad$zGuy$z$s{#admin} !');

        $this->query->calls = [];
        $this->chat('/admin kick 2'); // boss=1, bad=2 (join order)
        $this->assertCalled('Kick', ['bad']);
    }

    public function test_unknown_target_and_id()
    {
        $this->chat('/admin kick ghost');
        $this->assertToldTo('boss', '{#server}> {#highlite}ghost {#error}is not a valid player! Use {#highlite}$i/players {#error}to find the correct login or Player_ID.');

        $this->chat('/admin kick 9');
        $this->assertToldTo('boss', '{#server}> {#error}Player_ID not found! Type {#highlite}$i/players {#error}to see all players.');
        $this->assertSame([], $this->serverCalls());
    }

    public function test_operator_cannot_kick()
    {
        $op = $this->online('op', 'Op', 'operator');
        $this->chat('/admin kick bad', $op);
        $this->assertDenied($op);
    }

    public function test_ban()
    {
        $this->chat('/admin ban bad');
        $this->assertCalled('Ban', ['bad']);
        $this->assertBroadcast(self::BY . 'bans {#highlite}Bad$zGuy$z$s{#admin} !');
    }

    public function test_unban_offline_player()
    {
        Player::create(['login' => 'gone', 'nickname' => 'Gone']);
        $this->query->responses['UnBan'] = [true];

        $this->chat('/admin unban gone');

        $this->assertCalled('UnBan', ['gone']);
        $this->assertToldTo('boss', self::BY1 . 'un-bans {#highlite}Gone');
    }

    public function test_unban_not_banned()
    {
        $this->query->responses['UnBan'] = [];
        $this->chat('/admin unban bad');
        $this->assertToldTo('boss', '{#server}> {#highlite}bad{#error} is not a banned player!');
    }

    public function test_black_and_unblack()
    {
        config(['admin.blacklist_file' => 'blacklist.txt']);
        $this->chat('/admin black bad');
        $this->assertSame([['BlackList', ['bad']], ['Kick', ['bad']], ['SaveBlackList', ['blacklist.txt']]], $this->serverCalls());
        $this->assertBroadcast(self::BY . 'blacklists {#highlite}Bad$zGuy$z$s{#admin} !');

        $this->query->calls = [];
        $this->query->responses['UnBlackList'] = [true];
        $this->chat('/admin unblack unknownlogin');
        $this->assertSame([['UnBlackList', ['unknownlogin']], ['SaveBlackList', ['blacklist.txt']]], $this->serverCalls());
        $this->assertToldTo('boss', self::BY1 . 'un-blacklists {#highlite}unknownlogin');

        $this->query->responses['UnBlackList'] = [];
        $this->chat('/admin unblack bad');
        $this->assertToldTo('boss', '{#server}> {#highlite}bad{#error} is not a blacklisted player!');
    }

    public function test_banip_unbanip_listips_and_kick_on_connect()
    {
        $this->chat('/admin banip nope');
        $this->assertToldTo('boss', '{#server}> {#highlite}nope{#error} is not a valid IP address!');

        $this->chat('/admin banip 1.2.3.4');
        $this->assertBroadcast(self::BY . 'bans IP {#highlite}1.2.3.4$z$s{#admin} !');
        $this->assertSame(['1.2.3.4'], BannedIps::all());

        $this->chat('/admin banip 1.2.3.4');
        $this->assertToldTo('boss', '{#server}> {#highlite}1.2.3.4{#error} is already banned!');

        $op = $this->online('op', 'Op', 'operator');
        $this->chat('/admin listips', $op);
        $this->assertStringContainsString('1.2.3.4', $this->windows()[0]);

        $this->query->responses['GetDetailedPlayerInfo'] = [['IPAddress' => '1.2.3.4:2350']];
        $this->query->calls = [];
        app(BannedIps::class)->handle(new PlayerConnected(Player::where('login', 'bad')->first(), new PlayerInfo(['Login' => 'bad'])));
        $this->assertCalled('Kick', ['bad']);

        $this->chat('/admin unbanip 1.2.3.4');
        $this->assertToldTo('boss', self::BY1 . 'un-bans IP {#highlite}1.2.3.4');
        $this->assertSame([], BannedIps::all());

        $this->chat('/admin unbanip 1.2.3.4');
        $this->assertToldTo('boss', '{#server}> {#highlite}1.2.3.4{#error} is not a banned IP address!');

        $this->chat('/admin listips');
        $this->assertToldTo('boss', '{#server}> {#error}No banned IP(s) found!');
    }

    public function test_addguest_removeguest()
    {
        config(['admin.guestlist_file' => 'guestlist.txt']);
        $this->chat('/admin addguest bad');
        $this->assertSame([['AddGuest', ['bad']], ['SaveGuestList', ['guestlist.txt']]], $this->serverCalls());
        $this->assertToldTo('boss', self::BY1 . 'adds guest {#highlite}Bad$zGuy');

        $this->query->responses['RemoveGuest'] = [true];
        $this->chat('/admin removeguest bad');
        $this->assertCalled('RemoveGuest', ['bad']);
        $this->assertToldTo('boss', self::BY1 . 'removes guest {#highlite}Bad$zGuy');

        $this->query->responses['RemoveGuest'] = [];
        $this->chat('/admin removeguest bad');
        $this->assertToldTo('boss', '{#server}> {#highlite}bad{#error} is not a guest player!');
    }

    public function test_server_lists_for_operators()
    {
        $op = $this->online('op', 'Op', 'operator');
        $this->query->responses['GetBanList'] = [[['Login' => 'bad', 'ClientName' => 'x', 'IPAddress' => '1.1.1.1']]];
        $this->query->responses['GetBlackList'] = [[]];
        $this->query->responses['GetGuestList'] = [[['Login' => 'nobody']]];

        $this->chat('/admin listbans', $op);
        $this->assertCalled('GetBanList', [1000, 0]);
        $this->assertStringContainsString('Currently Banned Players:', $this->windows()[0]);
        $this->assertStringContainsString('Bad$zGuy', $this->windows()[0]);

        $this->chat('/admin showblacklist', $op);
        $this->assertToldTo('op', '{#server}> {#error}No blacklisted player(s) found!');

        $this->chat('/admin listguests', $op);
        $this->assertStringContainsString('nobody', $this->windows()[1]);
    }
}
