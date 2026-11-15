<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Auth\Permissions;
use Funseco\Core\SharedKernel\Enums\Role;

/**
 * /admin adminability|opability <ability> [on|off]: show or change an ability of the tier.
 * Changes are stored in the ability_overrides table and win over config/adminops.php.
 */
class AbilityCommand extends AdminCommand
{
    protected string $name;
    protected string $description;
    protected string $usage = '<ability> [on|off]';

    public function __construct(private Role $tier)
    {
        $this->name = $tier === Role::Admin ? 'admin adminability' : 'admin opability';
        $this->description = 'Shows/changes ' . ($tier === Role::Admin ? 'admin' : 'operator') . ' ability {ON/OFF}';
    }

    /** @return self[] */
    public static function all(): array
    {
        return [new self(Role::Admin), new self(Role::Operator)];
    }

    public function handle(Player $player, array $args): void
    {
        $ability = strtolower($args[0] ?? '');
        if ($ability === '') {
            $this->tell($player, '{#server}> {#error}No ability specified - see {#highlite}$i /admin helpall{#error} and {#highlite}$i /admin listabilities{#error}!');
            return;
        }

        $permissions = app(Permissions::class);
        if (!$permissions->knows($this->tier, $ability)) {
            $this->tell($player, '{#server}> {#error}No ability {#highlite}$i {1}{#error} known!', $args[0]);
            return;
        }

        if (($on = self::onOff($args[1] ?? null)) !== null) {
            $permissions->setAbility($this->tier, $ability, $on);
        }

        $this->tell($player, '{#server}> {#admin}{1}$z$s {#admin}ability {#highlite}{2}{#admin} is: {#highlite}{3}',
            self::roleTitle($this->tier), $ability, $permissions->ability($this->tier, $ability) ? 'ON' : 'OFF');
    }
}
