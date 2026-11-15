<?php
namespace Funseco\Admin\Commands;

use Funseco\Admin\GamePanels;
use Funseco\Core\Models\Player;

class ScorePanelCommand extends AdminCommand
{
    protected string $name = 'admin scorepanel';
    protected string $description = 'Shows automatic scorepanel {ON/OFF}';
    protected string $usage = '[on|off]';

    public function handle(Player $player, array $args): void
    {
        if (($on = self::onOff($args[0] ?? null)) !== null) {
            GamePanels::setScorePanel($on);
            $this->announce('{#server}>> {#admin}Automatic ScorePanel set to ' . self::enabled($on));
            return;
        }

        $this->tell($player, '{#server}> {#admin}Automatic ScorePanel is currently ' . self::enabled(GamePanels::scorePanel()));
    }
}
