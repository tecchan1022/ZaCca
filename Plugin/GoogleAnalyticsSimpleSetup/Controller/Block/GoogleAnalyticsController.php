<?php
/*
 * Copyright(c) 2015 SystemFriend Inc. All rights reserved.
 * http://ec-cube.systemfriend.co.jp/
 */

namespace Plugin\GoogleAnalyticsSimpleSetup\Controller\Block;

use Eccube\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

/**
 * Controller to handle module setting screen
 */
class GoogleAnalyticsController
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

        $pConfig = Yaml::parse(__DIR__ . '/../../config.yml');
        /* @var $Plugin \Eccube\Entity\Plugin */
        $Plugin = $this->app['eccube.repository.plugin']->findOneBy(array('code' => $pConfig['code']));

        $enable_flg = true;
        $transaction_id = '';
        if (is_null($Plugin)) {
            $enable_flg = false;
        }else{
            $enable_flg = $Plugin->getEnable() == 1;
        }

        if ($enable_flg){
            $gaPlugin = $this->app['eccube.plugin.repository.google_analytics_ss']->findOneBy(array('pluginCode' => $Plugin->getCode()));

            if (!is_null($gaPlugin)) {
                $arrConfig = unserialize($gaPlugin->getConfigData());
                if ($arrConfig){
                    if (array_key_exists('transaction_id', $arrConfig)){
                        $transaction_id = $arrConfig['transaction_id'];
                    }
                }
            }
        }

        return $this->app['view']->render('Block/gas_google_analytics.twig',
                array(
                        'transaction_id' => $transaction_id,
                        'enable_flg' => $enable_flg,
                ));
    }

}
