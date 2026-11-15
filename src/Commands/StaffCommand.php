<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Auth\Permissions;
use Funseco\Core\SharedKernel\Enums\Role;

/**
 * /admin addadmin|removeadmin|addop|removeop <login/id>. Staff changes are stored in players.role;
 * logins listed in config/adminops.php stay staff and can't be removed here.
 */
class StaffCommand extends AdminCommand
{
    protected string $name;
    protected string $description;
    protected string $usage = '<login/id>';

    public function __construct(private Role $tier, private bool $add)
    {
        $word = $tier === Role::Admin ? 'admin' : 'op';
        $this->name = 'admin ' . ($add ? 'add' : 'remove') . $word;
        $this->description = ($add ? 'Adds a new ' : 'Removes an ') . ($tier === Role::Admin ? 'admin' : 'operator');
    }

    /** @return self[] */
    public static function all(): array
    {
        return [
            new self(Role::Admin, true), new self(Role::Admin, false),
            new self(Role::Operator, true), new self(Role::Operator, false),
        ];
    }

    public function handle(Player $player, array $args): void
    {
        $arg = $args[0] ?? '';
        if ($arg === '') {
            $this->unknown($player, $args);
            return;
        }

        $target = $this->target($player, $arg, offline: !$this->add);
        if (!$target) {
            return;
        }

        $permissions = app(Permissions::class);
        $list = $this->tier === Role::Admin ? 'Admin List' : 'Operator List';
        $title = self::roleTitle($this->tier);
        $nick = $this->nick($target);

        if ($this->add) {
            // anyone already at this tier or above counts as listed
            $listed = in_array($target->login, $permissions->staff($this->tier), true)
                || ($this->tier === Role::Operator && in_array($target->login, $permissions->staff(Role::Admin), true))
                || in_array($target->login, $permissions->staff(Role::MasterAdmin), true);
            if ($listed) {
                $this->tell($player, '{#server}> {#error}Login {#highlite}$i {1}{#error} is already in ' . $list . '!', $target->login);
                return;
            }
            $target->role = $this->tier->value;
            $target->save();
            $this->announce($this->by($player) . 'adds new {1}$z$s{#admin}: {#highlite}{2}$z$s{#admin} !', $title, $nick);
            return;
        }

        if (!in_array($target->login, $permissions->staff($this->tier), true)) {
            $this->tell($player, '{#server}> {#error}Login {#highlite}$i {1}{#error} is not in ' . $list . '!', $target->login);
            return;
        }
        if ($permissions->inConfig($target->login, $this->tier)) {
            $this->tell($player, '{#server}> {#error}Login {#highlite}$i {1}{#error} is set in the configuration and can\'t be removed here!', $target->login);
            return;
        }
        $target->role = Role::Player->value;
        $target->save();
        $this->announce($this->by($player) . 'removes {1}$z$s{#admin}: {#highlite}{2}$z$s{#admin} !', $title, $nick);
    }
}
