<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\SharedKernel\UI\Window;

/** /admin wall|mta <text>: a popup message window for every online player (nothing goes to chat). */
class WallCommand extends AdminCommand
{
    protected string $name = 'admin wall';
    protected string $description = 'Displays popup message to all players';
    protected string $usage = '<message>';
    protected array $aliases = ['admin mta'];

    private const WRAP = 40;

    public function handle(Player $player, array $args): void
    {
        $text = $this->rest($args);
        if ($text === '') {
            $this->noMessage($player);
            return;
        }

        $colour = (string) config('admin.wall_colour', '{#welcome}');
        $rows = array_map(fn ($line) => [$colour . $line], explode("\n", wordwrap($text, self::WRAP, "\n", true)));
        $header = '{#black}' . $this->title($player) . ' ' . $this->nick($player) . '$z :';

        foreach (CurrentPlayers::all() as $target) {
            Window::show($target, $header, $rows, [0.8], ['Icons64x64_1', 'Inbox']);
        }
    }
}
