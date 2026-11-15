<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

class RemoveGuestCommand extends AdminCommand
{
    protected string $name = 'admin removeguest';
    protected string $description = 'Removes a guest player from server';
    protected string $usage = '<login/id>';

    public function handle(Player $player, array $args): void
    {
        $arg = $args[0] ?? '';
        if (!($target = $this->target($player, $arg, offline: true))) {
            return;
        }

        \Server::query()->RemoveGuest($target->login, function ($result) use ($player, $target, $arg) {
            if (!self::ok($result)) {
                $this->tell($player, '{#server}> {#highlite}{1}{#error} is not a guest player!', $arg);
                return;
            }
            $this->tell($player, $this->by($player, '>') . 'removes guest {#highlite}{1}', $this->nick($target));
            \Server::query()->SaveGuestList(config('admin.guestlist_file'));
        });
    }
}
