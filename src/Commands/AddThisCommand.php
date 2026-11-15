<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentMap;
use Funseco\Core\Services\Cache\ServerInfo;

/**
 * /admin addthis: make the current track permanent. Funseco downloads every added track to
 * Challenges/TMX, so this only has work to do for a track still in a temporary folder
 * (Challenges/TMXtmp); otherwise the track is "already permanently in track list".
 */
class AddThisCommand extends AdminCommand
{
    protected string $name = 'admin addthis';
    protected string $description = 'Adds current /add-ed track permanently';

    private const TEMP = 'Challenges/TMXtmp/';
    private const PERMANENT = 'Challenges/TMX/';

    public function handle(Player $player, array $args): void
    {
        $map = CurrentMap::get();
        if (!$map) {
            return;
        }

        $plain = self::stripColors($map->name);
        $file = str_replace('\\', '/', (string) $map->file_name);
        if (!str_starts_with($file, self::TEMP)) {
            $this->tell($player, '{#server}> {#error}Current track {#highlite}$i {1} {#error}already permanently in track list!', $plain);
            return;
        }

        $dir = rtrim((string) ServerInfo::getTracksDirectory(), '/');
        $target = self::PERMANENT . substr($file, strlen(self::TEMP));

        \Server::query()->RemoveChallenge($map->file_name, function ($result) use ($player, $map, $plain, $dir, $file, $target) {
            if (!self::ok($result)) {
                $this->tell($player, '{#server}> {#error}Error removing track {#highlite}$i {1} {#error}!', $file);
                return;
            }
            @mkdir(dirname("$dir/$target"), 0755, true);
            if (!@rename("$dir/$file", "$dir/$target")) {
                $this->tell($player, '{#server}> {#error}Error moving track {#highlite}$i {1} {#error}!', $file);
                $target = $file;
            }
            \Server::query()->AddChallenge($target, function ($result) use ($player, $map, $plain, $target) {
                if (!self::ok($result)) {
                    $this->tell($player, '{#server}> {#error}Error adding track {#highlite}$i {1} {#error}!', $target);
                    return;
                }
                $map->file_name = $target;
                $map->save();
                $this->announce($this->byTight($player) . 'permanently adds current track: {#highlite}{1}', $plain);
            });
        });
    }
}
