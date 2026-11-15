<?php
namespace Funseco\Admin\Tests\Feature;

use Funseco\Admin\Tests\AdminTestCase;
use Funseco\Core\Models\AbilityOverride;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Auth\Permissions;
use Funseco\Core\SharedKernel\Enums\Role;

/** Staff management and abilities: stored in players.role and the ability_overrides table. */
class StaffAbilitiesTest extends AdminTestCase
{
    private const MASTER_BY = '{#server}>> {#admin}God$z$s {#highlite}Master$z$s{#admin} ';
    private Player $master;

    protected function setUp(): void
    {
        parent::setUp();
        $this->master = $this->online('master', 'Master', 'player');
    }

    public function test_addadmin_persists_role_and_announces()
    {
        $joe = $this->online('joe', 'Joe');

        $this->chat('/admin addadmin joe', $this->master);

        $this->assertSame('admin', $joe->fresh()->role);
        $this->assertBroadcast(self::MASTER_BY . 'adds new Goddess$z$s{#admin}: {#highlite}Joe$z$s{#admin} !');
        $this->assertSame(Role::Admin, Player::where('login', 'joe')->first()->effectiveRole());

        $this->chat('/admin addadmin joe', $this->master);
        $this->assertToldTo('master', '{#server}> {#error}Login {#highlite}$i joe{#error} is already in Admin List!');
    }

    public function test_admin_cannot_addadmin_but_can_addop()
    {
        $joe = $this->online('joe', 'Joe');

        $this->chat('/admin addadmin joe');
        $this->assertDenied($this->admin);

        $this->chat('/admin addop joe');
        $this->assertSame('operator', $joe->fresh()->role);
        $this->assertBroadcast('{#server}>> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s{#admin} adds new $F93R$F85O$F67X$F49A$F29N$F09E$z$s{#admin}: {#highlite}Joe$z$s{#admin} !');
    }

    public function test_removeadmin_offline_and_config_staff_stays()
    {
        Player::create(['login' => 'gone', 'nickname' => 'Gone', 'role' => 'admin']);
        config(['adminops.admins' => ['fixed']]);
        Player::create(['login' => 'fixed', 'nickname' => 'Fixed', 'role' => 'player']);

        $this->chat('/admin removeadmin gone', $this->master);
        $this->assertSame('player', Player::where('login', 'gone')->value('role'));
        $this->assertBroadcast(self::MASTER_BY . 'removes Goddess$z$s{#admin}: {#highlite}Gone$z$s{#admin} !');

        $this->chat('/admin removeadmin gone', $this->master);
        $this->assertToldTo('master', '{#server}> {#error}Login {#highlite}$i gone{#error} is not in Admin List!');

        $this->chat('/admin removeadmin fixed', $this->master);
        $this->assertToldTo('master', '{#server}> {#error}Login {#highlite}$i fixed{#error} is set in the configuration and can\'t be removed here!');
        $this->assertContains('fixed', app(Permissions::class)->staff(Role::Admin));
    }

    public function test_removeop()
    {
        $op = $this->online('op', 'Op', 'operator');
        $this->chat('/admin removeop op');
        $this->assertSame('player', $op->fresh()->role);
        $this->assertBroadcast('{#server}>> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s{#admin} removes $F93R$F85O$F67X$F49A$F29N$F09E$z$s{#admin}: {#highlite}Op$z$s{#admin} !');
    }

    public function test_lists_merge_config_and_database_roles()
    {
        config(['adminops.masteradmins' => ['master', 'lan_1.2.3.4_2350'], 'adminops.admins' => ['cfgadmin'], 'adminops.operators' => []]);
        Player::create(['login' => 'cfgadmin', 'nickname' => 'Cfg', 'role' => 'player']);

        $this->chat('/admin listmasters');
        $window = collect($this->query->calls)->where(0, 'SendDisplayManialinkPageToLogin')->last();
        $this->assertStringContainsString('Current MasterAdmins:', $window[1][1]);
        $this->assertStringContainsString('master', $window[1][1]);
        $this->assertStringNotContainsString('1.2.3.4', $window[1][1], 'LAN logins are not listed');

        $this->query->calls = [];
        $this->chat('/admin listadmins');
        $window = $this->query->calls[0];
        $this->assertStringContainsString('Current Admins:', $window[1][1]);
        $this->assertStringContainsString('cfgadmin', $window[1][1]);
        $this->assertStringContainsString('boss', $window[1][1], 'DB role admin');

        $this->query->calls = [];
        $this->chat('/admin listops');
        $this->assertToldTo('boss', '{#server}> {#error}No operator(s) found!');
    }

