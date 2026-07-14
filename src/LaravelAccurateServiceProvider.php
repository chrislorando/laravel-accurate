<?php

namespace ChrisLorando\LaravelAccurate;

use ChrisLorando\LaravelAccurate\Commands\AccurateCommand;
use ChrisLorando\LaravelAccurate\Http\AccountClient;
use ChrisLorando\LaravelAccurate\OAuth\OAuthClient;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelAccurateServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-accurate')
            ->hasConfigFile()
            ->hasMigration('create_accurate_connections_table')
            ->hasMigration('create_accurate_databases_table')
            ->hasCommand(AccurateCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(
            LaravelAccurate::class,
            fn () => new LaravelAccurate(
                    $this->app->make(OAuthClient::class),
                    $this->app->make(AccountClient::class),
            )
        );
    }

    public function packageBooted(): void
    {
        $this->app->alias(LaravelAccurate::class, 'accurate');
    }
}
