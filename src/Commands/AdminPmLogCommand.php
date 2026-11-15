<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\UI\Window;

/** /admin pmlog: recent staff PMs, oldest first, long lines wrapped. */
class AdminPmLogCommand extends AdminCommand
{
    protected string $name = 'admin pmlog';
    protected string $description = 'Displays log of recent private admin messages';

    private const WRAP = 70;
    private const CUT = 73;

    public function handle(Player $player, array $args): void
    {
        $log = state()->get(AdminPmCommand::LOG_KEY, []);
        if (!$log) {
            $this->tell($player, '{#server}> {#error}No PM history found!');
            return;
        }

        $times = (bool) config('funseco.chatpmlog_times', true);
        $rows = [];
        foreach ($log as $entry) {
            $prefix = '$z' . ($times ? '<{#server}' . $entry['time'] . '$z> ' : '') . '[{#black}' . $entry['nick'] . '$z] ';
            $wrapped = wordwrap(self::stripColors($entry['text']), self::WRAP, "\n...", false);
            foreach (explode("\n", $wrapped) as $piece) {
                $rows[] = [$prefix . mb_substr($piece, 0, self::CUT)];
            }
        }

        Window::show($player, 'Recent Admin PM history:', $rows, [1.2], ['Icons64x64_1', 'Outbox']);
    }
}
