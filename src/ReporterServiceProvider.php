<?php

namespace LaveroAdmin\Admin\Reporter;

use Illuminate\Support\ServiceProvider;

class ReporterServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'lavero-admin-reporter');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'lavero-admin-reporter');

        if ($this->app->runningInConsole()) {
            $this->publishes(
                [__DIR__.'/../resources/assets/' => public_path('vendor/lavero-admin-reporter')],
                'lavero-admin-reporter'
            );

            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        Reporter::boot();
    }
}
