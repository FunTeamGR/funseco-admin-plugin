<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentSpectators;

class ForceSpecCommand extends AdminCommand
{
    protected string $name = 'admin forcespec';
    protected string $description = 'Forces player into free spectator';
    protected string $usage = '<login/id>';

    public function handle(Player $player, array $args): void
    {
        if (!($target = $this->target($player, $args[0] ?? ''))) {
            return;
        }

        if (CurrentSpectators::has($target->login)) {
            $this->tell($player, '{#server}> {#highlite}{1} {#error}is already a spectator!', self::stripColors($target->nickname ?? $target->login));
            return;
        }

        // Force into spectator, then release so the player may rejoin the race (XAseco).
        \Server::query()->ForceSpectator($target->login, 1, function ($result) use ($player, $target) {
            if (!self::ok($result)) {
                return;
            }
            \Server::query()->ForceSpectator($target->login, 0);
            $this->announce($this->by($player) . 'forces player {#highlite}{1}$z$s{#admin} into spectator!', $this->nick($target));
        });
    }
}
