<?php

namespace LaveroAdmin\Admin\Reporter;

use Illuminate\Routing\Router;
use LaveroAdmin\Admin\Admin;

trait BootExtension
{
    public static function boot()
    {
        static::registerRoutes();

        static::importAssets();

        Admin::extend('reporter', __CLASS__);
    }

    /**
     * Register routes for LaveroAdmin.
     *
     * @return void
     */
    protected static function registerRoutes()
    {
        parent::routes(function ($router) {
            /* @var Router $router */
            $router->post('exceptions/clear-resolved', 'LaveroAdmin\Admin\Reporter\ExceptionController@clearResolved')->name('reporter.clear-resolved');
            $router->post('exceptions/{id}/status', 'LaveroAdmin\Admin\Reporter\ExceptionController@updateStatus')->name('reporter.update-status');
            $router->resource('exceptions', 'LaveroAdmin\Admin\Reporter\ExceptionController');
        });
    }

    /**
     * {@inheritdoc}
     */
    public static function import()
    {
        parent::createMenu('Exception Reporter', 'exceptions', 'icon-bug');

        parent::createPermission('Exceptions reporter', 'ext.reporter', 'exceptions*');
    }

    public static function importAssets()
    {
        Admin::js('/vendor/lavero-admin-reporter/prism/prism.js');
        Admin::css('/vendor/lavero-admin-reporter/prism/prism.css');
    }
}
