<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\UI\Window;

class WarnCommand extends AdminCommand
{
    protected string $name = 'admin warn';
    protected string $description = 'Sends a kick/ban warning to a player';
    protected string $usage = '<login/id>';

    public function handle(Player $player, array $args): void
    {
        if (!($target = $this->target($player, $args[0] ?? ''))) {
            return;
        }

        $lines = array_map(fn ($line) => [$line], preg_split('/{br}/', config('admin.warning')));
        Window::show($target, '{#welcome}WARNING:', $lines, [0.8], ['Icons64x64_1', 'TV']);

        $this->announce($this->by($player) . 'warned {#highlite}{1}$z$s{#admin} !', $this->nick($target));
    }
}
