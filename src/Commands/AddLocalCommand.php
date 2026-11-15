<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Map;
use Funseco\Core\Models\Player;
use Funseco\Core\Servers\TmForever\Values\ChallengeInfo;
use Funseco\Core\Services\Cache\ServerInfo;
use Funseco\Core\SharedKernel\Events\TrackAdded;

/** /admin addlocal <filename>: add a track file already in Challenges/Downloaded. */
class AddLocalCommand extends AdminCommand
{
    protected string $name = 'admin addlocal';
    protected string $description = 'Adds a local track (<filename>)';
    protected string $usage = '<filename>';

    private const FOLDER = 'Challenges/Downloaded/';
    private const MAX_BYTES = 256 * 1024;

    public function handle(Player $player, array $args): void
    {
        $name = $this->rest($args);
        if ($name === '') {
            $this->tell($player, '{#server}> {#error}You must include a local track filename!');
            return;
        }
        if (!str_ends_with(strtolower($name), '.challenge.gbx')) {
            $name .= '.Challenge.gbx';
        }
        $name = basename(str_replace('\\', '/', $name)); // no walking out of the folder

        $dir = rtrim((string) ServerInfo::getTracksDirectory(), '/') . '/' . self::FOLDER;
        $found = self::find($dir, $name);
        if ($found === null) {
            $this->tell($player, '{#server}> {#highlite}{1}{#error} not found!', self::FOLDER . $name);
            return;
        }

        $kb = (int) round(filesize($dir . $found) / 1024);
        if (filesize($dir . $found) >= self::MAX_BYTES) {
            $this->tell($player, '{#server}> {#error}Track is too large ({#highlite}{1}$i{#error} > 256 KB) - not added!', $kb);
            return;
        }

        $path = self::FOLDER . $found;
        \Server::query()->CheckChallengeForCurrentServerParams($path, function ($result, $fault = null) use ($player, $path, $found) {
            if ($fault !== null) {
                $this->tell($player, '{#server}> {#emotic}Track {#highlite}{1}{#emotic} ignored: {#error}{2}', $found, $fault);
                return;
            }
            \Server::query()->AddChallenge($path, function ($result, $fault = null) use ($player, $path, $found) {
                if ($fault !== null || !self::ok($result)) {
                    $this->tell($player, '{#server}> {#emotic}Track {#highlite}{1}{#emotic} ignored: {#error}{2}', $found, (string) $fault);
                    return;
                }
                $this->added($player, $path);
            });
        });
    }

    private function added(Player $player, string $path): void
    {
        \Server::query()->GetChallengeInfo($path, function ($result) use ($player, $path) {
            if (empty($result[0])) {
                $this->tell($player, '{#server}> {#error}Error getting info on track {#highlite}$i {1} {#error}!', $path);
                return;
            }
            $info = new ChallengeInfo($result[0]);
            $map = Map::fromChallengeInfo($info);
            if ($map->file_name !== $path) {
                $map->file_name = $path;
                $map->save();
            }

            $jukebox = (bool) config('jukebox.admin_add');
            $this->announce($this->byTight($player) . ($jukebox ? 'adds & jukeboxes track:' : 'adds track:') . ' {#highlite}{1}', self::stripColors($map->name));
            event(new TrackAdded($map, $player, 'local'));
        });
    }

    /** Case-insensitive file lookup in the folder; the real file name or null. */
    private static function find(string $dir, string $name): ?string
    {
        if (!is_dir($dir)) {
            return null;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if (strcasecmp($entry, $name) === 0 && is_file($dir . $entry)) {
                return $entry;
            }
        }

        return null;
    }
}
