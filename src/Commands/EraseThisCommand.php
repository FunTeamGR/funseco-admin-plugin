<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentMap;

class EraseThisCommand extends EraseCommand
{
    protected string $name = 'admin erasethis';
    protected string $description = 'Removes this track from rotation & deletes track file';
    protected string $usage = '';

    public function handle(Player $player, array $args): void
    {
        if ($map = CurrentMap::get()) {
            $this->erase($player, $map->file_name, $map->name, true);
        }
    }
}
