<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

class UnbanCommand extends AdminCommand
{
    protected string $name = 'admin unban';
    protected string $description = 'UnBans a player from server';
    protected string $usage = '<login/id>';

    public function handle(Player $player, array $args): void
    {
        $arg = $args[0] ?? '';
        if (!($target = $this->target($player, $arg, offline: true))) {
            return;
        }

        \Server::query()->UnBan($target->login, function ($result) use ($player, $target, $arg) {
            if (!self::ok($result)) {
                $this->tell($player, '{#server}> {#highlite}{1}{#error} is not a banned player!', $arg);
                return;
            }
            $this->tell($player, $this->by($player, '>') . 'un-bans {#highlite}{1}', $this->nick($target));
        });
    }
}
