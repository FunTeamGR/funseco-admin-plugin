<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\ServerInfo;

/** /admin setgamemode <mode>: the mode for the next track. A pending change clears when a map starts. */
class SetGameModeCommand extends AdminCommand
{
    protected string $name = 'admin setgamemode';
    protected string $description = 'Sets next mode {ta,rounds,team,laps,stunts,cup}';
    protected string $usage = '<ta|rounds|team|laps|stunts|cup>';

    private const MODES = ['rounds' => 0, 'round' => 0, 'ta' => 1, 'team' => 2, 'laps' => 3, 'stunts' => 4, 'cup' => 5];

    private const PENDING = 'admin:gamemode_pending';

    public static function clearPending(): void
    {
        state()->forget(self::PENDING);
    }

    public function handle(Player $player, array $args): void
    {
        $arg = $args[0] ?? '';
        if ($arg === '') {
            $this->unknown($player, $args);
            return;
        }

        $mode = self::MODES[strtolower($arg)] ?? null;
        $label = strtoupper($arg);
        if ($mode === null) {
            $this->tell($player, '{#server}> {#error}Invalid game mode {#highlite}$i {1}$z$s {#error}!', $label);
            return;
        }

        $pending = (bool) state()->get(self::PENDING, false);
        if ($mode === ServerInfo::getGameMode() && !$pending) {
            $this->tell($player, '{#server}> Same game mode {#highlite}{1}', $label);
            state()->forget(self::PENDING);
            return;
        }

        \Server::query()->SetGameMode($mode);
        state()->forever(self::PENDING, true);
        $this->announce($this->by($player) . 'sets next game mode to {#highlite}{1}{#admin} !', $label);
    }
}
