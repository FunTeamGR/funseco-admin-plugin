<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\ServerInfo;

class ReadTrackListCommand extends AdminCommand
{
    protected string $name = 'admin readtracklist';
    protected string $description = 'Loads current track list (def: tracklist.txt)';
    protected string $usage = '[file]';

    public function handle(Player $player, array $args): void
    {
        $filename = WriteTrackListCommand::filename($args[0] ?? '');

        if (!file_exists(rtrim((string) ServerInfo::getTracksDirectory(), '/') . '/MatchSettings/' . $filename)) {
            $this->tell($player, '{#server}> {#error}Cannot find {#highlite}$i ' . $filename . ' {#error}!');
            return;
        }

        \Server::query()->LoadMatchSettings('MatchSettings/' . $filename, function ($result) use ($player, $filename) {
            if (!array_key_exists(0, $result ?? [])) {
                $this->tell($player, '{#server}> {#error}Error reading {#highlite}$i ' . $filename . ' {#error}!');
                return;
            }
            $count = (int) $result[0];
            $this->tell($player, '{#server}> {#highlite}' . $filename . '{#admin} read with {#highlite}' . $count . '{#admin} track' . ($count == 1 ? '' : 's'));
        });
    }
}
