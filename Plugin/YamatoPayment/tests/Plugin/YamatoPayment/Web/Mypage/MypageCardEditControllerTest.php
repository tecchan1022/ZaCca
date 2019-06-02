<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Web\Mypage;

use Eccube\Application;
use Eccube\Entity\Customer;
use Plugin\YamatoPayment\Controller\Mypage\MypageCardEditController;

class MypageCardEditControllerTest extends AbstractMypageWebTestCase
{
    protected $subData;

    /** @var  MypageCardEditController */
    var $object;

    public function setUp()
    {
        parent::setUp();

        // SubData用UserSettingを取得
        $this->subData = $this->app['yamato_payment.util.plugin']->getUserSettings();

        $this->object = new MypageCardEditController();
    }

    public function testIndex__預かりカード有()
    {
        // オプションサービスを契約済みに設定
        $this->subData['use_option'] = 0;
        // クレジットカード決済を有効にする
        $this->subData['enable_payment_type'] = array(
            0 => 10,
        );
        // プラグインの初期値を登録
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->subData);

        // クレジットカードお預かり情報照会（MemberClientService）モック作成
        $server_url = 'https://';
        $sendParams = null;

        // 預かりカード一件
        $results['cardData'] = array(
            'cardKey' => '1',
            'maskingCardNo' => '************1111',
            'cardExp' => '0528',
            'cardOwner' => 'KURONEKO YAMATO',
            'subscriptionFlg' => '1',
        );
        $results['cardUnit'] = 1;

        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService($server_url, $sendParams, $results);

