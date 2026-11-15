<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

class AllowDownloadCommand extends AdminCommand
{
    protected string $name = 'admin acdl';
    protected string $description = 'Sets AllowChallengeDownload {ON/OFF}';
    protected string $usage = '[on|off]';

    public function handle(Player $player, array $args): void
    {
        if (($on = self::onOff($args[0] ?? null)) !== null) {
            \Server::query()->AllowChallengeDownload($on);
            $this->tell($player, '{#server}> {#admin}AllowChallengeDownload set to ' . self::enabled($on));
            return;
        }

        \Server::query()->IsChallengeDownloadAllowed(function ($result) use ($player) {
            $this->tell($player, '{#server}> {#admin}AllowChallengeDownload is currently ' . self::enabled((bool) ($result[0] ?? false)));
        });
    }
}
