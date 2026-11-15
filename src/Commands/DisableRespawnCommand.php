<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

class DisableRespawnCommand extends AdminCommand
{
    protected string $name = 'admin disablerespawn';
    protected string $description = 'Disables respawn at CPs {ON/OFF}';
    protected string $usage = '[on|off]';

    public function handle(Player $player, array $args): void
    {
        if (($on = self::onOff($args[0] ?? null)) !== null) {
            \Server::query()->SetDisableRespawn($on);
            $this->announce('{#server}>> {#admin}DisableRespawn set to ' . self::enabled($on) . ' on the next track');
            return;
        }

        \Server::query()->GetDisableRespawn(function ($result) use ($player) {
            $this->tell($player, '{#server}> {#admin}DisableRespawn is currently ' . self::enabled((bool) ($result[0]['CurrentValue'] ?? false)));
        });
    }
}
