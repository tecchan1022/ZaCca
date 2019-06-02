<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Eccube\Application;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class MypageEvent extends AbstractEvent
{
    /**
     * マイページ：Beforeイベント
     *
     * @param FilterResponseEvent $event
     */
    public function onRenderMypageBefore(FilterResponseEvent $event)
    {
        if(is_null($this->app['security']->getToken())){
             return;
        }

        if (!$this->app->isGranted('ROLE_USER')) {
            return;
        }

        $const = $this->app['config']['YamatoPayment']['const'];
        $subData = $this->app['yamato_payment.util.plugin']->getUserSettings();

        // オプションサービス契約済（0:契約済 1:未契約）
        // かつ、クレジットカード決済が有効
        if ($subData['use_option'] == '0' && in_array($const['YAMATO_PAYID_CREDIT'], $subData['enable_payment_type'])) {

            $request = $event->getRequest();
            $response = $event->getResponse();

            $html = $this->getHtmlMypage($request, $response);

            $response->setContent($html);
            $event->setResponse($response);
        }
    }

    /**
     * マイページに「カード編集用のリンク」を追加
     *
     * @param Request $request
     * @param Response $response
     * @return string 表示する HTML 情報
     */
    private function getHtmlMypage(Request $request, Response $response)
    {
        $crawler = new Crawler($response->getContent());
        $html = $crawler->html();

        // マイページ Noを取得
        $myPageNo = $request->get('mypageno');
        $insert = $this->app->renderView('YamatoPayment/Resource/template/default/mypage_navi_add.twig', array(
            'mypageno' => $myPageNo,
        ));

        try {
            $oldHtml = $crawler->filter("#navi_list")->html();
            $newHtml = $oldHtml . $insert;
            $html = str_replace($oldHtml, $newHtml, $html);
        } catch (\InvalidArgumentException $e) {

        }
        return html_entity_decode($html, ENT_NOQUOTES, 'UTF-8');
    }
}
