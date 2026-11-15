<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Map;
use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Events\RecordsPruneRequested;

/** /admin prunerecs <n>: deletes all records and times of track #n in the admin's last track list. */
class PruneRecordsCommand extends AdminCommand
{
    protected string $name = 'admin prunerecs';
    protected string $description = 'Deletes records for specified track';
    protected string $usage = '<track #>';

    public function handle(Player $player, array $args): void
    {
        if (($args[0] ?? '') === '') {
            $this->unknown($player, $args);
            return;
        }

        $this->withTrack($player, $args[0], function (array $track) use ($player) {
            $plain = self::stripColors($track['Name']);
            $map = Map::where('uid', $track['UId'])->first();
            if (!$map) {
                $this->tell($player, '{#server}> {#error}Can\'t find ChallengeId for track: {#highlite}$i {1} / {2}', $plain, $track['UId']);
                return;
            }

            event(new RecordsPruneRequested($map));
            $this->tell($player, '{#server}> {#admin}Deleted all records & times for track: {#highlite}{1}', $plain);
        });
    }
}
