<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\UI\Window;

/** /admin server: the dedicated server's detailed settings (not the player /server card). */
class ServerInfoCommand extends AdminCommand
{
    protected string $name = 'admin server';
    protected string $description = 'Displays server\'s detailed settings';

    private const PAGE = 19;
    private const MODES = ['Rounds', 'TimeAttack', 'Team', 'Laps', 'Stunts', 'Cup'];

    public function handle(Player $player, array $args): void
    {
        $this->gather([
            'version'  => ['GetVersion'],
            'system'   => ['GetSystemInfo'],
            'relay'    => ['IsRelayServer'],
            'main'     => ['GetMainServerPlayerInfo', [1]],
            'coppers'  => ['GetServerCoppers'],
            'pack'     => ['GetServerPackMask'],
            'ladder'   => ['GetLadderServerLimits'],
            'options'  => ['GetServerOptions', [1]],
            'game'     => ['GetCurrentGameInfo', [1]],
            'network'  => ['GetNetworkStats'],
            'ratios'   => ['GetCallVoteRatios'],
            'list'     => ['GetPlayerList', [255, 0, 2]],
        ], function (array $r) use ($player) {
            $this->gather(['detail' => ['GetDetailedPlayerInfo', [(string) ($r['main']['Login'] ?? '')]]],
                fn (array $d) => $this->show($player, $r + $d));
        });
    }

    private function show(Player $player, array $r): void
    {
        $pad = function (array $rows): array {
            while (count($rows) % self::PAGE !== 0) {
                $rows[] = [];
            }

            return $rows;
        };

        // page 1
        $rows = [['{#black}GetVersion:'], ...self::pairs((array) $r['version']), [], ['{#black}GetSystemInfo:'], ...self::pairs((array) $r['system']), []];
        $united = (int) ($r['detail']['OnlineRights'] ?? 0) === 3;
        $rows[] = ['Rights', $united ? 'United   $gCoppers: {#black}' . (int) $r['coppers'] : 'Nations'];
        $rows[] = ['Packmask', '{#black}' . (is_scalar($r['pack']) ? $r['pack'] : '')];
        if ($r['relay']) {
            $rows[] = ['Relays', '{#black}' . ($r['main']['Login'] ?? '')];
        } else {
            $relays = count(array_filter((array) $r['list'], fn ($p) => intdiv((int) ($p['Flags'] ?? 0), 100000) % 10 === 1 && ($p['Login'] ?? '') !== ($r['main']['Login'] ?? null)));
            $rows[] = ['Master to', '{#black}' . $relays . ' $grelay' . ($relays === 1 ? '' : 's')];
        }
        $rows[] = [];
        $rows[] = ['{#black}GetLadderServerLimits:'];
        array_push($rows, ...self::pairs((array) $r['ladder']));
        $rows = $pad($rows);

        // page 2: server options without name, comment and the "Next..." values
        $options = array_filter((array) $r['options'], fn ($v, $k) => !in_array($k, ['Name', 'Comment'], true) && !str_starts_with($k, 'Next'), ARRAY_FILTER_USE_BOTH);
        array_push($rows, ['{#black}GetServerOptions:'], ...self::pairs($options));
        $rows = $pad($rows);

        // page 3+: the section title repeats every 19 rows
        $game = (array) $r['game'];
        $section = array_map(function (array $row) use ($game) {
            return $row[0] === 'GameMode' ? ['GameMode', '{#black}' . $game['GameMode'] . '$g  (' . (self::MODES[$game['GameMode']] ?? '?') . ')'] : $row;
        }, self::pairs($game));
        foreach (array_chunk($section, self::PAGE - 1) as $chunk) {
            array_push($rows, ['{#black}GetCurrentGameInfo:'], ...$chunk);
        }
        $rows[] = [];
        array_push($rows, ['{#black}GetNetworkStats:'], ...self::pairs(array_filter((array) $r['network'], 'is_scalar')));
        $rows[] = [];
        array_push($rows, ['{#black}GetCallVoteRatios:'], ['Command', 'Ratio']);
        foreach ((array) $r['ratios'] as $ratio) {
            $rows[] = ['{#black}' . ($ratio['Command'] ?? ''), '{#black}' . round((float) ($ratio['Ratio'] ?? 0), 2)];
        }

        Window::show($player, 'System info for: ' . ($r['options']['Name'] ?? ''), $rows, [1.1, 0.6, 0.5],
            ['Icons64x64_1', 'DisplaySettings', 0.01], perPage: self::PAGE);
    }

    /** @return array<int, array{0: string, 1: string}> key/value rows of the scalar entries */
    private static function pairs(array $values): array
    {
        $rows = [];
        foreach ($values as $key => $value) {
            if (is_scalar($value)) {
                $rows[] = [(string) $key, '{#black}' . (is_bool($value) ? ($value ? 'True' : 'False') : $value)];
            }
        }

        return $rows;
    }
}
