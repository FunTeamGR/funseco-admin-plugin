<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

class ListGuestsCommand extends LoginListCommand
{
    protected string $name = 'admin showguestlist';
    protected string $description = 'Displays current guest list';
    protected array $aliases = ['admin listguests'];

    public function handle(Player $player, array $args): void
    {
        $this->showServerList($player, 'GetGuestList', 'Current Guest Players:', 'No guest player(s) found!', ['Icons128x128_1', 'Invite']);
    }
}
