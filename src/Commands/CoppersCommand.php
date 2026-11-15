<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

/** /admin coppers: the server account's coppers (read-only; United server accounts only). */
class CoppersCommand extends AdminCommand
{
    protected string $name = 'admin coppers';
    protected string $description = 'Shows server\'s coppers amount';

    public function handle(Player $player, array $args): void
    {
        $this->gather(['main' => ['GetMainServerPlayerInfo', [1]]], function (array $r) use ($player) {
            $login = (string) ($r['main']['Login'] ?? '');
            $this->gather([
                'detail'  => ['GetDetailedPlayerInfo', [$login]],
                'options' => ['GetServerOptions', [1]],
                'coppers' => ['GetServerCoppers'],
            ], function (array $r) use ($player) {
                if ((int) ($r['detail']['OnlineRights'] ?? 0) !== 3) {
                    $this->tell($player, '{#server}> {#error}This requires a TM United Forever server!');
                    return;
                }
                $this->tell($player, '{#server}> Server {#highlite}{1}$z$s {#server}owns {#highlite}{2} {#server}coppers!',
                    (string) ($r['options']['Name'] ?? ''), (int) ($r['coppers'] ?? 0));
            });
        });
    }
}
