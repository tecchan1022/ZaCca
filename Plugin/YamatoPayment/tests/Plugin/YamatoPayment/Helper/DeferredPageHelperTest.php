<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Helper;

use Eccube\Application;
use Eccube\Entity\Order;

class DeferredPageHelperTest extends AbstractHelperTestCase
{
    var $error;

    /** @var DeferredPageHelper */
    var $object;

    /** @var Order */
    var $Order;

    protected $const;

    public function setUp()
    {
        parent::setUp();
        $this->const = $this->app['config']['YamatoPayment']['const'];
        $this->object = new DeferredPageHelper($this->app);

        // 受注情報作成
        $this->Order = $this->createOrder($this->createCustomer());
        $this->Order->setPaymentTotal(1000);
        $this->createOrderPaymentDataDeferred($this->Order);
    }

    function testModeAction_modeがnext_isCompleteはtrueとなること_注文状況が新規受付になること()
    {
        /*
         * DeferredClientServiceモック化
         */
        $this->app['yamato_payment.service.client.deferred'] = $this->createDeferredClientService(true);

        // フォームデータ作成
        $listParam = array();

        // 支払方法の設定情報作成
        $paymentExtension = $this->app['yamato_payment.util.payment']->getPaymentTypeConfig($this->Order->getPayment()->getId());

        // isCompleteがfalseなことを確認
        $this->assertFalse($this->object->isComplete);

        // 注文状況が「新規受付」でないことを確認
        $this->assertNotEquals($this->app['config']['order_new'], $this->Order->getOrderStatus()->getId());

        $this->object->modeAction($listParam, $this->Order, $paymentExtension, $this);

        // isCompleteはtrueとなること
        $this->assertTrue($this->object->isComplete);

        // 注文状況が「新規受付」なこと
        $Order = $this->app['eccube.repository.order']->find($this->Order->getId());
        $this->assertEquals($this->app['config']['order_new'], $Order->getOrderStatus()->getId());
    }

    function testModeAction_modeはnext_決済でエラーが発生した場合_エラーメッセージが返ること()
    {
        /*
         * DeferredClientServiceモック化
         */
        $this->app['yamato_payment.service.client.deferred'] = $this->createDeferredClientService(false);

        // フォームデータ作成
        $listParam = array();

        // 支払方法の設定情報作成
        $paymentExtension = $this->app['yamato_payment.util.payment']->getPaymentTypeConfig($this->Order->getPayment()->getId());

        $this->object->modeAction($listParam, $this->Order, $paymentExtension, $this);

        $this->assertContains('決済でエラーが発生しました', $this->error['payment']);
    }

    function testCheckError_エラーがないならEmptyが返る()
    {
        // フォームデータ作成
        $listParam = $this->object->createFormData($this->Order);

        // テスト対象メソッド実行
        $errors = $this->object->checkError($listParam, $this->Order);

        // Emptyが返ること
        $this->assertEmpty($errors);
    }

    function testCheckError_購入商品チェック()
    {
        // フォームデータ作成
        $listParam = $this->object->createFormData($this->Order);

        // 購入商品小計が上限額を超える
        $listParam['details'][0]['subTotal'] = 1000000;
        // 商品単価が上限額を超える
        $listParam['details'][0]['unitPrice'] = 1000000;
        // 購入点数が上限額を超える
        $listParam['details'][0]['itemCount'] = 10000;

        // テスト対象メソッド実行
        $errors = $this->object->checkError($listParam, $this->Order);

        // エラーメッセージが返ること
        $this->assertContains('商品小計が不正です。', $errors['subTotal1']);
        $this->assertContains('商品単価が不正です。', $errors['unitPrice1']);
        $this->assertContains('商品数量は9999までです。', $errors['itemCount1']);

        // 正常
        $listParam['details'][0]['subTotal'] = 999999;
        $listParam['details'][0]['unitPrice'] = 999999;
        $listParam['details'][0]['itemCount'] = 9999;
        $errors = $this->object->checkError($listParam, $this->Order);
        $this->assertEmpty($errors);
    }

