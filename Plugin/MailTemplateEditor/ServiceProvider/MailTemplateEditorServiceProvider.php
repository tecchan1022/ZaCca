<?php
/*
  * This file is part of the MailTemplateEditor plugin
  *
  * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
  *
  * For the full copyright and license information, please view the LICENSE
  * file that was distributed with this source code.
  */

namespace Plugin\MailTemplateEditor\ServiceProvider;

use Eccube\Common\Constant;
use Plugin\MailTemplateEditor\Form\Type\MailTemplateType;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

class MailTemplateEditorServiceProvider implements ServiceProviderInterface
{
    public function register(BaseApplication $app)
    {

        // 管理画面定義
        $admin = $app['controllers_factory'];
        // 強制SSL
        if ($app['config']['force_ssl'] == Constant::ENABLED) {
            $admin->requireHttps();
        }

        $admin->match('/plugin/mailtemplateeditor/mail', 'Plugin\MailTemplateEditor\Controller\MailTemplateController::index')->bind('plugin_MailTemplateEditor_mail');
        $admin->match('/plugin/mailtemplateeditor/mail/{name}/edit', 'Plugin\MailTemplateEditor\Controller\MailTemplateController::edit')->bind('plugin_MailTemplateEditor_mail_edit');
        $admin->put('/plugin/mailtemplateeditor/mail/{name}/reedit', 'Plugin\MailTemplateEditor\Controller\MailTemplateController::reedit')->bind('plugin_MailTemplateEditor_mail_reedit');

        $app->mount('/'.trim($app['config']['admin_route'], '/').'/', $admin);

        // Form
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new MailTemplateType();

            return $types;
        }));

        // メッセージ登録
        $file = __DIR__.'/../Resource/locale/message.'.$app['locale'].'.yml';
        $app['translator']->addResource('yaml', $file, $app['locale']);

        // 管理画面メニュー追加
        $app['config'] = $app->share($app->extend('config', function ($config) {
            $config['nav'][3]['child'][] = array(
                'id' => 'mail',
                'name' => 'メール管理',
                'url' => 'plugin_MailTemplateEditor_mail',
            );

            return $config;
        }));
    }

    public function boot(BaseApplication $app)
    {
    }
}
