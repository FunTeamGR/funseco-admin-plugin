<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

/**
 * /admin ban — server ban by login. XAseco also adds the player's IP to its banned-IP
 * list; we don't know player IPs at this point, so use /admin banip for that.
 */
class BanCommand extends AdminCommand
{
    protected string $name = 'admin ban';
    protected string $description = 'Bans a player from server';
    protected string $usage = '<login/id>';

    public function handle(Player $player, array $args): void
    {
        if (!($target = $this->target($player, $args[0] ?? ''))) {
            return;
        }

        $this->announce($this->by($player) . 'bans {#highlite}{1}$z$s{#admin} !', $this->nick($target));
        \Server::query()->Ban($target->login);
    }
}
