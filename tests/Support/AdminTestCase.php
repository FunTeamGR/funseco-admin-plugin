<?php
namespace Funseco\Admin\Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Funseco\Admin\AdminServiceProvider;
use Funseco\Core\Testing\TestCase;
use Funseco\Core\Models\Map;
use Funseco\Core\Models\Player;
use Funseco\Core\Servers\TmForever\Facades\Server;
use Funseco\Core\Services\Cache\CurrentMap;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\Services\Chat\Facades\Chat;

/**
 * Admin command tests: Server::query() returns a recorder that captures every call
 * ([method, args without callbacks]) and answers callbacks from $responses.
 */
abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    /** Admin only; a plugin's tests extend this and list the plugins they need (see their tests/AdminTestCase). */
    protected function plugins(): array
    {
        return [AdminServiceProvider::class];
    }

    protected RecordingQuery $query;
    protected Player $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['adminops.masteradmins' => ['master']]);
        AbilitySnapshot::apply();
        $this->query = new RecordingQuery();
        Server::shouldReceive('query')->andReturn($this->query);

        CurrentPlayers::clear();
        $this->admin = $this->online('boss', '$f00Boss', 'admin');
    }

    protected function online(string $login, ?string $nick = null, string $role = 'player'): Player
    {
        $player = Player::create(['login' => $login, 'nickname' => $nick ?? $login, 'role' => $role]);
        CurrentPlayers::add($player);

        return $player;
    }

    protected function map(array $attributes = []): Map
    {
        $map = new Map($attributes + ['uid' => 'uid1', 'name' => '$f00Speed', 'file_name' => 'Challenges/Speed.Challenge.Gbx']);
        CurrentMap::set($map);

        return $map;
    }

    /** Run "/admin ..." through the chat service as $player (role checks included). */
    protected function chat(string $text, ?Player $as = null): void
    {
        Chat::resolve($text, ($as ?? $this->admin)->login);
    }

    /** Server calls other than chat messages. */
    protected function serverCalls(): array
    {
        return array_values(array_filter($this->query->calls, fn ($c) => !str_starts_with($c[0], 'ChatSend')));
    }

    /** Chat messages sent to everyone. */
    protected function broadcasts(): array
    {
        return array_map(fn ($c) => $c[1][0], array_values(array_filter($this->query->calls, fn ($c) => $c[0] === 'ChatSendServerMessage')));
    }

    /** Chat messages sent to $login. */
    protected function messagesTo(string $login): array
    {
        return array_map(fn ($c) => $c[1][0], array_values(array_filter(
            $this->query->calls,
            fn ($c) => $c[0] === 'ChatSendServerMessageToLogin' && $c[1][1] === $login,
        )));
    }

    protected function assertCalled(string $method, array $args = []): void
    {
        $this->assertContains([$method, $args], $this->query->calls, "Expected $method(" . json_encode($args) . '). Calls: ' . json_encode($this->serverCalls()));
    }

    protected function assertBroadcast(string $template): void
    {
        $this->assertContains(format_colors('{#message}' . $template), $this->broadcasts(), 'Broadcasts: ' . json_encode($this->broadcasts()));
    }

    protected function assertToldTo(string $login, string $template): void
    {
        $this->assertContains(format_colors('{#message}' . $template), $this->messagesTo($login), 'Messages: ' . json_encode($this->messagesTo($login)));
    }

    protected function assertDenied(Player $player): void
    {
        $this->assertSame([], $this->serverCalls());
        $this->assertNotEmpty($this->messagesTo($player->login));
        $staff = $player->effectiveRole()->allows(\Funseco\Core\SharedKernel\Enums\Role::Operator);
        $this->assertStringContainsString(
            $staff ? "You don't have the required admin rights to do that!" : 'You have to be in admin list to do that!',
            $this->messagesTo($player->login)[0],
        );
    }
}

class RecordingQuery extends \Funseco\Core\Servers\TmForever\Query
{
    public function __construct()
    {
    }

    public array $calls = [];
    /** method => result passed to callbacks, or a closure (args) => result */
    public array $responses = [];
    /** method => fault text: callbacks get ([], $fault) like a server fault */
    public array $faults = [];

    public function system_listMethods(...$args)
    {
        return $this->__call('system_listMethods', $args);
    }

    public function system_methodSignature(...$args)
    {
        return $this->__call('system_methodSignature', $args);
    }

    public function system_methodHelp(...$args)
    {
        return $this->__call('system_methodHelp', $args);
    }

    public function __call($method, $args)
    {
        $callbacks = [];
        while ($args && end($args) instanceof \Closure) {
            array_unshift($callbacks, array_pop($args));
        }
        $this->calls[] = [$method, $args];

        if ($callbacks && isset($this->faults[$method])) {
            foreach ($callbacks as $callback) {
                $callback([], $this->faults[$method]);
            }
            return;
        }

        if ($callbacks && array_key_exists($method, $this->responses)) {
            $response = $this->responses[$method];
            $result = $response instanceof \Closure ? $response(...$args) : $response;
            foreach ($callbacks as $callback) {
                $callback($result);
            }
        }
    }
}
