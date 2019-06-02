<?php

/*
 * This file is part of the AdminLoginAlert
 *
 * Copyright (C) 2018 refine
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\AdminLoginAlert\ServiceProvider;

use Plugin\AdminLoginAlert\Form\Type\AdminLoginAlertConfigType;
use Plugin\AdminLoginAlert\SecurityEventListener;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

class AdminLoginAlertServiceProvider implements ServiceProviderInterface
{

    public function register(BaseApplication $app)
    {
        // プラグイン用設定画面
        $app->match('/'.$app['config']['admin_route'].'/plugin/AdminLoginAlert/config', 'Plugin\AdminLoginAlert\Controller\ConfigController::index')->bind('plugin_AdminLoginAlert_config');

        // Form
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new AdminLoginAlertConfigType();
            return $types;
        }));

        // Repository
        $app['plugin.admin_login_alert.repository.admin_login_alert_config'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\AdminLoginAlert\Entity\AdminLoginAlertConfig');
        });

        // Service
        $app['plugin.admin_login_alert.service.mail'] = $app->share(function () use ($app) {
            return new \Plugin\AdminLoginAlert\Service\MailService($app);
        });

        $SecurityEventListener = new SecurityEventListener($app);
        $app['dispatcher']->addListener(\Symfony\Component\Security\Http\SecurityEvents::INTERACTIVE_LOGIN, array($SecurityEventListener, 'onInteractiveLogin'));
    }

    public function boot(BaseApplication $app)
    {
    }

}
