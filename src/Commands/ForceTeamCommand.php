<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\ServerInfo;

/** /admin forceteam <login/id> [blue|red] (Team mode only). */
class ForceTeamCommand extends AdminCommand
{
    protected string $name = 'admin forceteam';
    protected string $description = 'Forces player into {Blue} or {Red} team';
    protected string $usage = '<login/id> [blue|red]';

    private const TEAMS = ['$00fBlue', '$f00Red'];
    private const TEAM_MODE = 2;

    public function handle(Player $player, array $args): void
    {
        if (($args[0] ?? '') === '') {
            $this->unknown($player, $args);
            return;
        }
        if (!($target = $this->target($player, $args[0]))) {
            return;
        }
        if (ServerInfo::getGameMode() !== self::TEAM_MODE) {
            $this->tell($player, '{#server}> {#error}Command only available in {#highlite}$i Team {#error}mode!');
            return;
        }

        $word = $args[1] ?? '';
        $team = match (true) {
            $word === '' => null,
            str_starts_with('blue', strtolower($word)) => 0,
            str_starts_with('red', strtolower($word)) => 1,
            default => false,
        };
        if ($team === false) {
            $this->tell($player, '{#server}> {#highlite}{1}$z$s{#error} is not a valid team!', $word);
            return;
        }

        \Server::query()->GetPlayerInfo($target->login, 1, function ($result) use ($player, $target, $team) {
            $current = (int) ($result[0]['TeamId'] ?? -1);
            $plain = self::stripColors((string) ($target->nickname ?: $target->login));

            if ($team === null) {
                $this->tell($player, '{#server}> {#admin}Player {#highlite}{1}{#admin} is in ' . (self::TEAMS[$current] ?? self::TEAMS[0]) . '{#admin} team', $plain);
            } elseif ($current === $team) {
                $this->tell($player, '{#server}> {#admin}Player {#highlite}{1}{#admin} is already in ' . self::TEAMS[$team] . '{#admin} team', $plain);
            } else {
                \Server::query()->ForcePlayerTeam($target->login, $team);
                $this->announce($this->by($player) . 'forces {#highlite}{1}$z$s{#admin} into ' . self::TEAMS[$team] . '{#admin} team!', $this->nick($target));
            }
        });
    }
}
