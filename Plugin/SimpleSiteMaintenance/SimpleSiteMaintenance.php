<?php
/*
 * Copyright(c) 2016 SYSTEM_KD
 */

namespace Plugin\SimpleSiteMaintenance;

use \Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;

class SimpleSiteMaintenance
{
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * フロント画面共通レスポンス
     * メンテ化のためレスポンスを後発で書き換え
     *
     * @param FilterResponseEvent $event
     */
    public function onEccubeEventFrontResponse(FilterResponseEvent $event)
    {

        $app = $this->app;

        $request = $event->getRequest();
        $response = $event->getResponse();


        // 3.0.9のバグに対応
        if (strpos($app['request']->getPathInfo(), '/'.trim($app['config']['admin_route'], '/')) === 0) {
            return;
        }


        /* @var $SsmConfig \Plugin\SimpleSiteMaintenance\Entity\SsmConfig */
        $SsmConfig = $app['ssm.repository.ssmconfig']->get();

        if(empty($SsmConfig)) {
            // 設定がない場合処理を行わない
            return;
        }

        // メンテナンス有効
        if($SsmConfig->isMenteMode()) {

            if(!$SsmConfig->isAdminCloseFlg()
                && $request->getSession()->has('_security_admin')) {

                // 管理者の場合はメンテにしない
                return;
            }

            // 指定のHTMLを表示
            $html = $SsmConfig->getPageHtml();

            $response->setContent($html);

            $event->setResponse($response);
        }

    }
}
