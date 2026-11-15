<?php
namespace Funseco\Admin\Commands;

use Funseco\Admin\BannedIps;
use Funseco\Core\Models\Player;

/** /admin cleanbanlist|cleaniplist|cleanblacklist|cleanguestlist. */
class CleanListCommand extends AdminCommand
{
    protected string $name;
    protected string $description;

    private const KINDS = [
        'banlist'   => ['CleanBanList', 'ban list'],
        'iplist'    => [null, 'banned IPs list'],
        'blacklist' => ['CleanBlackList', 'black list'],
        'guestlist' => ['CleanGuestList', 'guest list'],
    ];

    public function __construct(private string $kind)
    {
        $this->name = 'admin clean' . $kind;
        $this->description = 'Cleans current ' . self::KINDS[$kind][1];
    }

    /** @return self[] */
    public static function all(): array
    {
        return array_map(fn ($kind) => new self($kind), array_keys(self::KINDS));
    }

    public function handle(Player $player, array $args): void
    {
        [$method, $label] = self::KINDS[$this->kind];
        if ($method === null) {
            BannedIps::clear();
        } else {
            \Server::query()->{$method}();
        }
        $this->tell($player, '{#server}> {#admin}Cleaned ' . $label . '!');
    }
}
