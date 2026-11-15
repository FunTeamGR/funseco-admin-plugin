<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\UI\Window;

/** /admin relays: the relay servers connected to this server, or the master this one relays. */
class RelaysCommand extends AdminCommand
{
    protected string $name = 'admin relays';
    protected string $description = 'Displays relays list or shows relay master';

    private const IS_SERVER = 100000;

    public function handle(Player $player, array $args): void
    {
        $this->gather([
            'relay' => ['IsRelayServer'],
            'main'  => ['GetMainServerPlayerInfo', [1]],
            'list'  => ['GetPlayerList', [255, 0, 2]],
        ], function (array $r) use ($player) {
            if ($r['relay']) {
                $this->tell($player, '{#server}> This server relays master server: {#highlite}{1}{#server} ({#highlite}{2}{#server})',
                    (string) ($r['main']['Login'] ?? ''), (string) ($r['main']['NickName'] ?? ''));
                return;
            }

            $relays = array_values(array_filter(
                (array) $r['list'],
                fn ($p) => intdiv((int) ($p['Flags'] ?? 0), self::IS_SERVER) % 10 === 1 && ($p['Login'] ?? '') !== ($r['main']['Login'] ?? null),
            ));
            if (!$relays) {
                $this->tell($player, '{#server}> {#error}No relay servers connected');
                return;
            }

            $rows = [['{#login}Login', '{#nick}Nick']];
            foreach ($relays as $relay) {
                $rows[] = ['{#black}' . $relay['Login'], '{#black}' . $relay['NickName']];
            }
            Window::show($player, 'Relay servers:', $rows, [1.0, 0.35, 0.65], ['BgRaceScore2', 'Spectator'], perPage: 100);
        });
    }
}
