<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Events\ControllerShutdownRequested;

/** /admin shutdown (controller only, silently) and /admin shutdownall (dedicated server too). */
class ShutdownCommand extends AdminCommand
{
    protected string $name;
    protected string $description;

    public function __construct(private bool $all)
    {
        $this->name = $all ? 'admin shutdownall' : 'admin shutdown';
        $this->description = $all ? 'Shuts down Server & controller' : 'Shuts down controller';
    }

    /** @return self[] */
    public static function all(): array
    {
        return [new self(false), new self(true)];
    }

    public function handle(Player $player, array $args): void
    {
        if ($this->all) {
            $this->announce('{#server}>> {#error}$wShutting down server now!');
        }

        event(new ControllerShutdownRequested(stopServer: $this->all));
    }
}