    function testCheckError_お届け先チェック()
    {
        // フォームデータ作成
        $listParam = $this->object->createFormData($this->Order);

        // 配送先のエラーチェックをおこなう
        $listParam['sendDiv'] = 1;

        // 未登録
        $listParam['shippings'][0]['sendName'] = '';
        $listParam['shippings'][0]['sendPostCode'] = '';
        $listParam['shippings'][0]['sendAddress1'] = '';

        // テスト対象メソッド実行
        $errors = $this->object->checkError($listParam, $this->Order);

        // エラーメッセージが返ること
        $this->assertContains('送り先名称1が入力されていません。', $errors['sendName1']);
        $this->assertContains('送り先郵便番号1が入力されていません。', $errors['sendPostCode1']);
        $this->assertContains('送り先住所1が入力されていません。', $errors['sendAddress11']);

        // 正常
        $listParam['shippings'][0]['sendName'] = 'dummy';
        $listParam['shippings'][0]['sendPostCode'] = 'dummy';
        $listParam['shippings'][0]['sendAddress1'] = 'dummy';
        $errors = $this->object->checkError($listParam, $this->Order);
        $this->assertEmpty($errors);
    }

    function testCheckError_決済金額総計が支払方法利用条件最少額以下の場合_エラーメッセージが返る()
    {
        // 支払方法マスタの利用最小額を設定
        $Payment = $this->Order->getPayment();
        $Payment->setRuleMin(1000);

        // フォームデータ作成
        $listParam = $this->object->createFormData($this->Order);

        // 決済金額総計が利用最小額以下
        $listParam['totalAmount'] = (int)$Payment->getRuleMin() - 1;

        // テスト対象メソッド実行
        $errors = $this->object->checkError($listParam, $this->Order);

        // エラーメッセージが返ること
        $this->assertContains('決済金額総計が支払方法利用条件を満たしておりません。', $errors['totalAmount']);

        // 正常
        $listParam['totalAmount'] = $Payment->getRuleMin();
        $errors = $this->object->checkError($listParam, $this->Order);
        $this->assertEmpty($errors);
    }

    function testCheckError_決済金額総計が支払方法利用条件最大額以上の場合_エラーメッセージが返る()
    {
        // 支払方法マスタの利用最大額を設定
        $Payment = $this->Order->getPayment();
        $Payment->setRuleMax(1000);

        // フォームデータ作成
        $listParam = $this->object->createFormData($this->Order);

        // 決済金額総計が利用最大額以上
        $listParam['totalAmount'] = (int)$Payment->getRuleMax() + 1;

        // テスト対象メソッド実行
        $errors = $this->object->checkError($listParam, $this->Order);

        // エラーメッセージが返ること
        $this->assertContains('決済金額総計が支払方法利用条件を満たしておりません。', $errors['totalAmount']);

        // 正常
        $listParam['totalAmount'] = $Payment->getRuleMax();
        $errors = $this->object->checkError($listParam, $this->Order);
        $this->assertEmpty($errors);
    }

    function testCheckError_配送先数が11以上の場合_エラーメッセージが返る()
    {
        $count = $this->app['config']['YamatoPayment']['const']['DEFERRED_DELIV_ADDR_MAX'];

        // フォームデータ作成
        $listParam = $this->object->createFormData($this->Order);

        // 配送先数が11件
        $listParam['shippings'] = array();
        for ($i = 0; $i < $count + 1; $i++) {
            $listParam['shippings'][$i] = $i;
        }

        // テスト対象メソッド実行
        $errors = $this->object->checkError($listParam, $this->Order);

        // エラーメッセージが返ること
        $this->assertContains('送り先の上限数は' . $count . '件です。', $errors['sendCount']);

        // 正常
        $listParam['shippings'] = array();
        for ($i = 0; $i < $count; $i++) {
            $listParam['shippings'][$i] = $i;
        }
        $errors = $this->object->checkError($listParam, $this->Order);
        $this->assertEmpty($errors);
    }

