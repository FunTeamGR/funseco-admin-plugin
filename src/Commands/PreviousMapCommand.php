<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

/**
 * /admin prev — the track before the current one in the server's list (wrapping to the
 * last track), chosen as next, then skip to it. XAseco did the same through its jukebox.
 */
class PreviousMapCommand extends AdminCommand
{
    protected string $name = 'admin previous';
    protected string $description = 'Forces server to load previous track';
    protected array $aliases = ['admin prev'];

    public function handle(Player $player, array $args): void
    {
        \Server::query()->GetCurrentChallengeIndex(function ($result) use ($player) {
            $current = (int) ($result[0] ?? 0);

            $play = function ($list) use ($player) {
                $tracks = $list[0] ?? [];
                $track = end($tracks);
                if (!$track) {
                    return;
                }
                \Server::query()->ChooseNextChallenge($track['FileName']);
                \Server::query()->NextChallenge();
                $this->announce($this->by($player) . 'revisits previous challenge!');
            };

            if ($current > 0) {
                \Server::query()->GetChallengeList(1, $current - 1, $play);
            } else {
                \Server::query()->GetChallengeList(5000, 0, $play);
            }
        });
    }
}
