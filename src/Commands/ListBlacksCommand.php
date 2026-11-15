<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

class ListBlacksCommand extends LoginListCommand
{
    protected string $name = 'admin showblacklist';
    protected string $description = 'Displays current black list';
    protected array $aliases = ['admin listblacks'];

    public function handle(Player $player, array $args): void
    {
        $this->showServerList($player, 'GetBlackList', 'Currently Blacklisted Players:', 'No blacklisted player(s) found!', ['Icons64x64_1', 'NotBuddy']);
    }
}