    function testCheckError_パスワード未登録の場合_エラーメッセージが返る()
    {
        // フォームデータ作成
        $listParam = $this->object->createFormData($this->Order);

        // パスワード未登録
        $listParam['password'] = '';

        // テスト対象メソッド実行
        $errors = $this->object->checkError($listParam, $this->Order);

        // エラーメッセージが返ること
        $this->assertContains('パスワードが不正です。店舗までお問合わせ下さい。', $errors['password']);

        // 正常
        $listParam['password'] = 'dummy';
        $errors = $this->object->checkError($listParam, $this->Order);
        $this->assertEmpty($errors);
    }

    function testCheckError_注文者の電話番号が12桁以上の場合_エラーメッセージが返る()
    {
        // フォームデータ作成
        $listParam = $this->object->createFormData($this->Order);

        // 注文者の電話番号が12桁以上
        $listParam['telNum'] = 123456789012;

        // テスト対象メソッド実行
        $errors = $this->object->checkError($listParam, $this->Order);

        // エラーメッセージが返ること
        $this->assertContains('注文者の電話番号は10桁または11桁で入力してください。', $errors['telNum']);

        // 正常
        $listParam['telNum'] = 12345678901;
        $errors = $this->object->checkError($listParam, $this->Order);
        $this->assertEmpty($errors);
    }

    function testCheckError_配送先の電話番号が12桁以上の場合_エラーメッセージが返る()
    {
        // フォームデータ作成
        $listParam = $this->object->createFormData($this->Order);

        // 配送先の電話番号が12桁以上
        $i = 0;
        $seq = 0;
        foreach ($listParam['shippings'] as &$shipping) {
            $seq = $i + 1;
            $shipping['sendTelNum'] = 123456789012;
        }

        // テスト対象メソッド実行
        $errors = $this->object->checkError($listParam, $this->Order);

        // エラーメッセージが返ること
        $this->assertContains('の電話番号は10桁または11桁で入力してください。', $errors['sendTelNum' . $seq]);

        // 正常
        foreach ($listParam['shippings'] as &$shipping) {
            $shipping['sendTelNum'] = 12345678901;
        }
        $errors = $this->object->checkError($listParam, $this->Order);
        $this->assertEmpty($errors);
    }

    private function createDeferredClientService($doPaymentRequest = false)
    {
        $mock = $this->getMock('DeferredClientService', array('doPaymentRequest', 'getError'));
        $mock->expects($this->any())
            ->method('doPaymentRequest')
            ->will($this->returnValue($doPaymentRequest));
        $mock->expects($this->any())
            ->method('getError')
            ->will($this->returnValue(array('エラーメッセージ')));

        return $mock;
    }

