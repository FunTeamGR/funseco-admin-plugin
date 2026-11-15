<?php
namespace Funseco\Admin;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\ServerInfo;
use Funseco\Core\Services\ManiaLink\Facades\ManiaLink;
use Funseco\Core\SharedKernel\Events\MapEnded;
use Funseco\Core\SharedKernel\Events\MapStarted;
use Funseco\Core\SharedKernel\Events\PlayerFinished;
use Funseco\Core\SharedKernel\Events\RoundStarted;

/**
 * The game's own score table (end of a race) and rounds finish panel (TMF custom_ui), which
 * /admin scorepanel and /admin roundsfinish can switch off. State is in memory, default on.
 * Only the one custom_ui field is sent each time, so other fields stay as they are.
 */
class GamePanels
{
    public const ID = 'admin_game_ui';
    private const MODES_WITH_ROUNDS = [0, 2, 5]; // Rounds, Team, Cup

    public static function scorePanel(): bool
    {
        return (bool) state()->get('admin:scorepanel', true);
    }

    public static function setScorePanel(bool $on): void
    {
        state()->forever('admin:scorepanel', $on);
        if (!$on) {
            self::everyone('scoretable', false);
        }
    }

    public static function roundsFinish(): bool
    {
        return (bool) state()->get('admin:roundsfinish', true);
    }

    public static function setRoundsFinish(bool $on): void
    {
        state()->forever('admin:roundsfinish', $on);
    }

    public function mapStarted(MapStarted $event): void
    {
        if (!self::scorePanel()) {
            self::everyone('scoretable', false);
        }
    }

    /** The score table is always back for the end of the race. */
    public function mapEnded(MapEnded $event): void
    {
        if (!self::scorePanel()) {
            self::everyone('scoretable', true);
        }
    }

    public function roundStarted(RoundStarted $event): void
    {
        if (!self::roundsFinish() && in_array(ServerInfo::getGameMode(), self::MODES_WITH_ROUNDS, true)) {
            self::everyone('round_scores', false);
        }
    }

    /** With the panel off, a player who finishes gets it for themselves. */
    public function playerFinished(PlayerFinished $event): void
    {
        if (!self::roundsFinish() && in_array(ServerInfo::getGameMode(), self::MODES_WITH_ROUNDS, true)) {
            ManiaLink::displayLogin($event->player, self::xml('round_scores', true), 0, false);
        }
    }

    private static function everyone(string $field, bool $visible): void
    {
        ManiaLink::displayAll(self::xml($field, $visible), 0, false);
    }

    private static function xml(string $field, bool $visible): string
    {
        return '<manialinks><manialink id="' . ManiaLink::getAction(self::ID) . '"></manialink>'
            . '<custom_ui><' . $field . ' visible="' . ($visible ? 'true' : 'false') . '"/></custom_ui></manialinks>';
    }
}
