<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Core\SharedKernel\UI\Window;

/**
 * /admin helpall — XAseco showHelp() window variant: names + descriptions, 15 per page.
 */
class HelpAllCommand extends AdminCommand
{
    protected string $name = 'admin helpall';
    protected string $description = 'Displays help for available /admin commands';

    public function handle(Player $player, array $args): void
    {
        $role = $player->effectiveRole();
        $lines = array_map(
            fn ($cmd) => ['$f00... ' . HelpCommand::displayName($cmd), $cmd['description']],
            Chat::commands($player, 'admin'),
        );

        Window::show(
            $player,
            'Currently supported ' . HelpCommand::logTitle($role->value) . ' commands:',
            $lines,
            [1.3, 0.42, 1.3 - 0.42],
            ['Icons64x64_1', 'TrackInfo', -0.01],
        );
    }
}
