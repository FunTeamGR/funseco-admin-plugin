<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Servers\TmForever\MatchSettings;

class WriteTrackListCommand extends AdminCommand
{
    protected string $name = 'admin writetracklist';
    protected string $description = 'Saves current track list (def: tracklist.txt)';
    protected string $usage = '[file]';

    public function handle(Player $player, array $args): void
    {
        $filename = self::filename($args[0] ?? '');

        MatchSettings::save($filename, (bool) config('admin.writetracklist_random'))->then(
            fn () => $this->tell($player, '{#server}> {#highlite}' . $filename . '{#admin} written'),
            fn () => $this->tell($player, '{#server}> {#error}Error writing {#highlite}$i ' . $filename . ' {#error}!'),
        );
    }

    /** XAseco: default config tracklist, '.txt' appended when missing. */
    public static function filename(string $arg): string
    {
        $filename = $arg !== '' ? $arg : config('admin.default_tracklist', 'tracklist.txt');

        return stristr($filename, '.txt') ? $filename : $filename . '.txt';
    }
}
