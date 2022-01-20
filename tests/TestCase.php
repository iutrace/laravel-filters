<?php

namespace Tests;

use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTest;

class TestCase extends OrchestraTest
{
    protected function getApplicationProviders($app)
    {
        return [
            ValidationServiceProvider::class,
            TranslationServiceProvider::class,
            FilesystemServiceProvider::class,
        ];
    }
}