    function testConvertFormDataToSendParam()
    {
        // フォームデータ作成
        $listParam = $this->object->createFormData($this->Order);

        $expected = $listParam;

        /*
         * 事前チェック
         */
        // 商品購入エリア
        // $listParam直下に商品購入情報は存在しないこと
        foreach ($listParam['details'] as $key => $val) {
            $seq = $key + 1;
            $this->assertFalse(isset($listParam['itemName' . $seq]));
            $this->assertFalse(isset($listParam['itemCount' . $seq]));
            $this->assertFalse(isset($listParam['unitPrice' . $seq]));
            $this->assertFalse(isset($listParam['subTotal' . $seq]));
        }
        // $listParam['details']が存在すること
        $this->assertTrue(isset($listParam['details']));

        // 送り先情報エリア
        // $listParam直下に送り先情報は存在しないこと
        foreach ($listParam['shippings'] as $key => $val) {
            $seq = $key + 1;
            if ($seq == 1) {
                // 1件目の項目に数字を振らない
                $seq = '';
            }
            $this->assertFalse(isset($listParam['sendName' . $seq]));
            $this->assertFalse(isset($listParam['sendPostCode' . $seq]));
            $this->assertFalse(isset($listParam['sendAddress1' . $seq]));
            $this->assertFalse(isset($listParam['sendTelNum' . $seq]));
        }

        // テスト対象メソッド実行
        $this->object->convertFormDataToSendParam($listParam);

        /*
         * 実行結果
         */
        // 商品購入エリア
        // $listParam直下に商品購入情報が存在すること
        foreach ($expected['details'] as $key => $val) {
            $seq = $key + 1;
            $this->assertTrue(isset($listParam['itemName' . $seq]));
            $this->assertTrue(isset($listParam['itemCount' . $seq]));
            $this->assertTrue(isset($listParam['unitPrice' . $seq]));
            $this->assertTrue(isset($listParam['subTotal' . $seq]));
        }
        // $listParam['details']は存在しないこと
        $this->assertFalse(isset($listParam['details']));

        // 送り先情報エリア
        // $listParam直下に送り先情報が存在すること
        foreach ($listParam['shippings'] as $key => $val) {
            $seq = $key + 1;
            if ($seq == 1) {
                $seq = '';
            }
            $this->assertTrue(isset($listParam['sendName' . $seq]));
            $this->assertTrue(isset($listParam['sendPostCode' . $seq]));
            $this->assertTrue(isset($listParam['sendAddress1' . $seq]));
            $this->assertTrue(isset($listParam['sendTelNum' . $seq]));
        }
    }

    function testCreateFormData_Address1が25文字を超える場合_はAddress2に分割されて返る()
    {
        $address01 = '123456789012345678901234567890';
        $address02 = '123';
        // 購入者の住所を設定
        $this->Order->setPref($this->app['eccube.repository.master.pref']->find(1));
        $this->Order->setAddr01($address01);
        $this->Order->setAddr02($address02);

        // お届け先の住所を設定
        $Shippings = $this->Order->getShippings();
        $Shippings[0]->setPref($this->app['eccube.repository.master.pref']->find(1));
        $Shippings[0]->setAddr01($address01);
        $Shippings[0]->setAddr02($address02);

        // テスト対象メソッド実行
        $actual = $this->object->createFormData($this->Order);

        // 購入者住所 Address2に分割されて返ること
        $this->assertEquals('北海道１２３４５６７８９０１２３４５６７８９０１２', $actual['address1']);
        $this->assertEquals('３４５６７８９０　１２３', $actual['address2']);

        // お届け先住所 Address2に分割されて返ること
        $this->assertEquals('北海道１２３４５６７８９０１２３４５６７８９０１２', $actual['shippings'][0]['sendAddress1']);
        $this->assertEquals('３４５６７８９０　１２３', $actual['shippings'][0]['sendAddress2']);
    }

    function testCreateFormData_半角全角を変換する()
    {
        $str = 'あa1"\'\~';

        $this->Order->setName01($str);
        $this->Order->setName02($str);
        $this->Order->setkana01('アアア');
        $this->Order->setkana02('イイイ');

        // お届け先の住所を設定
        $Shippings = $this->Order->getShippings();
        $Shippings[0]->setName01($str);
        $Shippings[0]->setName02($str);

        // 商品名を設定
        $Details = $this->Order->getOrderDetails();
        $Details[0]->setProductName($str);


        // テスト対象メソッド実行
        $actual = $this->object->createFormData($this->Order);

        $expected = 'あａ１”’￥～';

        // [name]が全角に変換されること
        $this->assertEquals($expected . '　' . $expected, $actual['name']);
        // [nameKana]が半角カナに変換されること
        $this->assertEquals('ｱｱｱ ｲｲｲ', $actual['nameKana']);

        // [sendName]が全角に変換されること
        $this->assertEquals($expected . '　' . $expected, $actual['shippings'][0]['sendName']);

        // [itemName]が全角に変換されること
        $this->assertEquals($expected, $actual['details'][0]['itemName']);
    }
}
