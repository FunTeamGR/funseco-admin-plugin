<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

class RestartMapCommand extends AdminCommand
{
    protected string $name = 'admin restartmap';
    protected string $description = 'Restarts currently running track';
    protected array $aliases = ['admin res'];

    public function handle(Player $player, array $args): void
    {
        \Server::query()->ChallengeRestart();
        $this->announce($this->by($player) . 'restarts challenge!');
    }
}
