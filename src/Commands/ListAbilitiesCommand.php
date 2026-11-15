<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Auth\Permissions;
use Funseco\Core\SharedKernel\Enums\Role;
use Funseco\Core\SharedKernel\UI\Window;

/** /admin listabilities [admin|operator]: the special (non-command) abilities in effect. */
class ListAbilitiesCommand extends AdminCommand
{
    protected string $name = 'admin listabilities';
    protected string $description = 'Displays current abilities list';
    protected string $usage = '[admin|operator]';

    private const SPECIAL = [
        'chat_pma'        => '{#black}/pma$g sends a PM to player & admins',
        'chat_bestworst'  => '{#black}/best$g & {#black}/worst$g accept login/Player_ID',
        'chat_statsip'    => '{#black}/stats$g includes IP address',
        'chat_summary'    => '{#black}/summary$g accepts login/Player_ID',
        'chat_jb_multi'   => '{#black}/jukebox$g adds more than one track',
        'chat_jb_recent'  => '{#black}/jukebox$g adds recently played track',
        'chat_add_tref'   => '{#black}/add trackref$g writes TMX trackref file',
        'chat_match'      => '{#black}/match$g allows match control',
        'chat_tc_listen'  => '{#black}/tc$g will copy team chat to admins',
        'chat_jfreu'      => 'use all {#black}/jfreu$g commands',
        'chat_musicadmin' => 'use {#black}/music$g admin commands',
        'noidlekick_play' => 'no idlekick when {#black}player$g',
        'noidlekick_spec' => 'no idlekick when {#black}spectator$g',
        'server_coppers'  => 'view coppers amount in {#black}/server$g',
    ];

    public function handle(Player $player, array $args): void
    {
        $role = $player->effectiveRole();
        $all = false; // masteradmin without an argument lists every entry, not only the ON ones
        if ($role === Role::MasterAdmin) {
            $arg = strtolower($args[0] ?? '');
            if ($arg === '') {
                $tier = Role::Admin;
                $all = true;
            } elseif (str_starts_with('admin', $arg)) {
                $tier = Role::Admin;
            } elseif (str_starts_with('operator', $arg)) {
                $tier = Role::Operator;
            } else {
                $this->tell($player, '{#server}> {#highlite}{1}{#error} is not a valid administrator tier!', $args[0]);
                return;
            }
        } else {
            $tier = $role === Role::Operator ? Role::Operator : Role::Admin;
        }

        $permissions = app(Permissions::class);
        $rows = [];
        foreach (self::SPECIAL as $ability => $text) {
            if ($all || $permissions->ability($tier, $ability)) {
                $rows[] = [$ability, $text];
            }
        }
        if ($rows) {
            $rows[] = [];
        }
        $rows[] = ['See {#black}/admin helpall$g for available /admin commands'];

        Window::show($player, 'Current ' . HelpCommand::logTitle($all ? 'masteradmin' : $tier->value) . ' abilities:', $rows,
            [1.0, 0.3, 0.7], ['Icons128x128_1', 'ProfileAdvanced', 0.02], perPage: 100);
    }
}
