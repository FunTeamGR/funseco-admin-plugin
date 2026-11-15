<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentSpectators;

/** /admin specfree <login/id>: puts a spectator's camera into free mode. */
class SpecFreeCommand extends AdminCommand
{
    protected string $name = 'admin specfree';
    protected string $description = 'Forces spectator into free mode';
    protected string $usage = '<login/id>';

    public function handle(Player $player, array $args): void
    {
        if (($args[0] ?? '') === '') {
            $this->unknown($player, $args);
            return;
        }
        if (!($target = $this->target($player, $args[0]))) {
            return;
        }
        if (!CurrentSpectators::has($target->login)) {
            $this->tell($player, '{#server}> {#highlite}{1} {#error}is not a spectator!', self::stripColors((string) ($target->nickname ?: $target->login)));
            return;
        }

        \Server::query()->ForceSpectatorTarget($target->login, '', 2);
        $this->announce($this->by($player) . 'forces spectator free mode on {#highlite}{1}$z$s{#admin} !', $this->nick($target));
    }
}
