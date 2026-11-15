<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

/** /admin remove <track #> — number in the server's challenge list (see withTrack()). */
class RemoveMapCommand extends AdminCommand
{
    protected string $name = 'admin remove';
    protected string $description = 'Removes a track from rotation';
    protected string $usage = '<track #>';

    public function handle(Player $player, array $args): void
    {
        $this->withTrack($player, $args[0] ?? '', function (array $track) use ($player) {
            \Server::query()->RemoveChallenge($track['FileName'], function ($result) use ($player, $track) {
                if (!self::ok($result)) {
                    $this->tell($player, '{#server}> {#error}Error removing track {#highlite}$i {1} {#error}!', $track['FileName']);
                    return;
                }
                $this->announce($this->text('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s {#admin}removes track: {#highlite}{3}',
                    $this->title($player), $this->nick($player), self::stripColors($track['Name'])));
            });
        });
    }
}
