<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

class AddGuestCommand extends AdminCommand
{
    protected string $name = 'admin addguest';
    protected string $description = 'Adds a guest player to server';
    protected string $usage = '<login/id>';

    public function handle(Player $player, array $args): void
    {
        if (!($target = $this->target($player, $args[0] ?? '', offline: true))) {
            return;
        }

        \Server::query()->AddGuest($target->login);
        $this->tell($player, $this->by($player, '>') . 'adds guest {#highlite}{1}', $this->nick($target));
        \Server::query()->SaveGuestList(config('admin.guestlist_file'));
    }
}
