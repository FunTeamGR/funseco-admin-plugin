<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Core\SharedKernel\Commands\Guarded;
use Funseco\Core\Services\Defaults\NullLastTrackList;
use Funseco\Core\SharedKernel\Enums\Role;
use Funseco\Core\SharedKernel\LastTrackList;

/**
 * Base for /admin commands. Staff only (operator and up), and guarded by the adminops ability
 * named after the command: 'admin kick' needs ability 'kick' (override $ability if it differs).
 *
 * Also shared helpers: XAseco message formatting ({1}, {2} placeholders, {#colour} tokens),
 * the admin's chat title, and login-or-id target lookup.
 */
abstract class AdminCommand implements Command, Guarded, Described
{
    use DeclaresCommand;

    protected ?Role $role = Role::Operator;

    abstract public function handle(Player $player, array $args): void;

    public function ability(): ?string
    {
        return $this->ability ?? preg_replace('/^admin /', '', $this->name);
    }

    /** Chat title shown before an admin's nickname (adminops titles). */
    protected function title(Player $player): string
    {
        return self::roleTitle($player->effectiveRole());
    }

    public static function roleTitle(Role $role): string
    {
        return config("adminops.titles.{$role->value}", ucfirst($role->value));
    }

    /** XAseco formatText(): replaces {1}, {2}, ... with the given values. */
    protected function text(string $template, string|int ...$values): string
    {
        foreach (array_values($values) as $i => $value) {
            $template = str_replace('{' . ($i + 1) . '}', (string) $value, $template);
        }

        return $template;
    }

    /** Message to everyone. */
    protected function announce(string $template, string|int ...$values): void
    {
        Chat::sendMessage($this->text($template, ...$values));
    }

    /** Message to one player. */
    protected function tell(Player $player, string $template, string|int ...$values): void
    {
        Chat::sendMessageToLogin($player->login, $this->text($template, ...$values));
    }

    /** Nickname without $w (wide) codes, as XAseco shows it in admin messages. */
    protected function nick(Player $player): string
    {
        return str_ireplace('$w', '', $player->nickname ?: $player->login);
    }

    /** Arguments joined back into one string (messages, server names, passwords). */
    protected function rest(array $args): string
    {
        return trim(implode(' ', $args));
    }

    /**
     * XAseco getPlayerParam(): a login, or the number shown in /players
     * (CurrentPlayers::indexed()). With $offline, a known (database) login also matches.
     * Tells the admin and returns null when nothing matches.
     */
    protected function target(Player $admin, string $arg, bool $offline = false): ?Player
    {
        if (ctype_digit($arg) && (int) $arg < 300) {
            $target = CurrentPlayers::findByLoginOrIndex($arg)
                ?? ($offline ? Player::where('login', $arg)->first() : null);
            if (!$target) {
                $this->tell($admin, '{#server}> {#error}Player_ID not found! Type {#highlite}$i/players {#error}to see all players.');
            }
            return $target;
        }

        $target = CurrentPlayers::get($arg)
            ?? ($offline ? Player::where('login', $arg)->first() : null);

        if (!$target) {
            $this->tell($admin, '{#server}> {#highlite}' . $arg . ' {#error}is not a valid player! Use {#highlite}$i/players {#error}to find the correct login or Player_ID.');
        }

        return $target;
    }

    /** Server answered true / a value. A fault answers with empty params. */
    protected static function ok(mixed $result): bool
    {
        return !empty($result[0]);
    }

    /** Admin announcement prefix: '{#server}>> {#admin}Title$z$s {#highlite}Nick$z$s{#admin} '. */
    protected function by(Player $admin, string $prefix = '>>'): string
    {
        return $this->text('{#server}' . $prefix . ' {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} ', $this->title($admin), $this->nick($admin));
    }

    /** Like by(), for the messages XAseco words '...$z$s {#admin}verb' (erase, addlocal, addthis, delrec). */
    protected function byTight(Player $admin): string
    {
        return $this->text('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s {#admin}', $this->title($admin), $this->nick($admin));
    }

    /** XAseco stripColors(): drop $-formatting codes, keep literal '$$' as '$'. */
    protected static function stripColors(string $text): string
    {
        $text = str_replace('$$', "\0", $text);
        $text = preg_replace('/\$(?:[0-9a-f][^$]{0,2}|[lh]\[[^\]]*\]|[^$]?)/i', '', $text);

        return str_replace("\0", '$', $text);
    }

    /**
     * Track number <n> as the admin was last shown it (/list, /admin listdupes); with no jukebox
     * plugin (null LastTrackList) it is the position in the server's challenge list. Calls $then
     * with that track's struct (FileName, Name, UId), or tells the admin why there is none.
     */
    protected function withTrack(Player $admin, string $arg, callable $then): void
    {
        if (!ctype_digit($arg) || (int) $arg < 1) {
            $this->tell($admin, '{#server}> {#emotic}You have to include a Track_ID, e.g {#highlite}/jukebox 12{#emotic} - Type {#highlite}/jukebox help{#emotic} for more info.');
            return;
        }

        $lists = app(LastTrackList::class);
        if (!$lists instanceof NullLastTrackList) {
            if ($lists->count($admin) === 0) {
                $this->tell($admin, '{#server}> {#emotic}Use {#highlite}/list{#emotic} first - Type {#highlite}/list help{#emotic} for more info.');
                return;
            }
            $entry = $lists->at($admin, (int) $arg);
            if (!$entry) {
                $this->tell($admin, '{#server}> {#emotic}Track_ID not found - Type {#highlite}/list{#emotic} to see all tracks.');
                return;
            }
            $then(['FileName' => $entry['file'], 'Name' => $entry['name'], 'UId' => $entry['uid']]);
            return;
        }

        \Server::query()->GetChallengeList(5000, 0, function ($result) use ($admin, $arg, $then) {
            $track = ($result[0] ?? [])[(int) $arg - 1] ?? null;
            if (!$track) {
                $this->tell($admin, '{#server}> {#emotic}Track_ID not found - Type {#highlite}/list{#emotic} to see all tracks.');
                return;
            }
            $then($track);
        });
    }

    /**
     * Run server queries one after the other and call $done with their first results by key.
     * A failed query gives null.
     *
     * @param array<string, array{0: string, 1?: array}> $calls key => [method, args]
     */
    protected function gather(array $calls, callable $done, array $results = []): void
    {
        if (!$calls) {
            $done($results);
            return;
        }

        $key = array_key_first($calls);
        [$method, $args] = $calls[$key] + [1 => []];
        unset($calls[$key]);

        \Server::query()->{$method}(...[...$args, function ($result) use ($calls, $done, $results, $key) {
            $results[$key] = $result[0] ?? null;
            $this->gather($calls, $done, $results);
        }]);
    }

    /** 'on'/'off' (any case) as a bool, anything else null. */
    protected static function onOff(?string $arg): ?bool
    {
        return match (strtolower((string) $arg)) {
            'on'    => true,
            'off'   => false,
            default => null,
        };
    }

    protected static function enabled(bool $on): string
    {
        return $on ? 'Enabled' : 'Disabled';
    }

    /** XAseco's "Unknown admin command or missing parameter(s)" answer. */
    protected function unknown(Player $admin, array $args): void
    {
        $this->tell($admin, '{#server}> {#error}Unknown admin command or missing parameter(s): {#highlite}$i ' . preg_replace('/^admin /', '', $this->name) . ' ' . $this->rest($args));
    }

    protected function noMessage(Player $admin): void
    {
        $this->tell($admin, '{#server}> {#error}No message!');
    }
}
