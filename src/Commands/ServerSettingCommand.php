<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;

/**
 * Server setting commands (setservername, setcomment, setpwd, setspecpwd, setmaxplayers,
 * setmaxspecs). Subclasses declare the server method and XAseco's messages.
 */
abstract class ServerSettingCommand extends AdminCommand
{
    protected const METHOD = '';
    /** '>>' = message to everyone, '>' = message to the admin only (as XAseco). */
    protected const PREFIX = '>>';
    /** After the admin's name; {1} = the new value. */
    protected const MESSAGE = '';
    /** Message when the value is empty (passwords only); null = command ignored. */
    protected const EMPTY_MESSAGE = null;

    public function handle(Player $player, array $args): void
    {
        $value = $this->value($args);
        if ($value === null) {
            return; // XAseco falls through to "unknown command" for invalid values
        }
        if ($value === '' && static::EMPTY_MESSAGE === null) {
            return;
        }

        \Server::query()->{static::METHOD}($value);

        $message = $this->by($player, static::PREFIX) . ($value === '' ? static::EMPTY_MESSAGE : static::MESSAGE);
        if (static::PREFIX === '>>') {
            $this->announce($message, $value);
        } else {
            $this->tell($player, $message, $value);
        }
    }

    /** The value to send, or null when invalid. */
    protected function value(array $args): string|int|null
    {
        return $this->rest($args);
    }
}
