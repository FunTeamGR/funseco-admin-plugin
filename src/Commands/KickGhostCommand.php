<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

/** /admin kickghost <login>: kicks a login without checking it is a known player (stuck connections). */
class KickGhostCommand extends AdminCommand
{
    protected string $name = 'admin kickghost';
    protected string $description = 'Kicks a ghost player from server';
    protected string $usage = '<login>';

    public function handle(Player $player, array $args): void
    {
        $login = $args[0] ?? '';
        if ($login === '') {
            $this->unknown($player, $args);
            return;
        }

        // The announcement goes out before the kick, as in XAseco.
        $this->announce($this->by($player) . 'kicked ghost {#highlite}{1}$z$s{#admin} !', $login);
        \Server::query()->Kick($login);
    }
}
