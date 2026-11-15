<?php

namespace Funseco\Admin\Tests;

use Funseco\Admin\AdminServiceProvider;
use Funseco\Admin\Tests\Support\AdminTestCase as BaseAdminTestCase;
use Funseco\Jukebox\JukeboxServiceProvider;
use Funseco\Rankings\RankingsServiceProvider;
use Funseco\Records\RecordsServiceProvider;
use Funseco\Votes\VotesServiceProvider;

/** Admin command test base for admin's own tests; adds the plugins its commands act on. */
abstract class AdminTestCase extends BaseAdminTestCase
{
    protected function plugins(): array
    {
        return [RankingsServiceProvider::class, RecordsServiceProvider::class, JukeboxServiceProvider::class, VotesServiceProvider::class, AdminServiceProvider::class];
    }
}
