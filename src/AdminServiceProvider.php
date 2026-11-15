<?php
namespace Funseco\Admin;

use Funseco\Core\SharedKernel\PublishesPluginConfig;
use Illuminate\Support\Facades\Event;
use Funseco\Core\SharedKernel\Settings\Setting;
use Funseco\Core\SharedKernel\Settings\SettingsRegistry;
use Illuminate\Support\ServiceProvider;
use Funseco\Admin\Commands\ReplayMapCommand;
use Funseco\Admin\Commands\SetGameModeCommand;
use Funseco\Core\SharedKernel\Events\MapEnded;
use Funseco\Core\SharedKernel\Events\PlayerFinished;
use Funseco\Core\SharedKernel\Events\RoundStarted;
use Funseco\Core\SharedKernel\Events\MapStarted;
use Funseco\Core\Services\ManiaLink\ManiaLinkService;
use Funseco\Core\SharedKernel\Events\PlayerConnected;
use Funseco\Core\SharedKernel\Events\StartupEvent;

class AdminServiceProvider extends ServiceProvider
{
    use PublishesPluginConfig;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/admin.php', 'admin');
        // Template geometry; the app's config/panels.php may still override a whole 'admin' subtree.
        $this->mergeConfigFrom(__DIR__ . '/../config/panels.php', 'panels.admin');
        $this->publishPluginConfig(__DIR__.'/..');
        // Read lazily through config() at use time: live.
        $this->app->make(SettingsRegistry::class)->declare('admin', 'admin', [
            Setting::enum('forceplay.level', '/forceplay minimum role', ['operator', 'admin', 'masteradmin']),
            Setting::string('forceplay.result', '/forceplay announcement'),
            Setting::bool('writetracklist_random', 'Shuffle track list when writing it'),
            Setting::string('wall_colour', '/admin wall message colour'),
            Setting::string('lock_password', '/admin unlock password', secret: true),
            Setting::string('warning', '/admin warn text ({br} = line break)'),
        ]);
    }

    public function boot(): void
    {
        foreach (AdminCommands::all() as $command) {
            \Chat::register($command);
        }

        // The menu's status lights
        $lights = app(\Funseco\Core\SharedKernel\MenuIndicators::class);
        $lights->register('admin.autoscorepanel', fn () => GamePanels::scorePanel());
        $lights->register('admin.roundsfinish', fn () => GamePanels::roundsFinish());
        $lights->register('core.debug', fn () => (bool) config('funseco.debug', false));

        // A queued replay is used up once the next map starts.
        Event::listen(MapStarted::class, [ReplayMapCommand::class, 'mapStarted']);
        Event::listen(PlayerConnected::class, BannedIps::class);
        // The /admin unlock only lasts for the session.
        Event::listen(\Funseco\Core\SharedKernel\Events\PlayerDisconnected::class,
            function ($e): void {
                state()->forget('admin:unlocked:'.$e->player->login);   // no return: false would stop the other listeners
            });
        Event::listen(MapStarted::class, fn () => SetGameModeCommand::clearPending());

        // /admin scorepanel, /admin roundsfinish
        $this->app->make(ManiaLinkService::class)->addAction(GamePanels::ID);
        Event::listen(MapStarted::class, [GamePanels::class, 'mapStarted']);
        Event::listen(MapEnded::class, [GamePanels::class, 'mapEnded']);
        Event::listen(RoundStarted::class, [GamePanels::class, 'roundStarted']);
        Event::listen(PlayerFinished::class, [GamePanels::class, 'playerFinished']);

        $maniaLinks = app(ManiaLinkService::class);
        $maniaLinks->addAction('admin_panel');
        $maniaLinks->addAction(PayConfirmation::WINDOW);
        $maniaLinks->addActions([PayConfirmation::YES, PayConfirmation::NO], PayConfirmation::class);
        $maniaLinks->addActions(AdminPanel::actions(), AdminPanel::class);
        $maniaLinks->addActions(PlayersWindowAction::actions(), PlayersWindowAction::class);
        Event::listen(PlayerConnected::class, fn (PlayerConnected $event) => AdminPanel::showTo($event->player));
        Event::listen(StartupEvent::class, fn () => AdminPanel::showToStaff());

        // /admin panel: the staff member's own template (or none)
        app(\Funseco\Core\Services\Looks\PlayerLooks::class)->registerPanels('admin', AdminPanel::templates());
        Event::listen(\Funseco\Core\SharedKernel\Events\PanelChanged::class, function (\Funseco\Core\SharedKernel\Events\PanelChanged $e) {
            if ($e->kind === 'admin') {
                AdminPanel::showTo($e->player);
            }
        });
    }
}
