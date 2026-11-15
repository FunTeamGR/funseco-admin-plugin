<?php
namespace Funseco\Admin\Tests\Feature;

use Funseco\Admin\Tests\AdminTestCase;

class ServerSettingsTest extends AdminTestCase
{
    private const MASTER = '{#server}>> {#admin}God$z$s {#highlite}Master$z$s{#admin} ';

    private function master()
    {
        return $this->online('master', 'Master');
    }

    public function test_setservername_masteradmin_only()
    {
        $this->chat('/admin setservername My Server');
        $this->assertDenied($this->admin);

        $this->chat('/admin setservername $f00My  Server', $this->master());
        $this->assertCalled('SetServerName', ['$f00My Server']);
        $this->assertBroadcast(self::MASTER . 'sets servername to {#highlite}$f00My Server');
    }

    public function test_setcomment_tells_admin_only()
    {
        $this->chat('/admin setcomment hello all', $this->master());
        $this->assertCalled('SetServerComment', ['hello all']);
        $this->assertToldTo('master', '{#server}> {#admin}God$z$s {#highlite}Master$z$s{#admin} sets server comment to {#highlite}hello all');
        $this->assertSame([], $this->broadcasts());
    }

    public function test_setpwd_and_disable()
    {
        $this->chat('/admin setpwd secret');
        $this->assertCalled('SetServerPassword', ['secret']);
        $this->assertToldTo('boss', '{#server}> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s{#admin} sets player password to {#highlite}secret');

        $this->chat('/admin setpwd');
        $this->assertCalled('SetServerPassword', ['']);
        $this->assertToldTo('boss', '{#server}> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s{#admin} disables player password');
    }

    public function test_setspecpwd()
    {
        $this->chat('/admin setspecpwd spec');
        $this->assertCalled('SetServerPasswordForSpectator', ['spec']);
        $this->assertToldTo('boss', '{#server}> {#admin}Goddess$z$s {#highlite}$f00Boss$z$s{#admin} sets spectator password to {#highlite}spec');
    }

    public function test_setmaxplayers_and_specs()
    {
        $master = $this->master();
        $this->chat('/admin setmaxplayers 0', $master);
        $this->chat('/admin setmaxplayers abc', $master);
        $this->assertSame([], $this->serverCalls(), 'invalid numbers ignored');

        $this->chat('/admin setmaxplayers 24', $master);
        $this->assertCalled('SetMaxPlayers', [24]);
        $this->assertBroadcast(self::MASTER . 'sets new player maximum to {#highlite}24{#admin} !');

        $this->chat('/admin setmaxspecs 0', $master);
        $this->assertCalled('SetMaxSpectators', [0]);
        $this->assertBroadcast(self::MASTER . 'sets new spectator maximum to {#highlite}0{#admin} !');
    }

    public function test_admin_cannot_set_max_players()
    {
        $this->chat('/admin setmaxplayers 10');
        $this->assertDenied($this->admin);
    }

    public function test_every_registered_handler_exists()
    {
        foreach (\Funseco\Admin\AdminCommands::all() as $class) {
            $command = is_string($class) ? app($class) : $class;
            $this->assertInstanceOf(\Funseco\Core\SharedKernel\Commands\Guarded::class, $command, get_class($command) . ' is not guarded');
            if ($command instanceof \Funseco\Admin\Commands\ForcePlayCommand) {
                $this->assertNull($command->ability());   // role-only on purpose (admin.forceplay.level)
                continue;
            }
            $this->assertArrayHasKey($command->ability(), config('adminops.admin_abilities'), get_class($command) . ": ability '{$command->ability()}' missing in adminops");
        }
    }
}
