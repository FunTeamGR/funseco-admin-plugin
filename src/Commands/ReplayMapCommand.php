<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentMap;
use Funseco\Core\SharedKernel\Events\ReplayRequested;

/**
 * /admin replay — XAseco queues the current track at the front of its jukebox
 * (ReplayRequested). Without the jukebox plugin, ChooseNextChallenge(current file)
 * makes the server play it again after this one ends.
 */
class ReplayMapCommand extends AdminCommand
{
    protected string $name = 'admin replaymap';
    protected string $description = 'Replays current track (queued as next track)';
    protected array $aliases = ['admin replay'];

    public const STATE_KEY = 'admin.replayUid';

    public function handle(Player $player, array $args): void
    {
        $map = CurrentMap::get();
        if (!$map) {
            return;
        }

        // With the jukebox plugin, replays go through its queue (XAseco).
        $request = new ReplayRequested($player);
        event($request);
        if ($request->queued !== null) {
            $request->queued
                ? $this->announce($this->by($player) . 'queues challenge for replay!')
                : $this->tell($player, '{#server}> {#error}Track is already getting replayed!');
            return;
        }

        // No jukebox: let the server play it next.
        if (state()->get(self::STATE_KEY) === $map->uid) {
            $this->tell($player, '{#server}> {#error}Track is already getting replayed!');
            return;
        }

        \Server::query()->ChooseNextChallenge($map->file_name);
        state()->forever(self::STATE_KEY, $map->uid);

        $this->announce($this->by($player) . 'queues challenge for replay!');
    }

    /** MapStarted listener: a queued replay is used up once the next map starts. */
    public function mapStarted(): void
    {
        state()->forget(self::STATE_KEY);
    }
}
