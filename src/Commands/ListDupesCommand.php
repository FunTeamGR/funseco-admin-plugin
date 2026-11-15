<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\LastTrackList;
use Funseco\Core\SharedKernel\UI\Window;

/** /admin listdupes: tracks whose UID is on the server more than once; becomes the admin's last shown list. */
class ListDupesCommand extends AdminCommand
{
    protected string $name = 'admin listdupes';
    protected string $description = 'Displays list of duplicate tracks';

    public function handle(Player $player, array $args): void
    {
        \Server::query()->GetChallengeList(5000, 0, function ($result) use ($player) {
            $seen = [];
            $dupes = [];
            foreach ($result[0] ?? [] as $track) {
                if (isset($seen[$track['UId']])) {
                    $dupes[] = ['uid' => $track['UId'], 'file' => $track['FileName'], 'name' => $track['Name']];
                }
                $seen[$track['UId']] = true;
            }

            if (!$dupes) {
                $this->tell($player, '{#server}> {#error}No duplicate track(s) found!');
                return;
            }

            app(LastTrackList::class)->show($player, $dupes);

            $rows = [];
            foreach ($dupes as $i => $dupe) {
                $rows[] = [str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT) . '.', '{#black}' . self::stripColors($dupe['name'])];
            }
            Window::show($player, 'Duplicate Tracks On This Server:', $rows, [0.95, 0.15, 0.8], ['Icons128x128_1', 'Challenge'], columns: ['Id', 'Name']);
        });
    }
}
