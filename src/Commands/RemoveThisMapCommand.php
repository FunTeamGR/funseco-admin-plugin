<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentMap;

class RemoveThisMapCommand extends AdminCommand
{
    protected string $name = 'admin removethis';
    protected string $description = 'Removes this track from rotation';

    public function handle(Player $player, array $args): void
    {
        $map = CurrentMap::get();
        if (!$map) {
            return;
        }

        \Server::query()->RemoveChallenge($map->file_name, function ($result) use ($player, $map) {
            if (!self::ok($result)) {
                $this->tell($player, '{#server}> {#error}Error removing track {#highlite}$i {1} {#error}!', $map->file_name);
                return;
            }
            $this->announce($this->text('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s {#admin}removes current track: {#highlite}{3}',
                $this->title($player), $this->nick($player), self::stripColors($map->name)));
        });
    }
}
