<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

class EndRoundCommand extends AdminCommand
{
    protected string $name = 'admin endround';
    protected string $description = 'Forces end of current round';
    protected array $aliases = ['admin er'];

    public function handle(Player $player, array $args): void
    {
        \Server::query()->ForceEndRound();
        $this->announce($this->by($player) . 'forces round end!');
    }
}
