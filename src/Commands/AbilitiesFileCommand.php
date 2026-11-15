<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Auth\Permissions;

/**
 * /admin writeabilities | readabilities. Staff and ability changes are stored in the database as
 * they happen, so "write" only confirms; "read" reloads the abilities from config + database.
 */
class AbilitiesFileCommand extends AdminCommand
{
    protected string $name;
    protected string $description;

    public function __construct(private bool $write)
    {
        $this->name = $write ? 'admin writeabilities' : 'admin readabilities';
        $this->description = ($write ? 'Saves' : 'Loads') . ' current abilities list (def: adminops.php)';
    }

    /** @return self[] */
    public static function all(): array
    {
        return [new self(true), new self(false)];
    }

    public function handle(Player $player, array $args): void
    {
        if (!$this->write) {
            app(Permissions::class)->reload();
        }

        $this->tell($player, '{#server}> {#highlite}adminops.php {#admin}' . ($this->write ? 'written' : 'read'));
    }
}
