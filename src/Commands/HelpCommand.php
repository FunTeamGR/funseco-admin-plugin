<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Chat\Facades\Chat;

/**
 * /admin help — XAseco showHelp() plain variant: one chat line of command names.
 */
class HelpCommand extends AdminCommand
{
    protected string $name = 'admin help';
    protected string $description = 'Shows all available /admin commands';

    public function handle(Player $player, array $args): void
    {
        $role = $player->effectiveRole();
        $names = array_map([self::class, 'displayName'], Chat::commands($player, 'admin'));

        $this->tell($player, '{#interact}Currently supported ' . self::logTitle($role->value) . ' commands:' . "\n" . implode(', ', $names));
    }

    /** 'admin nextmap' + aliases → 'nextmap/next/skipmap/skip', as XAseco lists them. */
    public static function displayName(array $command): string
    {
        $strip = fn (string $name) => preg_replace('/^admin /', '', $name);

        return implode('/', array_map($strip, [$command['name'], ...$command['aliases']]));
    }

    public static function logTitle(string $role): string
    {
        return match ($role) {
            'masteradmin' => 'MasterAdmin',
            'admin'       => 'Admin',
            default       => 'Operator',
        };
    }
}
