<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

class ListBansCommand extends LoginListCommand
{
    protected string $name = 'admin showbanlist';
    protected string $description = 'Displays current ban list';
    protected array $aliases = ['admin listbans'];

    public function handle(Player $player, array $args): void
    {
        $this->showServerList($player, 'GetBanList', 'Currently Banned Players:', 'No banned player(s) found!', ['Icons64x64_1', 'NotBuddy']);
    }
}
