<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\SharedKernel\Events\PrivateMessageSent;
use Funseco\Core\SharedKernel\MuteList;

/**
 * /admin pm <text>: staff chat. Goes to every other online player with the 'pm' ability (not to
 * those who muted the sender); the sender gets an echo listing who received it. Logged for
 * /admin pmlog (last 30, in memory).
 */
class AdminPmCommand extends AdminCommand
{
    protected string $name = 'admin pm';
    protected string $description = 'Sends private message to all available admins';
    protected string $usage = '<message>';

    public const LOG_KEY = 'admin:pmlog';
    public const LOG_MAX = 30;

    public function handle(Player $player, array $args): void
    {
        $text = $this->rest($args);
        if ($text === '') {
            $this->noMessage($player);
            return;
        }

        $mutes = app(MuteList::class);
        $recipients = CurrentPlayers::all()
            ->filter(fn (Player $p) => $p->login !== $player->login && $p->allowed('pm') && !$mutes->isMuted($p, $player))
            ->values();

        $log = [...state()->get(self::LOG_KEY, []), ['time' => date('H:i:s'), 'nick' => $this->nick($player), 'text' => $text]];
        state()->forever(self::LOG_KEY, array_slice($log, -self::LOG_MAX));

        if ($recipients->isEmpty()) {
            $this->tell($player, '{#server}> {#error}No other admins currectly available!');
            return;
        }

        $head = '{#error}-pm-$g[' . $this->nick($player) . '$z$s$i->';
        $toAdmins = format_colors($head . '{#logina}Admins$g]$i {#interact}' . $text);
        foreach ($recipients as $recipient) {
            \Server::query()->ChatSendServerMessageToLogin($toAdmins, $recipient->login);
        }
        event(new PrivateMessageSent($player, $recipients->pluck('login')->all(), $toAdmins));

        $nicks = $recipients->map(fn (Player $p) => $this->nick($p))->implode('$z$s$i,');
        $echo = format_colors($head . $nicks . '$z$s$i]$i {#interact}' . $text);
        \Server::query()->ChatSendServerMessageToLogin($echo, $player->login);
        event(new PrivateMessageSent($player, [$player->login], $echo));
    }
}
