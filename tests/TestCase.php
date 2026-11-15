<?php

namespace Funseco\Admin\Tests;

use Funseco\Core\Testing\TestCase as CoreTestCase;
use Funseco\Admin\AdminServiceProvider;

abstract class TestCase extends CoreTestCase
{
    /** Dependencies first, then this plugin (isolated runs load only these). */
    protected function plugins(): array
    {
        return [
            AdminServiceProvider::class,
        ];
    }
}
