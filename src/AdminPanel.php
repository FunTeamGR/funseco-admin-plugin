<?php
namespace Funseco\Admin;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Core\Services\Looks\PlayerLooks;
use Funseco\Core\Services\ManiaLink\Facades\ManiaLink;
use Funseco\Core\SharedKernel\Enums\Role;

/**
 * XAseco's admin panel (panels/AdminBelowChat.xml): icon buttons for operators and up.
 * Each button runs its /admin command as the clicking player, so role checks still apply.
 */
class AdminPanel
{
    /**
     * Button => [icon substyle, -, -, command], in the order of the template's button list
     * (Rewind, Pause, Play, Refresh, Green, Red, Buddy). Buttons carry no permissions: each
     * runs its command, which declares what it needs. Where they sit is the template's
     * business (config/panels.php, chosen per player with /admin panel).
     */
    public const BUTTONS = [
        'restartmap'   => ['ClipRewind', null, null, 'restartmap'],
        'endround'     => ['ClipPause', null, null, 'endround'],
        'nextmap'      => ['ClipPlay', null, null, 'nextmap'],
        'replaymap'    => ['Refresh', null, null, 'replaymap'],
        'pass'         => ['ArrowGreen', null, null, 'pass'],
        'cancel'       => ['ArrowRed', null, null, 'cancel'],
        'players'      => ['Buddy', null, null, 'players live'],
    ];

    /** Template names (full, with the 'Admin' prefix) this plugin can draw. */
    public static function templates(): array
    {
        return array_map(fn ($n) => 'Admin'.$n, array_keys(config('panels.admin', [])));
    }

    public static function actions(): array
    {
        return array_map(fn ($name) => 'admin_panel_' . $name, array_keys(self::BUTTONS));
    }

    public static function showToStaff(): void
    {
        foreach (CurrentPlayers::all() as $player) {
            self::showTo($player);
        }
    }

    public static function showTo(Player $player): void
    {
        if (!$player->effectiveRole()->allows(Role::Operator)) {
            return;
        }

        // '' = the player switched the panel off
        ManiaLink::displayLogin($player, self::xml(app(PlayerLooks::class)->panel($player, 'admin')) ?? self::empty());
    }

    public static function empty(): string
    {
        return '<manialink id="' . ManiaLink::getAction('admin_panel') . '"></manialink>';
    }

    /** XML of a template by full name (default: the server default); null for '' or an unknown name. */
    public static function xml(?string $name = null): ?string
    {
        $name ??= app(PlayerLooks::class)->defaultPanel('admin');
        $t = config('panels.admin.' . substr($name, strlen('Admin')));
        if ($name === '' || !is_array($t)) {
            return null;
        }

        $xml = '<manialink id="' . ManiaLink::getAction('admin_panel') . '"><frame posn="' . $t['frame'] . '">'
            . '<quad sizen="' . $t['bg'][0] . ' ' . $t['bg'][1] . '" style="' . $t['bg_style'] . '" substyle="NavButton"/>';
        foreach (array_keys(self::BUTTONS) as $i => $button) {
            [$x, $y, $w, $h] = $t['buttons'][$i];
            $xml .= '<quad posn="' . $x . ' ' . $y . ' 2" sizen="' . $w . ' ' . $h . '" style="Icons64x64_1" substyle="' . self::BUTTONS[$button][0]
                . '" action="' . ManiaLink::getAction('admin_panel_' . $button) . '"/>';
        }

        return $xml . '</frame></manialink>';
    }

    /** ManiaLink click handler for the panel buttons. */
    public function handle(Player $player, string $action): void
    {
        $command = self::BUTTONS[substr($action, strlen('admin_panel_'))][3] ?? null;
        if ($command !== null) {
            Chat::run($player, 'admin ' . $command);
        }
    }
}
