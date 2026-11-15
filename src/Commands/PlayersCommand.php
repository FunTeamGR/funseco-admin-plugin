<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\UI\Window;

/**
 * /admin players [string|live] — XAseco's player management window: online ("live") or
 * known players matching a string, with per-player Warn/Kick/Ban/Black/Guest/Spec buttons.
 * (XAseco's Ignore column is left out until muting exists.)
 */
class PlayersCommand extends AdminCommand
{
    protected string $name = 'admin players';
    protected string $description = 'Displays list of known players {string}';
    protected string $usage = '[string|live]';

    /** Rows that get buttons (XAseco: 200). */
    public const MAX_BUTTONS = 200;

    /** Button operations; each maps to "/admin <op> <login>". */
    public const OPERATIONS = ['warn', 'kick', 'ban', 'unban', 'black', 'unblack', 'addguest', 'removeguest', 'forcespec'];

    public function handle(Player $player, array $args): void
    {
        $param = $this->rest($args);

        \Server::query()->GetPlayerList(300, 0, 1, function ($result) use ($player, $param) {
            $online = [];
            foreach ($result[0] ?? [] as $pl) {
                $online[$pl['Login']] = ['nick' => $pl['NickName'], 'spec' => ((int) ($pl['SpectatorStatus'] ?? 0)) % 10 === 1];
            }
            $this->withLists(fn (array $bans, array $blacks, array $guests) => $this->show($player, $param, $online, $bans, $blacks, $guests));
        });
    }

    /** Fetch the server's ban, black and guest lists (logins as keys). */
    private function withLists(callable $then): void
    {
        $logins = fn ($result) => array_flip(array_column($result[0] ?? [], 'Login'));

        \Server::query()->GetBanList(1000, 0, function ($bans) use ($then, $logins) {
            \Server::query()->GetBlackList(1000, 0, function ($blacks) use ($then, $logins, $bans) {
                \Server::query()->GetGuestList(1000, 0, function ($guests) use ($then, $logins, $bans, $blacks) {
                    $then($logins($bans), $logins($blacks), $logins($guests));
                });
            });
        });
    }

    private function show(Player $admin, string $param, array $online, array $bans, array $blacks, array $guests): void
    {
        $live = strtolower($param) === 'live';

        if ($live) {
            $players = array_map(fn ($p) => $p['nick'], $online);
        } else {
            $like = '%' . $param . '%';
            $players = Player::where('login', 'like', $like)->orWhere('nickname', 'like', $like)
                ->limit(5000)->pluck('nickname', 'login')->all();
        }

        if (!$players) {
            $this->tell($admin, '{#server}> {#error}No player(s) found!');
            return;
        }

        $logins = [];
        $lines  = [];
        $pid    = 1;
        foreach ($players as $login => $nick) {
            $logins[$pid] = $login;
            $isOnline = isset($online[$login]);
            $button = fn (string $label, string $op) => $pid <= self::MAX_BUTTONS ? [$label, "admin_players_{$op}_{$pid}"] : $label;

            $ban   = isset($bans[$login]) ? $button('$c30UnBan', 'unban') : ($isOnline ? $button('$f30Ban', 'ban') : '$f30Ban');
            $black = isset($blacks[$login]) ? $button('$c03UnBlack', 'unblack') : $button('$f03Black', 'black');
            $guest = isset($guests[$login]) ? $button('$393Remove', 'removeguest') : $button('$3c3Add', 'addguest');
            $spec  = !$isOnline ? '$09cOffln' : ($online[$login]['spec'] ? '$09cSpec' : $button('$09fForce', 'forcespec'));

            $lines[] = [
                str_pad((string) $pid, 2, '0', STR_PAD_LEFT) . '.',
                '{#black}' . str_ireplace('$w', '', (string) ($nick ?: $login)) . '$z / {#login}' . $login,
                $isOnline ? $button('$ff3Warn', 'warn') : '$ff3Warn',
                $isOnline ? $button('$c3fKick', 'kick') : '$c3fKick',
                $ban, $black, $guest, $spec,
            ];
            $pid++;
        }

        state()->forever(self::key($admin->login), ['param' => $param, 'logins' => $logins]);

        Window::show(
            $admin,
            ($live ? 'Online' : 'Known') . ' Players On This Server:',
            $lines,
            [1.37, 0.15, 0.5, 0.12, 0.12, 0.12, 0.12, 0.12, 0.12],
            ['Icons128x128_1', 'Buddies'],
            perPage: 14,
            columns: ['Id', '{#nick}Nick $g/{#login} Login', 'Warn', 'Kick', 'Ban', 'Black', 'Guest', 'Spec'],
        );
    }

    /** The admin's last list: ['param' => string, 'logins' => [pid => login]]. */
    public static function lastList(string $adminLogin): ?array
    {
        return state()->get(self::key($adminLogin));
    }

    private static function key(string $login): string
    {
        return 'admin_players:' . $login;
    }
}
