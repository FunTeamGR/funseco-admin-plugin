<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentMap;

/**
 * /admin nextenv: skip to the next track in the same environment. A single-environment server
 * (TMNF: Stadium only) answers that the command needs TMU Forever.
 */
class NextEnvCommand extends AdminCommand
{
    protected string $name = 'admin nextenv';
    protected string $description = 'Loads next track in same environment';

    public function handle(Player $player, array $args): void
    {
        \Server::query()->GetChallengeList(5000, 0, function ($result) use ($player) {
            $tracks = array_values($result[0] ?? []);
            $envs = array_unique(array_column($tracks, 'Environnement'));
            if (count($envs) < 2) {
                $this->tell($player, '{#server}> {#error}Command only available on TMU Forever!');
                return;
            }

            $map = CurrentMap::get();
            $current = $map ? array_search($map->uid, array_column($tracks, 'UId'), true) : false;
            $env = $current !== false ? $tracks[$current]['Environnement'] : ($map->environment ?? '');
            $count = count($tracks);
            for ($i = 1; $i <= $count; $i++) {
                $next = $tracks[(($current === false ? -1 : $current) + $i) % $count];
                if ($next['Environnement'] === $env) {
                    \Server::query()->ChooseNextChallenge($next['FileName'], function () use ($player, $env) {
                        \Server::query()->NextChallenge();
                        $this->announce($this->by($player) . 'skips to next {#highlite}{1}{#admin} challenge!', $env);
                    });
                    return;
                }
            }
        });
    }
}