    public function test_abilities_are_stored_and_win_over_config()
    {
        $this->assertFalse(config('adminops.admin_abilities.acdl'));
        $this->assertFalse($this->admin->allowed('acdl'));

        $this->chat('/admin adminability ACDL on', $this->master);
        $this->assertToldTo('master', '{#server}> {#admin}Goddess$z$s {#admin}ability {#highlite}acdl{#admin} is: {#highlite}ON');
        $this->assertSame(1, AbilityOverride::where(['role' => 'admin', 'ability' => 'acdl', 'allowed' => true])->count());
        $this->assertTrue($this->admin->allowed('acdl'));

        $this->chat('/admin adminability acdl', $this->master);
        $this->assertToldTo('master', '{#server}> {#admin}Goddess$z$s {#admin}ability {#highlite}acdl{#admin} is: {#highlite}ON');

        $this->chat('/admin opability warn off', $this->master);
        $this->assertToldTo('master', '{#server}> {#admin}' . '$F93R$F85O$F67X$F49A$F29N$F09E$z$s {#admin}ability {#highlite}warn{#admin} is: {#highlite}OFF');
        $this->assertFalse(app(Permissions::class)->ability(Role::Operator, 'warn'));
    }

    public function test_ability_errors()
    {
        $this->chat('/admin adminability', $this->master);
        $this->assertToldTo('master', '{#server}> {#error}No ability specified - see {#highlite}$i /admin helpall{#error} and {#highlite}$i /admin listabilities{#error}!');
        $this->chat('/admin opability nonsense on', $this->master);
        $this->assertToldTo('master', '{#server}> {#error}No ability {#highlite}$i nonsense{#error} known!');
        $this->assertSame(0, AbilityOverride::count());
    }

    public function test_readabilities_reloads_and_writeabilities_confirms()
    {
        $permissions = app(Permissions::class);
        $this->assertFalse($permissions->ability(Role::Admin, 'acdl'));
        AbilityOverride::create(['role' => 'admin', 'ability' => 'acdl', 'allowed' => true]); // changed behind its back

        $this->chat('/admin readabilities');
        $this->assertToldTo('boss', '{#server}> {#highlite}adminops.php {#admin}read');
        $this->assertTrue($permissions->ability(Role::Admin, 'acdl'));

        $this->chat('/admin writeabilities');
        $this->assertToldTo('boss', '{#server}> {#highlite}adminops.php {#admin}written');
    }

    public function test_listabilities_views()
    {
        // admin: ON special abilities only
        $this->chat('/admin listabilities');
        $window = $this->query->calls[0][1][1];
        $this->assertStringContainsString('Current Admin abilities:', $window);
        $this->assertStringContainsString('chat_pma', $window);
        $this->assertStringNotContainsString('chat_match', $window);
        $this->assertStringContainsString('/admin helpall', $window);

        // masteradmin: every entry; with argument the operator tier
        $this->query->calls = [];
        $this->chat('/admin listabilities', $this->master);
        $window = $this->query->calls[0][1][1];
        $this->assertStringContainsString('Current MasterAdmin abilities:', $window);
        $this->assertStringContainsString('chat_match', $window);

        $this->query->calls = [];
        $this->chat('/admin listabilities op', $this->master);
        $this->assertStringContainsString('Current Operator abilities:', $this->query->calls[0][1][1]);
        $this->assertStringNotContainsString('chat_pma', $this->query->calls[0][1][1]);

        $this->query->calls = [];
        $this->chat('/admin listabilities zzz', $this->master);
        $this->assertToldTo('master', '{#server}> {#highlite}zzz{#error} is not a valid administrator tier!');
    }
}
