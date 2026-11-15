<?php
namespace Funseco\Admin\Commands;

use Funseco\Admin\BannedIps;
use Funseco\Core\Models\Player;

class UnbanIpCommand extends AdminCommand
{
    protected string $name = 'admin unbanip';
    protected string $description = 'UnBans an IP address from server';
    protected string $usage = '<ip>';

    public function handle(Player $player, array $args): void
    {
        $ip = $args[0] ?? '';

        if (!BannedIps::has($ip)) {
            $this->tell($player, '{#server}> {#highlite}{1}{#error} is not a banned IP address!', $ip);
            return;
        }

        BannedIps::remove($ip);
        $this->tell($player, $this->by($player, '>') . 'un-bans IP {#highlite}{1}', $ip);
    }
}
