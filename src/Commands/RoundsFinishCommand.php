<?php
namespace Funseco\Admin\Commands;

use Funseco\Admin\GamePanels;
use Funseco\Core\Models\Player;

class RoundsFinishCommand extends AdminCommand
{
    protected string $name = 'admin roundsfinish';
    protected string $description = 'Shows rounds panel upon first finish {ON/OFF}';
    protected string $usage = '[on|off]';

    public function handle(Player $player, array $args): void
    {
        if (($on = self::onOff($args[0] ?? null)) !== null) {
            GamePanels::setRoundsFinish($on);
            $this->announce('{#server}>> {#admin}Rounds Finishpanel set to ' . self::enabled($on));
            return;
        }

        $this->tell($player, '{#server}> {#admin}Rounds Finishpanel is currently ' . self::enabled(GamePanels::roundsFinish()));
    }
}
