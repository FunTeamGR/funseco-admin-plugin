<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\UI\Window;

/**
 * XAseco-style "Id / Nick / Login" list window (ban, black, guest, staff lists).
 */
abstract class LoginListCommand extends AdminCommand
{
    /** @param string[] $logins */
    protected function showLogins(Player $admin, array $logins, string $header, string $empty, array $icon): void
    {
        if (!$logins) {
            $this->tell($admin, '{#server}> {#error}' . $empty);
            return;
        }

        $nicks = Player::whereIn('login', $logins)->pluck('nickname', 'login');
        $lines = [['Id', '{#nick}Nick $g/{#login} Login']];
        foreach (array_values($logins) as $i => $login) {
            $nick = str_ireplace('$w', '', $nicks[$login] ?? $login);
            $lines[] = [str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) . '.', '{#black}' . $nick . '$z / {#login}' . $login];
        }

        Window::show($admin, $header, $lines, [0.9, 0.1, 0.8], $icon);
    }

    /** Fetch a server list (GetBanList, GetBlackList, GetGuestList) and show its logins. */
    protected function showServerList(Player $admin, string $method, string $header, string $empty, array $icon): void
    {
        \Server::query()->{$method}(1000, 0, function ($result) use ($admin, $header, $empty, $icon) {
            $logins = array_column($result[0] ?? [], 'Login');
            $this->showLogins($admin, $logins, $header, $empty, $icon);
        });
    }
}
