<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

class KickCommand extends AdminCommand
{
    protected string $name = 'admin kick';
    protected string $description = 'Kicks a player from server';
    protected string $usage = '<login/id>';

    public function handle(Player $player, array $args): void
    {
        if (!($target = $this->target($player, $args[0] ?? ''))) {
            return;
        }

        $this->announce($this->by($player) . 'kicked {#highlite}{1}$z$s{#admin} !', $this->nick($target));
        \Server::query()->Kick($target->login);
    }
}
