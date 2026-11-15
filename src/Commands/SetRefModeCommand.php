<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

class SetRefModeCommand extends AdminCommand
{
    protected string $name = 'admin setrefmode';
    protected string $description = 'Sets referee mode {0=top3,1=all}';
    protected string $usage = '[0|1]';

    public function handle(Player $player, array $args): void
    {
        $arg = $args[0] ?? '';

        if ($arg === '') {
            // XAseco answers everyone here; we answer the admin only.
            \Server::query()->GetRefereeMode(function ($result) use ($player) {
                $this->tell($player, '{#server}> {#admin}Referee mode is set to {#highlite}' . ((int) ($result[0] ?? 0) === 1 ? 'All' : 'Top-3'));
            });
            return;
        }

        if ($arg !== '0' && $arg !== '1') {
            $this->tell($player, '{#server}> {#error}Invalid referee mode {#highlite}$i {1}$z$s {#error}!', strtoupper($arg));
            return;
        }

        \Server::query()->SetRefereeMode((int) $arg);
        $this->announce($this->by($player) . 'sets referee mode to {#highlite}{1}{#admin} !', $arg);
    }
}
