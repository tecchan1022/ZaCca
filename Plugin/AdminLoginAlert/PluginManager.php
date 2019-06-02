<?php

/*
 * This file is part of the AdminLoginAlert
 *
 * Copyright (C) 2018 joolen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\AdminLoginAlert;

use Eccube\Application;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\AdminLoginAlert\Entity\AdminLoginAlertConfig;

class PluginManager extends AbstractPluginManager
{

    /**
     * プラグインインストール時の処理
     *
     * @param $config
     * @param Application $app
     * @throws \Exception
     */
    public function install($config, Application $app)
    {

    }

    /**
     * プラグイン削除時の処理
     *
     * @param $config
     * @param Application $app
     */
    public function uninstall($config, Application $app)
    {
        // マイグレーション実行
        $this->migrationSchema($app, __DIR__ . '/Resource/doctrine/migration', $config['code'], 0);
    }

    /**
     * プラグイン有効時の処理
     *
     * @param $config
     * @param Application $app
     * @throws \Exception
     */
    public function enable($config, Application $app)
    {
        //インストール
        $this->migrationSchema($app, __DIR__ . '/Resource/doctrine/migration', $config['code']);

        $AlertConfig = new AdminLoginAlertConfig();
        $AlertConfig->setId(1);
        $app['orm.em']->persist($AlertConfig);
        $app['orm.em']->flush();
    }

    /**
     * プラグイン無効時の処理
     *
     * @param $config
     * @param Application $app
     * @throws \Exception
     */
    public function disable($config, Application $app)
    {
    }

    /**
     * プラグイン更新時の処理
     *
     * @param $config
     * @param Application $app
     * @throws \Exception
     */
    public function update($config, Application $app)
    {
    }

}
