<?php
namespace Funseco\Admin\Commands;

use Illuminate\Support\Facades\Log;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentSpectators;
use Funseco\Core\SharedKernel\Enums\Role;

/**
 * /forceplay <login/id> (Milenco's pack): puts a spectator back into the race. Minimum role from
 * config admin.forceplay.level; no adminops ability is needed.
 */
class ForcePlayCommand extends AdminCommand
{
    protected string $name = 'forceplay';
    protected string $description = 'Forces a spectator into race mode';
    protected string $usage = '<login/id>';

    public function role(): ?Role
    {
        return Role::tryFrom((string) config('admin.forceplay.level', 'operator')) ?? Role::Operator;
    }

    public function ability(): ?string
    {
        return null;
    }

    public function handle(Player $player, array $args): void
    {
        $arg = (string) ($args[0] ?? '');
        if (!($target = $this->target($player, $arg))) {
            return;
        }

        if (!CurrentSpectators::has($target->login)) {
            $this->tell($player, '$ff0>> {#highlite}{1}{#error} is already in play-mode!', $arg);
            return;
        }

        // Force the state (2 = player), then release it so the player may choose again.
        \Server::query()->ForceSpectator($target->login, 2, function ($result) use ($player, $target) {
            if (!self::ok($result)) {
                return;
            }
            \Server::query()->ForceSpectator($target->login, 0);
            Log::info(sprintf('%s [%s] forced player %s into race modus!', ucfirst($player->effectiveRole()->value), $player->login, self::stripColors($target->nickname)));
            $this->announce((string) config('admin.forceplay.result'), $player->nickname.'$z$s', self::stripColors($player->nickname),
                $target->nickname.'$z$s', self::stripColors($target->nickname), $this->title($player));
        });
    }
}
