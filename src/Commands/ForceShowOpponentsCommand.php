<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

class ForceShowOpponentsCommand extends AdminCommand
{
    protected string $name = 'admin forceshowopp';
    protected string $description = 'Forces to show opponents {##/ALL/OFF}';
    protected string $usage = '[all|off|N]';

    public function handle(Player $player, array $args): void
    {
        $arg = strtolower($args[0] ?? '');
        $value = match (true) {
            $arg === 'all' => 1,
            $arg === 'off' => 0,
            ctype_digit($arg) && (int) $arg > 1 => (int) $arg,
            default => null,
        };

        if ($value !== null) {
            \Server::query()->SetForceShowAllOpponents($value);
            $shown = $value > 1 ? (string) $value : self::enabled($value === 1);
            $this->announce('{#server}>> {#admin}ForceShowAllOpponents set to {#highlite}' . $shown . '{#admin} on the next track');
            return;
        }

        \Server::query()->GetForceShowAllOpponents(function ($result) use ($player) {
            $current = (int) ($result[0]['CurrentValue'] ?? 0);
            $this->tell($player, '{#server}> {#admin}ForceShowAllOpponents is set to: {#highlite}' . ($current === 0 ? 'Off' : ($current === 1 ? 'All' : $current)));
        });
    }
}
