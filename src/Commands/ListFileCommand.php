<?php
namespace Funseco\Admin\Commands;

use Funseco\Admin\BannedIps;
use Funseco\Core\Models\Player;

/**
 * /admin write|read + iplist|blacklist|guestlist [file]. Black and guest lists live on the dedicated
 * server (SaveBlackList / LoadGuestList ...); the banned IPs are the controller's own list, so
 * "write" saves it now and "read" reloads it from storage.
 */
class ListFileCommand extends AdminCommand
{
    protected string $name;
    protected string $description;
    protected string $usage = '[file]';

    private const KINDS = [
        'iplist'    => ['Saves current banned IPs list', 'Loads current banned IPs list', null, null],
        'blacklist' => ['Saves current black list', 'Loads current black list', 'SaveBlackList', 'LoadBlackList'],
        'guestlist' => ['Saves current guest list', 'Loads current guest list', 'SaveGuestList', 'LoadGuestList'],
    ];

    public function __construct(private bool $write, private string $kind)
    {
        $this->name = 'admin ' . ($write ? 'write' : 'read') . $kind;
        [$save, $load] = self::KINDS[$kind];
        $this->description = ($write ? $save : $load) . ' (def: ' . $this->defaultFile() . ')';
    }

    /** @return self[] */
    public static function all(): array
    {
        $commands = [];
        foreach (array_keys(self::KINDS) as $kind) {
            $commands[] = new self(true, $kind);
            $commands[] = new self(false, $kind);
        }

        return $commands;
    }

    public function handle(Player $player, array $args): void
    {
        $file = ($args[0] ?? '') !== '' ? $args[0] : $this->defaultFile();
        $done = $this->write ? '{#admin}written' : '{#admin}read';
        $failed = $this->write
            ? '{#server}> {#error}Error writing {#highlite}$i {1} {#error}!'
            : '{#server}> {#highlite}{1} {#error}not found, or error ' . ($this->kind === 'guestlist' ? 'loading' : 'reading') . '!';

        $finish = fn (bool $ok) => $ok
            ? $this->tell($player, '{#server}> {#highlite}{1} ' . $done, $file)
            : $this->tell($player, $failed, $file);

        if ($this->kind === 'iplist') {
            $finish($this->write ? BannedIps::save() : is_file((string) config('admin.bannedips_file')));
            return;
        }

        $method = self::KINDS[$this->kind][$this->write ? 2 : 3];
        \Server::query()->{$method}($file, fn ($result) => $finish(self::ok($result)));
    }

    private function defaultFile(): string
    {
        return match ($this->kind) {
            'iplist'    => basename((string) config('admin.bannedips_file', 'bannedips.json')),
            'blacklist' => (string) config('admin.blacklist_file', 'blacklist.txt'),
            default     => (string) config('admin.guestlist_file', 'guestlist.txt'),
        };
    }
}
