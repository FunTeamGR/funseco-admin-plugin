<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Auth\Permissions;
use Funseco\Core\SharedKernel\Enums\Role;

/** /admin listmasters|listadmins|listops: staff from config/adminops.php and the players.role column. */
class StaffListCommand extends LoginListCommand
{
    protected string $name;
    protected string $description;

    public function __construct(private Role $tier)
    {
        $word = match ($tier) {
            Role::MasterAdmin => 'masters',
            Role::Admin       => 'admins',
            default           => 'ops',
        };
        $this->name = 'admin list' . $word;
        $this->description = 'Displays current ' . match ($tier) {
            Role::MasterAdmin => 'masteradmin',
            Role::Admin       => 'admin',
            default           => 'operator',
        } . ' list';
    }

    /** @return self[] */
    public static function all(): array
    {
        return [new self(Role::MasterAdmin), new self(Role::Admin), new self(Role::Operator)];
    }

    public function handle(Player $player, array $args): void
    {
        $logins = app(Permissions::class)->staff($this->tier);
        if ($this->tier === Role::MasterAdmin) {
            // LAN logins (..._a.b.c.d_port or .../a.b.c.d:port) are no real accounts
            $logins = array_filter($logins, fn ($l) => !preg_match('/(_|\/)\d+\.\d+\.\d+\.\d+(_|:)\d+$/', $l));
        }
        sort($logins);

        [$header, $empty] = match ($this->tier) {
            Role::MasterAdmin => ['Current MasterAdmins:', 'No masteradmin(s) found!'],
            Role::Admin       => ['Current Admins:', 'No admin(s) found!'],
            default           => ['Current Operators:', 'No operator(s) found!'],
        };

        $this->showLogins($player, array_values($logins), $header, $empty, ['Icons128x128_1', 'Solo']);
    }
}
