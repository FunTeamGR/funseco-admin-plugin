<?php
namespace Funseco\Admin\Tests\Support;

/** Staff ability tables for isolated runs, for any plugin's tests that run guarded admin commands. */
final class AbilitySnapshot
{
    /** The ability tables are the deployment's config/adminops.php; a bare (isolated) app has none. */
    public static function apply(): void
    {
        if (! config('adminops.admin_abilities')) {
            config(['adminops' => array_merge(config('adminops'), require __DIR__.'/abilities.php')]);
        }
    }
}
