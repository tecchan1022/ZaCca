<?php
/*
 * Copyright(c) 2016 SYSTEM_KD
 */

namespace Plugin\SimpleSiteMaintenance\ServiceProvider;

use Eccube\Application;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

class SimpleSiteMaintenanceServiceProvider implements ServiceProviderInterface
{
    public function register(BaseApplication $app)
    {

        $app->match('/' . $app["config"]["admin_route"] . '/plugin/SimpleSiteMaintenance/config','\Plugin\SimpleSiteMaintenance\Controller\ConfigController::index')->bind('plugin_SimpleSiteMaintenance_config');

        // Repository
        $app['ssm.repository.ssmconfig'] = $app->share(function () use ($app) {
            $SsmConfig = $app['orm.em']->getRepository('Plugin\SimpleSiteMaintenance\\Entity\SsmConfig');
            $SsmConfig->setApplication($app);

            return $SsmConfig;
        });

        // Form
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new \Plugin\SimpleSiteMaintenance\Form\Type\PluginConfigType($app);
            return $types;
        }));
    }

    public function boot(BaseApplication $app)
    {
    }
}
