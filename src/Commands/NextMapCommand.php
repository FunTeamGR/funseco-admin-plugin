<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

class NextMapCommand extends AdminCommand
{
    protected string $name = 'admin nextmap';
    protected string $description = 'Forces server to load next track';
    protected array $aliases = ['admin next', 'admin skipmap', 'admin skip'];

    public function handle(Player $player, array $args): void
    {
        \Server::query()->NextChallenge();
        $this->announce($this->by($player) . 'skips challenge!');
    }
}
