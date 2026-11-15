<?php
namespace Funseco\Admin;

use Funseco\Admin\Commands\PlayersCommand;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Chat\Facades\Chat;

/**
 * Click on a button in the /admin players window: runs "/admin <op> <login>" as the admin
 * (normal role checks apply), then refreshes the window, as XAseco does.
 */
class PlayersWindowAction
{
    public function handle(Player $admin, string $action): void
    {
        if (!preg_match('/^admin_players_([a-z]+)_(\d+)$/', $action, $m)) {
            return;
        }
        [, $op, $pid] = $m;

        $list  = PlayersCommand::lastList($admin->login);
        $login = $list['logins'][(int) $pid] ?? null;
        if ($login === null) {
            return;
        }

        Chat::run($admin, "admin $op", [$login]);
        Chat::run($admin, 'admin players', array_filter(explode(' ', $list['param'])));
    }

    /** Action names for every button the window can show. */
    public static function actions(): array
    {
        $actions = [];
        foreach (PlayersCommand::OPERATIONS as $op) {
            foreach (range(1, PlayersCommand::MAX_BUTTONS) as $pid) {
                $actions[] = "admin_players_{$op}_{$pid}";
            }
        }

        return $actions;
    }
}