        $crawler = $this->client->request(
            'GET',
            $this->app->url('yamato_mypage_change_card', array('mypageno' => 'card'))
        );
        // 正しく表示処理されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertRegexp('/選択/u',
            $crawler->filter('table thead tr th')->text());
    }

    public function testIndex__預かりカード照会でエラーが出た場合エラーメッセージが返る()
    {
        // オプションサービスを契約済みに設定
        $this->subData['use_option'] = 0;
        // クレジットカード決済を有効にする
        $this->subData['enable_payment_type'] = array(
            0 => 10,
        );
        // プラグインの初期値を登録
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->subData);

        // クレジットカードお預かり情報照会（MemberClientService）モック作成
        $server_url = false;
        $sendParams = null;
        $results[] = array();
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService($server_url, $sendParams, $results);

        $crawler = $this->client->request(
            'GET',
            $this->app->url('yamato_mypage_change_card', array('mypageno' => 'card'))
        );
        // 正しく表示処理されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        // エラーメッセージが返ること
        $this->assertRegexp('/お預かり照会でエラーが発生しました。/u',
            $crawler->filter('.col-md-10 .message .errormsg')->text());
    }

    public function testIndex__オプションサービス未契約()
    {
        // オプションサービスを未契約に設定
        $this->subData['use_option'] = 1;
        // プラグインの初期値を登録
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->subData);

        $crawler = $this->client->request(
            'GET',
            $this->app->url('yamato_mypage_change_card', array('mypageno' => 'card'))
        );
        // 正しく表示処理されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        // エラーメッセージが返ること
        $this->assertRegexp('/現在のご契約内容ではマイページカード編集ページはご利用になれません/u',
            $crawler->filter('#default_error__message')->text());
    }

    public function testIndex__カード情報登録()
    {
        // オプションサービスを契約済みに設定
        $this->subData['use_option'] = 0;
        // クレジットカード決済を有効にする
        $this->subData['enable_payment_type'] = array(
            0 => 10,
        );
        // プラグインの初期値を登録
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->subData);

        // クレジットカードお預かり情報照会（MemberClientService）モック作成
        $server_url = 'https://';
        $sendParams = null;
        // 預かりカード一件
        $results['cardData'] = array(
            'cardKey' => '1',
            'maskingCardNo' => '************1111',
            'cardExp' => '0528',
            'cardOwner' => 'KURONEKO YAMATO',
            'subscriptionFlg' => '1',
        );
        $results['cardUnit'] = 1;
        $bool = true;
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService($server_url, $sendParams, $results, $bool);

        // フォームの作成
        $formData = $this->createFormData();

        $crawler = $this->client->request(
            'POST',
            $this->app->url('yamato_mypage_change_card', array('mypageno' => 'card'))
            , array('regist_credit' => $formData)
        );
        // 正しく表示処理されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertRegexp('/正常に更新されました/u',
            $crawler->filter('.col-md-10 .message .heading01')->text());
    }

    public function testIndex__カード情報登録__不備がある場合エラーメッセージが返る()
    {
        // オプションサービスを契約済みに設定
        $this->subData['use_option'] = 0;
        // クレジットカード決済を有効にする
        $this->subData['enable_payment_type'] = array(
            0 => 10,
        );
        // プラグインの初期値を登録
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->subData);

        // クレジットカードお預かり情報照会（MemberClientService）モック作成
        $server_url = 'https://';
        $sendParams = null;
        // 預かりカード一件
        $results['cardData'] = array(
            'cardKey' => '1',
            'maskingCardNo' => '************1111',
            'cardExp' => '0528',
            'cardOwner' => 'KURONEKO YAMATO',
            'subscriptionFlg' => '1',
        );
        $results['cardUnit'] = 1;
        $bool = true;
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService($server_url, $sendParams, $results, $bool);

        // フォームの作成
        $formData = array();

        $crawler = $this->client->request(
            'POST',
            $this->app->url('yamato_mypage_change_card', array('mypageno' => 'card'))
            , array('regist_credit' => $formData)
        );
        // 正しく表示処理されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertRegexp('/入力内容に不備があります。内容をご確認ください/u',
            $crawler->filter('.col-md-10 .message .errormsg')->text());
    }

    public function testIndex__カード情報削除()
    {
        // オプションサービスを契約済みに設定
        $this->subData['use_option'] = 0;
        // クレジットカード決済を有効にする
        $this->subData['enable_payment_type'] = array(
            0 => 10,
        );
        // プラグインの初期値を登録
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->subData);

        // クレジットカードお預かり情報照会（MemberClientService）モック作成
        $server_url = 'https://';
        $sendParams = null;
        // 預かりカード一件
        $results['cardData'] = array(
            'cardKey' => '1',
            'maskingCardNo' => '************1111',
            'cardExp' => '0528',
            'cardOwner' => 'KURONEKO YAMATO',
            'subscriptionFlg' => '0',
            'lastCreditDate' => '20161010',
        );
        $results['cardUnit'] = 1;
        $bool = true;
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService($server_url, $sendParams, $results, $bool);

        // フォームの作成
        $formData = array(
            'cardSeq' => array(0 => '1'),
            'card_key' => $results['cardData']['cardKey'],
        );

        $crawler = $this->client->request(
            'POST',
            $this->app->url('yamato_mypage_change_card', array('mypageno' => 'card'))
            , array(
                'form' => $formData,
            )
        );

        // 正しく表示処理されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertRegexp('/正常に更新されました/u',
            $crawler->filter('.col-md-10 .message .heading01')->text());
    }

    public function testIndex__カード情報削除__予約販売利用有りのカード情報の場合エラーメッセージが返る()
    {
        // オプションサービスを契約済みに設定
        $this->subData['use_option'] = 0;
        // クレジットカード決済を有効にする
        $this->subData['enable_payment_type'] = array(
            0 => 10,
        );
        // プラグインの初期値を登録
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->subData);

        // クレジットカードお預かり情報照会（MemberClientService）モック作成
        $server_url = 'https://';
        $sendParams = null;
        // 預かりカード一件
        $results['cardData'] = array(
            'cardKey' => '1',
            'maskingCardNo' => '************1111',
            'cardExp' => '0528',
            'cardOwner' => 'KURONEKO YAMATO',
            'subscriptionFlg' => '1',
            'lastCreditDate' => '20161010',
        );
        $results['cardUnit'] = 1;
        $bool = true;
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService($server_url, $sendParams, $results, $bool);

        // フォームの作成
        $formData = array(
            'cardSeq' => array(0 => '1'),
            'card_key' => $results['cardData']['cardKey'],
        );

        $crawler = $this->client->request(
            'POST',
            $this->app->url('yamato_mypage_change_card', array('mypageno' => 'card'))
            , array(
                'form' => $formData,
            )
        );

        // 正しく表示処理されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        // エラーメッセージが返ること
        $this->assertRegexp('/予約販売利用有りのカード情報は削除できません。/u',
            $crawler->filter('.col-md-10 .message .errormsg')->text());
    }

    public function testDoRegistCard__True()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'doRegistCard');
        $method->setAccessible(true);

        // 会員情報取得
        $customer = $this->app['user'];
        /** @var Customer $objCustomer */
        $objCustomer = $this->app['eccube.repository.customer']->find($customer->getId());

        // パラメタ作成
        $listParam = array(
            'cardSeq' => array(0 => '1'),
            'card_key' => 1,
        );

        // クレジットカードお預かり情報照会（MemberClientService）モック作成
        $server_url = 'https://';
        $sendParams = null;
        // 預かりカード一件
        $results['cardData'] = array(
            'card_key' => '1',
            'maskingCardNo' => '************1111',
            'cardExp' => '0528',
            'cardOwner' => 'KURONEKO YAMATO',
            'subscriptionFlg' => '0',
        );
        $results['cardUnit'] = 1;
        $bool = true;
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService($server_url, $sendParams, $results, $bool);

        // Trueが返ること
        $this->assertTrue($method->invoke($this->object, $objCustomer->getId(), $listParam, $this->object, $this->app));
    }

    public function testDoRegistCard__False__お預かり情報照会でエラーが出た場合Falseが返る()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'doRegistCard');
        $method->setAccessible(true);

        // 会員情報取得
        $customer = $this->app['user'];
        /** @var Customer $objCustomer */
        $objCustomer = $this->app['eccube.repository.customer']->find($customer->getId());

        // パラメタ作成
        $listParam = array();

        // クレジットカードお預かり情報照会（MemberClientService）モック作成
        $server_url = false;
        $sendParams = null;
        // 預かりカード一件
        $results = array();
        $bool = true;
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService($server_url, $sendParams, $results, $bool);

        // Falseが返ること
        $this->assertFalse($method->invoke($this->object, $objCustomer->getId(), $listParam, $this->object, $this->app));
    }

    public function testDoRegistCard__登録数上限3件を超えた場合はfalseが返る()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'doRegistCard');
        $method->setAccessible(true);

        // 会員情報取得
        $customer = $this->app['user'];
        /** @var Customer $objCustomer */
        $objCustomer = $this->app['eccube.repository.customer']->find($customer->getId());

        // パラメタ作成
        $listParam = array(
            'cardSeq' => array(0 => '1'),
            'card_key' => 1,
        );

        // クレジットカードお預かり情報照会（MemberClientService）モック作成
        $server_url = 'https://';
        $sendParams = null;
        // 預かりカード一件
        $results['cardData'] = array(
            'card_key' => '1',
            'maskingCardNo' => '************1111',
            'cardExp' => '0528',
            'cardOwner' => 'KURONEKO YAMATO',
            'subscriptionFlg' => '0',
        );
        $results['cardUnit'] = $this->app['config']['YamatoPayment']['const']['CREDIT_SAVE_LIMIT'];
        $bool = true;
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService($server_url, $sendParams, $results, $bool);

        // Falseが返ること
        $this->assertFalse($method->invoke($this->object, $objCustomer->getId(), $listParam, $this->object, $this->app));
    }

    public function testDoRegistCard__カード情報登録でエラーが発生した場合はFalseが返る()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'doRegistCard');
        $method->setAccessible(true);

        // 会員情報取得
        $customer = $this->app['user'];
        /** @var Customer $objCustomer */
        $objCustomer = $this->app['eccube.repository.customer']->find($customer->getId());

        // パラメタ作成
        $listParam = array(
            'cardSeq' => array(0 => '1'),
            'card_key' => 1,
        );

        // クレジットカードお預かり情報照会（MemberClientService）モック作成
        $server_url = 'https://';
        $sendParams = null;
        // 預かりカード一件
        $results['cardData'] = array(
            'card_key' => '1',
            'maskingCardNo' => '************1111',
            'cardExp' => '0528',
            'cardOwner' => 'KURONEKO YAMATO',
            'subscriptionFlg' => '0',
        );
        $results['cardUnit'] = 1;
        $bool = false;
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService($server_url, $sendParams, $results, $bool);

        // Falseが返ること
        $this->assertFalse($method->invoke($this->object, $objCustomer->getId(), $listParam, $this->object, $this->app));
    }

    public function testGetArrCardInfo__預かり情報1件の場合()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'getArrCardInfo');
        $method->setAccessible(true);

        // 預かりカード一件
        $listCardInfos['cardData'] = array(
            'card_key' => '1',
            'maskingCardNo' => '************1111',
            'cardExp' => '0528',
            'cardOwner' => 'KURONEKO YAMATO',
            'subscriptionFlg' => '1',
        );
        $listCardInfos['cardUnit'] = 1;

        $actual = $method->invoke($this->object, $listCardInfos);

        // 預かり情報が1件の場合、返ってくる値は更新されること
        $this->assertNotEquals($listCardInfos, $actual);
    }

    public function testGetArrCardInfo__預かり情報2件の場合()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'getArrCardInfo');
        $method->setAccessible(true);

        // 預かりカード2件
        $listCardInfos['cardData'] = array(
            0 => array(
                'card_key' => '1',
                'maskingCardNo' => '************1111',
                'cardExp' => '0528',
                'cardOwner' => 'KURONEKO YAMATO',
                'subscriptionFlg' => '1',
            ),
            1 => array(
                'card_key' => '2',
                'maskingCardNo' => '************2222',
                'cardExp' => '1128',
                'cardOwner' => 'IPPO MAEE',
                'subscriptionFlg' => '0',
            ),
        );
        $listCardInfos['cardUnit'] = 2;

        $actual = $method->invoke($this->object, $listCardInfos);

        // 預かり情報が複数件の場合、返ってくる値に変更がないこと
        $this->assertEquals($listCardInfos, $actual);
    }

    protected function createFormData()
    {
        $form = array(
            '_token' => 'dummy',
            // カード番号
            'card_no' => '1234567890123456',
            // 有効期限(月)
            'card_exp_month' => '05',
            // 有効期限(年)
            'card_exp_year' => '28',
            // カード名義
            'card_owner' => 'KURONEKO YAMATO',
            // セキュリティコード
            'security_code' => '1234',
            // 3Dセキュア
            'CardSeq' => null,
        );
        return $form;
    }

    private function createMemberClientService($server_url, $sendParams, $results, $bool = false)
    {
        if (is_null($sendParams)) {
            $ret = $server_url;
        } else {
            $ret = array($server_url, $sendParams);
        }

        $mock = $this->getMock('MemberClientService', array('doGetCard', 'getResults', 'getError', 'doRegistCard', 'doDeleteCard'));
        $mock->expects($this->any())
            ->method('doGetCard')
            ->will($this->returnValue($ret));
        $mock->expects($this->any())
            ->method('getResults')
            ->will($this->returnValue($results));
        $mock->expects($this->any())
            ->method('getError')
            ->will($this->returnValue(array('エラーメッセージ')));
        $mock->expects($this->any())
            ->method('doRegistCard')
            ->will($this->returnValue($bool));
        $mock->expects($this->any())
            ->method('doDeleteCard')
            ->will($this->returnValue($bool));
        return $mock;
    }
}
