<?php
namespace Funseco\Admin\Commands;

use Funseco\Admin\BannedIps;
use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\UI\Window;

class ListIpsCommand extends AdminCommand
{
    protected string $name = 'admin showiplist';
    protected string $description = 'Displays current banned IPs list';
    protected array $aliases = ['admin listips'];

    public function handle(Player $player, array $args): void
    {
        $ips = BannedIps::all();
        if (!$ips) {
            $this->tell($player, '{#server}> {#error}No banned IP(s) found!');
            return;
        }

        $lines = [['Id', '{#login}IP']];
        foreach ($ips as $i => $ip) {
            $lines[] = [str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) . '.', '{#black}' . $ip];
        }

        Window::show($player, 'Currently Banned IPs:', $lines, [0.6, 0.1, 0.5], ['Icons64x64_1', 'NotBuddy']);
    }
}
