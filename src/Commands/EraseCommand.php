<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\ServerInfo;

/** /admin erase <n>: like remove, and the track file is deleted too. */
class EraseCommand extends AdminCommand
{
    protected string $name = 'admin erase';
    protected string $description = 'Removes a track from rotation & deletes track file';
    protected string $usage = '<track #>';

    public function handle(Player $player, array $args): void
    {
        $this->withTrack($player, $args[0] ?? '', fn (array $track) => $this->erase($player, $track['FileName'], $track['Name'], false));
    }

    protected function erase(Player $player, string $file, string $name, bool $current): void
    {
        \Server::query()->RemoveChallenge($file, function ($result) use ($player, $file, $name, $current) {
            if (!self::ok($result)) {
                $this->tell($player, '{#server}> {#error}Error removing track {#highlite}$i {1} {#error}!', $file);
                return;
            }

            $plain = self::stripColors($name);
            $path = rtrim((string) ServerInfo::getTracksDirectory(), '/') . '/' . $file;
            $noun = $current ? 'current track' : 'track';
            $by = $this->byTight($player);

            if (!is_file($path)) {
                $this->announce($by . 'removes ' . $noun . ': {#highlite}{1}', $plain);
            } elseif (@unlink($path)) {
                $this->announce($by . 'erases ' . $noun . ': {#highlite}{1}', $plain);
            } else {
                $this->tell($player, '{#server}> {#error}Delete file {#highlite}$i {1}{#error} failed', $path);
                $this->announce($by . 'erase track failed: {#highlite}{1}', $plain);
            }
        });
    }
}
