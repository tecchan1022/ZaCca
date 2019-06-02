<?php
/*
 * Copyright(c) 2015 SystemFriend Inc. All rights reserved.
 * http://ec-cube.systemfriend.co.jp/
 */

namespace Plugin\GoogleAnalyticsSimpleSetup\Controller;

use Eccube\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;
use Eccube\Util\Cache;

/**
 * Controller to handle module setting screen
 */
class ConfigController
{

    /**
     * Edit config
     *
     * @param Application $app
     * @param Request $request
     * @param type $id
     * @return type
     */
    public function index(Application $app, Request $request)
    {
        $this->app = $app;

        $pConfig = Yaml::parse(__DIR__ . '/../config.yml');
        $Plugin = $this->app['eccube.repository.plugin']->findOneBy(array('code' => $pConfig['code']));

        if (is_null($Plugin)) {
            $error = "例外エラー<br />プラグインが存在しません。";
            $error_title = 'エラー';
            return $this->app['view']->render('error.twig', compact('error', 'error_title'));
        }

        $gaPlugin = $this->app['eccube.plugin.repository.google_analytics_ss']->findOneBy(array('pluginCode' => $Plugin->getCode()));

        if (is_null($gaPlugin)) {
            $error = "例外エラー<br />プラグインが存在しません。";
            $error_title = 'エラー';
            return $this->app['view']->render('error.twig', compact('error', 'error_title'));
        }

        // データ取得
        $arrForm = unserialize($gaPlugin->getConfigData());
        if (empty($arrForm)) {
            $arrForm = array(
                'transaction_id' => null,
            );
        }

        // Typeの作成
        $form = $this->app['form.factory']->createBuilder('plugin_GoogleAnalyticsSimpleSetup_config', $arrForm)->getForm();

        if ('POST' === $this->app['request']->getMethod()) {
            $form->handleRequest($this->app['request']);
            if ($form->isValid()) {

                $formData = $form->getData();
                $gaPlugin->setConfigData(serialize($formData));
                $em = $app['orm.em'];
                $em->getConnection()->beginTransaction();
                $em->persist($gaPlugin);
                $em->flush();
                $em->getConnection()->commit();
                Cache::clear($app, false);
                $app->addSuccess('admin.register.complete', 'admin');

                return $app->redirect($app->url('plugin_GoogleAnalyticsSimpleSetup_config'));

            }
        }

        return $this->app['view']->render('GoogleAnalyticsSimpleSetup/Resource/template/Admin/analytics_ss_config.twig',
                array(
                        'form' => $form->createView(),
                        'tpl_subtitle' => 'Google Analytics 設定',
                ));


    }

}
