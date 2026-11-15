<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentMap;

/**
 * /admin shuffle — XAseco reloads its autosave match settings with random order.
 * We shuffle server-side instead: remove every track but the current one, then add
 * them back in random order. Same result for players, no settings file needed.
 */
class ShuffleCommand extends AdminCommand
{
    protected string $name = 'admin shuffle';
    protected string $description = 'Randomizes current track list';
    protected array $aliases = ['admin shufflemaps'];

    public function handle(Player $player, array $args): void
    {
        $current = CurrentMap::get()?->file_name;

        \Server::query()->GetChallengeList(5000, 0, function ($result) use ($player, $current) {
            $files = array_values(array_filter(
                array_column($result[0] ?? [], 'FileName'),
                fn ($file) => $file !== $current,
            ));
            $count = count($files) + ($current ? 1 : 0);
            shuffle($files);

            if ($files) {
                \Server::query()->RemoveChallengeList($files);
                \Server::query()->AddChallengeList($files);
            }

            $this->announce($this->by($player) . 'shuffled track list with {#highlite}{1}{#admin} track{2}!', $count, $count == 1 ? '' : 's');
        });
    }
}
