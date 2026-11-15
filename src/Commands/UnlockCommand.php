<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

/**
 * /admin unlock <pwd>: checks the configured lock password (admin.lock_password). An empty
 * configured password is never accepted. The result is only remembered for the session
 * (UnlockCommand::unlocked()). While a password is configured, core refuses every other
 * "admin ..." command until the player has unlocked (Permissions::adminLocked()).
 */
class UnlockCommand extends AdminCommand
{
    protected string $name = 'admin unlock';
    protected string $description = 'Unlocks admin commands & features';
    protected string $usage = '<password>';

    public static function unlocked(Player $player): bool
    {
        return (bool) state()->get('admin:unlocked:' . $player->login, false);
    }

    public function handle(Player $player, array $args): void
    {
        $given = $this->rest($args);
        if ($given === '') {
            $this->unknown($player, $args);
            return;
        }

        $password = (string) config('admin.lock_password', '');
        if ($password !== '' && hash_equals($password, $given)) {
            state()->forever('admin:unlocked:' . $player->login, true);
            $this->tell($player, '{#server}> {#admin}Password accepted: admin commands unlocked!');
            return;
        }

        $this->tell($player, '{#server}> {#error}Invalid password!');
    }
}
