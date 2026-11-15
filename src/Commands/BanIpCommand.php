<?php
namespace Funseco\Admin\Commands;

use Funseco\Admin\BannedIps;
use Funseco\Core\Models\Player;

class BanIpCommand extends AdminCommand
{
    protected string $name = 'admin banip';
    protected string $description = 'Bans an IP address from server';
    protected string $usage = '<ip>';

    public function handle(Player $player, array $args): void
    {
        $ip = $args[0] ?? '';

        if (!preg_match('/^\d+\.\d+\.\d+\.\d+$/', $ip)) {
            $this->tell($player, '{#server}> {#highlite}{1}{#error} is not a valid IP address!', $ip);
            return;
        }
        if (BannedIps::has($ip)) {
            // XAseco builds this message but never sends it; we do.
            $this->tell($player, '{#server}> {#highlite}{1}{#error} is already banned!', $ip);
            return;
        }

        $this->announce($this->by($player) . 'bans IP {#highlite}{1}$z$s{#admin} !', $ip);
        BannedIps::add($ip);
    }
}
