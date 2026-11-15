<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Events\RecordDeletionRequested;

/** /admin delrec <n>: deletes local record #n on the current track (the records plugin does the work). */
class DeleteRecordCommand extends AdminCommand
{
    protected string $name = 'admin delrec';
    protected string $description = 'Deletes specific record on current track';
    protected string $usage = '<record #>';

    public function handle(Player $player, array $args): void
    {
        $arg = $args[0] ?? '';
        if ($arg === '') {
            $this->unknown($player, $args);
            return;
        }

        $event = new RecordDeletionRequested($player, ctype_digit($arg) ? (int) $arg : 0);
        if ($event->rank >= 1) {
            event($event);
        }

        if (!$event->handled()) {
            $this->tell($player, '{#server}> {#error}No such record {#highlite}$i {1} {#error}!', $arg);
            return;
        }

        $this->announce($this->byTight($player) . 'removes record {#highlite}{1}{#admin} by {#highlite}{2}', $event->rank,
            self::stripColors((string) ($event->player->nickname ?: $event->player->login)));
    }
}
