<?php
namespace Funseco\Admin;

use Funseco\Admin\Commands\PayCommand;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Core\Services\ManiaLink\Facades\ManiaLink;

/**
 * The Yes/No window of /admin pay. One pending payment per admin (a new request replaces it);
 * a click re-checks that the admin may still run /admin pay.
 */
class PayConfirmation
{
    public const WINDOW = 'admin_pay_window';
    public const YES = 'admin_pay_yes';
    public const NO = 'admin_pay_no';

    private static function key(string $login): string
    {
        return 'admin:pay:'.$login;
    }

    public static function ask(Player $admin, string $target, int $coppers, string $label): void
    {
        state()->put(self::key($admin->login), compact('target', 'coppers', 'label'), 300);
        ManiaLink::displayLogin($admin, self::xml($label));
    }

    public static function pending(string $login): ?array
    {
        return state()->get(self::key($login));
    }

    public function handle(Player $player, string $action): void
    {
        $pending = self::pending($player->login);
        state()->forget(self::key($player->login));
        ManiaLink::displayLogin($player, '<manialink id="'.ManiaLink::getAction(self::WINDOW).'"></manialink>');

        if (! $pending || ! Chat::canRun($player, 'admin pay')) {
            return;
        }
        if ($action === self::YES) {
            PayCommand::confirmed($player, $pending['target'], $pending['coppers'], $pending['label']);
        } else {
            PayCommand::cancelled($player, $pending['target']);
        }
    }

    public static function xml(string $label): string
    {
        return '<manialink id="'.ManiaLink::getAction(self::WINDOW).'"><frame posn="-20 8 20">'
            .'<quad sizen="40 14" style="Bgs1InRace" substyle="BgWindow2"/>'
            .'<label posn="20 -2 1" sizen="38 4" halign="center" style="TextTitle3" text="'.htmlspecialchars(format_colors('{#server}Server payment')).'"/>'
            .'<label posn="20 -5.5 1" sizen="38 4" halign="center" textsize="2" text="'.htmlspecialchars(format_colors($label)).'"/>'
            .'<quad posn="3 -9 1" sizen="15 4" style="Bgs1InRace" substyle="NavButton" action="'.ManiaLink::getAction(self::YES).'"/>'
            .'<label posn="10.5 -9.8 2" sizen="14 3" halign="center" textsize="2" text="$0f0Yes"/>'
            .'<quad posn="22 -9 1" sizen="15 4" style="Bgs1InRace" substyle="NavButton" action="'.ManiaLink::getAction(self::NO).'"/>'
            .'<label posn="29.5 -9.8 2" sizen="14 3" halign="center" textsize="2" text="$f00No"/>'
            .'</frame></manialink>';
    }
}
