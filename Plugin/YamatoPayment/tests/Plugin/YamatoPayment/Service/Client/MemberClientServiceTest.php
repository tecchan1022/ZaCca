<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Service\Client;

use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Plugin\YamatoPayment\Service\AbstractServiceTestCase;
use Plugin\YamatoPayment\Util\PaymentUtil;
use Plugin\YamatoPayment\Util\PluginUtil;

class MemberClientServiceTest extends AbstractServiceTestCase
{
    /** @var  PluginUtil */
    var $PluginUtil;
    /** @var  PaymentUtil */
    var $PaymentUtil;
    /** @var YamatoPaymentMethod $YamatoPaymentMethod */
    var $YamatoPaymentMethod;
    /** @var  array */
    var $userSettings;

    /**
     * @var MemberClientService
     */
    protected $object;

    function setUp()
    {
        parent::setUp();
        $this->object = $this->app['yamato_payment.service.client.member'];

        $this->PluginUtil = $this->app['yamato_payment.util.plugin'];
        $this->PaymentUtil = $this->app['yamato_payment.util.payment'];
        $this->userSettings = $this->PluginUtil->getUserSettings();
        $this->YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_DEFERRED']));

    }

    function test_doGetCard__リクエスト成功の場合__trueが返ること()
    {
        // ユーザー設定 オプションサービス（0:契約済 1:未契約）
        $this->userSettings['use_option'] = 0;
        $this->PluginUtil->registerUserSettings($this->userSettings);

        // 会員情報作成
        $Customer = $this->createCustomer();

        /*
         * 決済モジュール 決済処理 クレジットカードのお預かり処理（MemberClientService/BaseClientService）モック作成
         */
        // MemberClientService（BaseClientService）モック化
        $this->object = $this->createMemberClientService(true);

        /*
         * クレジットカードお預かり情報照会実行
         */
        // trueが返ること
        $this->assertTrue($this->object->doGetCard($Customer->getId()));
    }

    function test_doGetCard__非会員の場合__falseが返ること()
    {
        // ユーザー設定 オプションサービス（0:契約済 1:未契約）
        $this->userSettings['use_option'] = 0;
        $this->PluginUtil->registerUserSettings($this->userSettings);

        /*
         * クレジットカードお預かり情報照会実行
         */
        // falseが返ること
        $this->assertFalse($this->object->doGetCard(0));
    }

    function test_doGetCard__オプションサービス未契約の場合__falseが返ること()
    {
        // ユーザー設定 オプションサービス（0:契約済 1:未契約）
        $this->object = new Change($this->app);
        $this->object->changeOption();

        /*
         * クレジットカードお預かり情報照会実行
         */
        // falseが返ること
        $this->assertFalse($this->object->doGetCard(0));
    }

    function test_doRegistCard__リクエスト成功の場合__trueが返ること()
    {
        // 会員情報作成
        $Customer = $this->createCustomer();

        /*
         * 決済モジュール 決済処理 クレジットカードのお預かり処理（MemberClientService/BaseClientService）モック作成
         */
        // MemberClientService（BaseClientService）モック化
        $this->object = $this->createMemberClientService(true);

        /*
         * クレジットカードお預かり情報登録実行
         */
        // trueが返ること
        $this->assertTrue($this->object->doRegistCard($Customer->getId()));
    }

    function test_doRegistCard__非会員の場合__falseが返ること()
    {
        /*
         * クレジットカードお預かり情報照会実行
         */
        // falseが返ること
        $this->assertFalse($this->object->doRegistCard(0));
    }

    function test_doDeleteCard__リクエスト成功の場合__trueが返ること()
    {
        // 会員情報作成
        $Customer = $this->createCustomer();

        /*
         * 決済モジュール 決済処理 クレジットカードのお預かり処理（MemberClientService/BaseClientService）モック作成
         */
        // MemberClientService（BaseClientService）モック化
        $this->object = $this->createMemberClientService(true);

        // パラメータ情報作成
        $listParam = array(
            // カード情報
            // cardKey
            'cardKey' => 1,
        );

        /*
         * クレジットカードお預かり情報照会実行
         */
        // trueが返ること
        $this->assertTrue($this->object->doDeleteCard($Customer->getId(), $listParam));
    }

    function test_doDeleteCard__非会員の場合__falseが返ること()
    {
        /*
         * クレジットカードお預かり情報照会実行
         */
        // falseが返ること
        $this->assertFalse($this->object->doDeleteCard(0));
    }

    private function createMemberClientService($sendRequest = false)
    {
        $mock = $this->getMock('Plugin\YamatoPayment\Service\Client\MemberClientService', array('sendRequest'), array($this->app));
        $mock->expects($this->any())
            ->method('sendRequest')
            ->will($this->returnValue($sendRequest));

        return $mock;
    }
}

class Change extends MemberClientService {
    public function changeOption()
    {
        $this->userSettings['use_option'] = 1;
    }
}
