<?php
/*
* This file is part of EC-CUBE
*
* Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
* http://www.lockon.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\GoogleAnalyticsSimpleSetup\ServiceProvider;

use Eccube\Application;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class GoogleAnalyticsSimpleSetupServiceProvider implements ServiceProviderInterface
{
    public function register(BaseApplication $app)
    {
        // ルーティング
        $app->match('/' . $app["config"]["admin_route"] . '/plugin/google_analytics_ss/config', '\Plugin\GoogleAnalyticsSimpleSetup\Controller\ConfigController::index')->bind('plugin_GoogleAnalyticsSimpleSetup_config');
        $app->match('/block/gas_google_analytics', '\Plugin\GoogleAnalyticsSimpleSetup\Controller\Block\GoogleAnalyticsController::index')->bind('block_gas_google_analytics');

        // Repositoy
        $app['eccube.plugin.repository.google_analytics_ss'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\GoogleAnalyticsSimpleSetup\Entity\GoogleAnalyticsSs');
        });

        // FormTypeの定義
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new \Plugin\GoogleAnalyticsSimpleSetup\Form\Type\Admin\ConfigType($app['config']);
            return $types;
        }));
   }

    public function boot(BaseApplication $app)
    {
    }
}