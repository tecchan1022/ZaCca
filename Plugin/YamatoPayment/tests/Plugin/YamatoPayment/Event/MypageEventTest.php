<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

class MypageEventTest extends AbstractEventTestCase
{
    function setUp()
    {
        parent::setUp();
    }

    function testIndex_追加メニューの表示確認()
    {
        $this->logIn();

        $crawler = $this->client->request(
            'GET',
            $this->app->path('mypage')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // サブメニューのソース取得
        $source = $crawler->filter('#navi_list')->html();

        // 追加メニューが表示されていること
        $this->assertRegExp('/カード情報編集/u', $source);
    }

    function testIndex_クレカが有効でないなら追加メニューが表示されない()
    {
        $this->logIn();

        // クレカを決済方法から除外する
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $userSettings['enable_payment_type'] = array(
            $this->const['YAMATO_PAYID_CVS'],
            $this->const['YAMATO_PAYID_DEFERRED'],
        );
        $this->app['yamato_payment.util.plugin']->subData['user_settings'] = $userSettings;

        $crawler = $this->client->request(
            'GET',
            $this->app->path('mypage')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // サブメニューのソース取得
        $source = $crawler->filter('#navi_list')->html();

        // 追加メニューが表示されていないこと
        $this->assertNotRegExp('/カード情報編集/u', $source);
    }

    function testIndexWithLogout_ログインしていないならログイン画面が表示される()
    {
        $crawler = $this->client->request(
            'GET',
            $this->app->path('mypage')
        );
//        $this->assertTrue($this->client->getResponse()->isRedirect());

        // ページタイトル確認
        $this->expected = 'ログイン';
        $this->actual = $crawler->filter('h1.page-heading')->text();
        $this->verify();

        // サブメニューは表示されていないこと
        $this->assertEmpty($crawler->filter('#navi_list'));
    }

}
