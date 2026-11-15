<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

class BlackCommand extends AdminCommand
{
    protected string $name = 'admin black';
    protected string $description = 'Blacklists a player from server';
    protected string $usage = '<login/id>';

    public function handle(Player $player, array $args): void
    {
        if (!($target = $this->target($player, $args[0] ?? '', offline: true))) {
            return;
        }

        $this->announce($this->by($player) . 'blacklists {#highlite}{1}$z$s{#admin} !', $this->nick($target));
        \Server::query()->BlackList($target->login);
        \Server::query()->Kick($target->login);
        \Server::query()->SaveBlackList(config('admin.blacklist_file'));
    }
}
