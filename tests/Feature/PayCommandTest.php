<?php
namespace Funseco\Admin\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Funseco\Core\Testing\FakesServer;
use Funseco\Admin\Tests\TestCase;
use Funseco\Core\Models\Player;
use Funseco\Core\Servers\TmForever\Callbacks\BillUpdated as BillCallback;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Core\Services\ManiaLink\ManiaLinkService;

class PayCommandTest extends TestCase
{
    use FakesServer;
    use RefreshDatabase;

    private Player $master;

    protected function setUp(): void
    {
        parent::setUp();
        config(['adminops.masteradmins' => ['master']]);
        $this->fakeServer();
        state()->flush();
        state()->forever('funseco:server_united', true);
        $this->responses['GetMainServerPlayerInfo'] = ['Login' => 'srv'];
        $this->responses['GetServerCoppers'] = 1000;
        $this->responses['Pay'] = 77;
        CurrentPlayers::clear();
        $this->master = Player::factory()->create(['login' => 'master', 'nickname' => 'Master']);
        CurrentPlayers::add($this->master);
    }

    private function click(string $name, Player $as): void
    {
        $maniaLinks = app(ManiaLinkService::class);
        $maniaLinks->dispatchAnswer($as, $maniaLinks->getAction($name));
    }

    public function test_ask_confirm_pay_and_report_the_balance(): void
    {
        Chat::resolve('/admin pay racer 100', 'master');

        $this->assertStringContainsString('Pay 100 coppers to racer?', $this->window());
        $this->assertSame([], $this->sent('Pay'), 'nothing is paid before the click');

        $this->click('admin_pay_yes', $this->master);
        $this->assertSame([['racer', 100, 'Pay 100 coppers to racer?']], $this->sent('Pay'));

        $this->responses['GetServerCoppers'] = 895;
        event(new BillCallback(77, 4, 'Payed', 5));
        $this->assertStringContainsString('100', $this->messages()[count($this->messages()) - 1]);
        $this->assertStringContainsString('racer', $this->messages()[count($this->messages()) - 1]);
        $this->assertStringContainsString('895', $this->messages()[count($this->messages()) - 1]);
    }

    public function test_no_cancels(): void
    {
        Chat::resolve('/admin pay racer 100', 'master');
        $this->click('admin_pay_no', $this->master);

        $this->assertSame([], $this->sent('Pay'));
        $this->assertStringContainsString('Payment to racer cancelled', $this->messages()[0]);
        // a stale second click does nothing
        $this->click('admin_pay_yes', $this->master);
        $this->assertSame([], $this->sent('Pay'));
    }

    public function test_checks(): void
    {
        Chat::resolve('/admin pay racer', 'master');
        Chat::resolve('/admin pay racer -5', 'master');
        Chat::resolve('/admin pay srv 100', 'master');
        $this->responses['GetServerCoppers'] = 100;   // 100 > 100 - 2 - 5
        Chat::resolve('/admin pay racer 100', 'master');
        $m = $this->messages();
        $this->assertStringContainsString('/admin pay <login> <amount>', $m[0]);
        $this->assertStringContainsString('/admin pay <login> <amount>', $m[1]);
        $this->assertStringContainsString('Cannot pay coppers to the server itself', $m[2]);
        $this->assertStringContainsString('Insufficient server coppers', $m[3]);
        $this->assertStringContainsString('100', $m[3]);
        $this->assertSame([], $this->sent('SendDisplayManialinkPageToLogin'));

        $this->responses['GetServerCoppers'] = 108;   // 100 <= 108 - 2 - 5
        Chat::resolve('/admin pay racer 100', 'master');
        $this->assertNotSame('', $this->window());
    }

    public function test_needs_united_server_and_the_pay_ability(): void
    {
        state()->forever('funseco:server_united', false);
        Chat::resolve('/admin pay racer 100', 'master');
        $this->assertStringContainsString('requires a TM United Forever server', $this->messages()[0]);

        state()->forever('funseco:server_united', true);
        $boss = Player::factory()->create(['login' => 'boss', 'nickname' => 'Boss', 'role' => 'admin']);
        CurrentPlayers::add($boss);
        Chat::resolve('/admin pay racer 100', 'boss');   // admins lack the 'pay' ability
        $this->assertSame([], $this->sent('SendDisplayManialinkPageToLogin'));
        $this->assertStringContainsString('required admin rights', $this->messages()[count($this->messages()) - 1]);
    }

    public function test_failed_payout_is_told_to_the_admin(): void
    {
        Chat::resolve('/admin pay racer 100', 'master');
        $this->click('admin_pay_yes', $this->master);
        event(new BillCallback(77, 6, 'Not enough coppers', 5));
        $this->assertStringContainsString('Transaction failed:', $this->messages()[count($this->messages()) - 1]);
    }
}
