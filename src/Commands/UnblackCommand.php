<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

/** /admin unblack — XAseco accepts any login here (blacklisted players may be unknown). */
class UnblackCommand extends AdminCommand
{
    protected string $name = 'admin unblack';
    protected string $description = 'UnBlacklists a player from server';
    protected string $usage = '<login/id>';

    public function handle(Player $player, array $args): void
    {
        $arg = $args[0] ?? '';
        if (ctype_digit($arg)) {
            if (!($target = $this->target($player, $arg))) {
                return;
            }
            $login = $target->login;
        } else {
            $login = $arg;
        }
        $nick = Player::where('login', $login)->value('nickname') ?: $login;

        \Server::query()->UnBlackList($login, function ($result) use ($player, $arg, $nick) {
            if (!self::ok($result)) {
                $this->tell($player, '{#server}> {#highlite}{1}{#error} is not a blacklisted player!', $arg);
                return;
            }
            $this->tell($player, $this->by($player, '>') . 'un-blacklists {#highlite}{1}', str_ireplace('$w', '', $nick));
            \Server::query()->SaveBlackList(config('admin.blacklist_file'));
        });
    }
}
