<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

/** /admin debug: toggles debug output (config funseco.debug, in memory for this run). */
class DebugCommand extends AdminCommand
{
    protected string $name = 'admin debug';
    protected string $description = 'Toggles debugging output';

    public function handle(Player $player, array $args): void
    {
        $on = !config('funseco.debug', false);
        config(['funseco.debug' => $on]);
        $this->tell($player, '{#server}> Debug is now ' . ($on ? 'enabled' : 'disabled'));
    }
}
