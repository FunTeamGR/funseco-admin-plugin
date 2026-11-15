<?php
namespace Funseco\Admin\Tests\Feature;

use Funseco\Admin\Tests\AdminTestCase;
use Funseco\Admin\AdminPanel;
use Funseco\Core\Services\Cache\CurrentSpectators;
use Funseco\Core\Services\ManiaLink\Facades\ManiaLink;

class AdminPanelTest extends AdminTestCase
{
    private function sentTo(string $login): array
    {
        return array_map(fn ($c) => $c[1][1], array_values(array_filter(
            $this->query->calls,
            fn ($c) => $c[0] === 'SendDisplayManialinkPageToLogin' && $c[1][0] === $login,
        )));
    }

    private function click(string $action, $as = null): void
    {
        ManiaLink::dispatchAnswer($as ?? $this->admin, ManiaLink::getAction($action));
    }

    public function test_panel_is_xaseco_admin_below_chat_for_staff_only()
    {
        $player = $this->online('joe');
        $op = $this->online('op', role: 'operator');

        AdminPanel::showTo($this->admin);
        AdminPanel::showTo($op);
        AdminPanel::showTo($player);

        $xml = $this->sentTo('boss')[0];
        $this->assertStringContainsString('<frame posn="-33.5 -45.6 0"><quad sizen="21.8 2.5" style="Bgs1InRace" substyle="NavButton"/>', $xml);
        foreach (['ClipRewind', 'ClipPause', 'ClipPlay', 'Refresh', 'ArrowGreen', 'ArrowRed', 'Buddy'] as $icon) {
            $this->assertStringContainsString('substyle="'.$icon.'" action="', $xml);
        }
        $this->assertNotFalse(simplexml_load_string($xml));
        $this->assertCount(1, $this->sentTo('op'));
        $this->assertSame([], $this->sentTo('joe'));
    }

    public function test_buttons_run_their_admin_commands()
    {
        $this->click('admin_panel_endround');
        $this->click('admin_panel_restartmap');
        $this->click('admin_panel_cancel');

        $this->assertCalled('ForceEndRound');
        $this->assertCalled('ChallengeRestart');
        $this->assertCalled('CancelVote');
        $this->assertBroadcast('{#server}>> {#admin}'.config('admin.titles.admin').'$z$s {#highlite}$f00Boss$z$s{#admin} cancels vote!');
    }

    public function test_buttons_keep_role_checks()
    {
        $player = $this->online('joe');

        $this->click('admin_panel_nextmap', $player);

        $this->assertDenied($player);
    }

    public function test_pass_follows_adminops_ability()
    {
        // The owner's adminops.xml denies 'pass' to admins; masteradmins may do everything.
        $this->click('admin_panel_pass');
        $this->assertDenied($this->admin);

        $master = $this->online('master', 'Master');
        $this->click('admin_panel_pass', $master);
        // answered by the votes plugin, which owns chat votes
        $this->assertContains(format_colors('{#server}> {#error}There is no vote right now!'), $this->messagesTo('master'));
    }

    public function test_forcespec_forces_then_releases()
    {
        $target = $this->online('racer', '$0f0Racer');
        $this->query->responses['ForceSpectator'] = [true];

        $this->chat('/admin forcespec racer');

        $this->assertCalled('ForceSpectator', ['racer', 1]);
        $this->assertCalled('ForceSpectator', ['racer', 0]);
        $this->assertBroadcast('{#server}>> {#admin}'.config('admin.titles.admin').'$z$s {#highlite}$f00Boss$z$s{#admin} forces player {#highlite}$0f0Racer$z$s{#admin} into spectator!');

        CurrentSpectators::add($target);
        $this->chat('/admin forcespec racer');
        $this->assertToldTo('boss', '{#server}> {#highlite}Racer {#error}is already a spectator!');
    }

    public function test_players_window_buttons_run_commands_and_refresh()
    {
        $this->online('racer', '$0f0Racer');
        $this->query->responses['GetPlayerList'] = [[
            ['Login' => 'boss', 'NickName' => '$f00Boss', 'SpectatorStatus' => 0],
            ['Login' => 'racer', 'NickName' => '$0f0Racer', 'SpectatorStatus' => 0],
        ]];
        $this->query->responses['GetBanList'] = [[]];
        $this->query->responses['GetBlackList'] = [[['Login' => 'racer']]];
        $this->query->responses['GetGuestList'] = [[]];

        $this->click('admin_panel_players');

        $windows = $this->sentTo('boss');
        $xml = end($windows);
        $this->assertStringContainsString('Online Players On This Server:', $xml);
        $this->assertStringContainsString('racer', $xml, 'row shown');
        $this->assertStringContainsString('action="'.ManiaLink::getAction('admin_players_kick_2').'"', $xml);
        $this->assertStringContainsString('action="'.ManiaLink::getAction('admin_players_unblack_2').'"', $xml, 'blacklisted shows UnBlack');
        $listsFetched = count(array_filter($this->query->calls, fn ($c) => $c[0] === 'GetPlayerList'));

        $this->click('admin_players_kick_2');

        $this->assertCalled('Kick', ['racer']);
        $this->assertSame($listsFetched + 1, count(array_filter($this->query->calls, fn ($c) => $c[0] === 'GetPlayerList')), 'window refreshed');
    }
}
