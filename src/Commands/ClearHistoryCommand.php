<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Events\JukeboxHistoryClearRequested;

/** /admin clearhist [N|-N|all]: trims the played-tracks history (owned by the jukebox plugin). */
class ClearHistoryCommand extends AdminCommand
{
    protected string $name = 'admin clearhist';
    protected string $description = 'Clears (part of) track history';
    protected string $usage = '[N|-N|all]';

    public function handle(Player $player, array $args): void
    {
        $arg = strtolower($args[0] ?? '');
        $amount = match (true) {
            $arg === 'all' => null,
            (bool) preg_match('/^-?\d+$/', $arg) => (int) $arg,
            default => 0,
        };

        $event = new JukeboxHistoryClearRequested($amount);
        event($event);
        if (!$event->handled) {
            return;
        }

        $s = fn (int $n) => $n === 1 ? '' : 's';
        $by = $this->by($player);
        if ($amount === 0 || $event->removed === 0 && $amount !== null) {
            $this->tell($player, '{#server}> {#admin}The track history contains {#highlite}{1}{#admin} track' . $s($event->size), $event->size);
        } elseif ($amount === null) {
            $this->announce($by . 'clears entire track history!');
        } elseif ($amount > 0) {
            $this->announce($by . 'clears newest {#highlite}{1}{#admin} track' . $s($event->removed) . ' from history!', $event->removed);
        } else {
            $this->announce($by . 'clears oldest {#highlite}{1}{#admin} track' . $s($event->removed) . ' from history!', $event->removed);
        }
    }
}
