<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */

namespace Plugin\YamatoPayment\Service\Client;

use Eccube\Entity\Master\ProductType;
use Eccube\Entity\OrderDetail;
use Plugin\YamatoPayment\Entity\YamatoOrderPayment;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Plugin\YamatoPayment\Entity\YamatoProduct;
use Plugin\YamatoPayment\Service\AbstractServiceTestCase;
use Plugin\YamatoPayment\Util\PaymentUtil;
use Plugin\YamatoPayment\Util\PluginUtil;

class CreditClientServiceTest extends AbstractServiceTestCase
{
    /** @var  PluginUtil */
    var $PluginUtil;
    /** @var  PaymentUtil */
    var $PaymentUtil;
    /** @var YamatoPaymentMethod $YamatoPaymentMethod */
    var $YamatoPaymentMethod;

    /**
     * @var CreditClientService
     */
    protected $object;

    function setUp()
    {
        parent::setUp();
        $this->object = $this->app['yamato_payment.service.client.credit'];

        $this->PluginUtil = $this->app['yamato_payment.util.plugin'];
        $this->PaymentUtil = $this->app['yamato_payment.util.payment'];
        $this->YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']));

    }

    function test_doSecureTran__3Dセキュア認証遷移成功__リクエスト成功の場合__trueが返ること__受注支払情報が更新されていること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT']);
        // memo05：受注編集画面の決済内容表示用データをセット
        $memo05 = $YamatoOrderPayment->getMemo05();
        $memo05 = array_merge_recursive($memo05, array(
            'order_no' => $Order->getId(),
            'threeDToken' => '3D_dummy',
        ));
        $YamatoOrderPayment->setMemo05($memo05);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());
        $this->app['orm.em']->flush();

        $expected = array(
            // 受注情報から決済完了ページ表示用データを取得
            'memo02' => $YamatoOrderPayment->getMemo02(),
            // 受注情報から決済状況取得
            'memo04' => $YamatoOrderPayment->getMemo04(),
            // 受注情報から決済データ取得
            'memo05' => $YamatoOrderPayment->getMemo05(),
            // 受注情報から決済ログ取得
            'memo09' => $YamatoOrderPayment->getMemo09(),
        );

        // パラメータ情報作成
        $listParam = array(
            // オプションサービス区分
            // 購入時カード預かり 0：利用しない 1：利用する
            'register_card' => '1',
            // 預かりカードでの購入 0：利用しない 1：利用する
            'use_registed_card' => '1',
            // 予約商品購入 true：予約商品 false：通常商品
            'tpl_is_reserve' => true,

            // カード情報
            // card_no
            'CARD_NO' => '************1111',
            //
            'CARD_EXP' => '0528',

            // card_key
            'card_key' => 1,
        );

        // 支払方法の設定情報を取得する
        $PaymentExtension = $this->PaymentUtil->getPaymentTypeConfig($Order->getPayment()->getId());

        /*
         * 決済モジュール 決済処理 クレジットカード（CreditClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'returnDate' => date('YmdHms'),
            'crdCResCd' => '0' . date('YmdHms'),
            'threeDAuthHtml' => 'html://3D',
            'threeDToken' => '3D_dummy',
        );
        // CreditClientService（BaseClientService）モック化
        $this->object = $this->createCreditClientService(true, $getResults);

        /*
         * 3Dセキュア実行
         */
        // Trueが返ること
        $this->assertTrue($this->object->doSecureTran($Order->getId(), $listParam, $PaymentExtension));

        /** @var YamatoOrderPayment $newYamatoOrderPayment */
        $newYamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済完了ページ表示用データが更新されていること
        $this->assertNotEquals($expected['memo02'], $newYamatoOrderPayment->getMemo02());
        // 決済状況が更新されていること
        $this->assertNotEquals($expected['memo04'], $newYamatoOrderPayment->getMemo04());
        // 決済データが更新されていること
        $this->assertNotEquals($expected['memo05'], $newYamatoOrderPayment->getMemo05());
        // 決済ログが更新されていること
        $this->assertNotEquals($expected['memo09'], $newYamatoOrderPayment->getMemo09());
    }

    function test_doSecureTran__3Dセキュア認証遷移成功__リクエスト失敗の場合__falseが返ること__決済完了ページ表示用データに変更がないこと()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT']);
        // memo05：受注編集画面の決済内容表示用データをセット
        $memo05 = $YamatoOrderPayment->getMemo05();
        $memo05 = array_merge_recursive($memo05, array(
            'order_no' => $Order->getId(),
            'threeDToken' => '3D_dummy',
        ));
        $YamatoOrderPayment->setMemo05($memo05);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());
        $this->app['orm.em']->flush();

        $expected = array(
            // 受注情報から決済完了ページ表示用データを取得
            'memo02' => $YamatoOrderPayment->getMemo02(),
            // 受注情報から決済状況取得
            'memo04' => $YamatoOrderPayment->getMemo04(),
            // 受注情報から決済データ取得
            'memo05' => $YamatoOrderPayment->getMemo05(),
            // 受注情報から決済ログ取得
            'memo09' => $YamatoOrderPayment->getMemo09(),
        );

        // パラメータ情報作成
        $listParam = array(
            // オプションサービス区分
            // 購入時カード預かり 0：利用しない 1：利用する
            'register_card' => '1',
            // 預かりカードでの購入 0：利用しない 1：利用する
            'use_registed_card' => '1',
            // 予約商品購入 true：予約商品 false：通常商品
            'tpl_is_reserve' => true,

            // カード情報
            // card_no
            'CARD_NO' => '************1111',
            //
            'CARD_EXP' => '0528',

            // card_key
            'card_key' => 1,
        );

        // 支払方法の設定情報を取得する
        $PaymentExtension = $this->PaymentUtil->getPaymentTypeConfig($Order->getPayment()->getId());

        /*
         * 決済モジュール 決済処理 クレジットカード（CreditClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'returnDate' => date('YmdHms'),
            'crdCResCd' => '0' . date('YmdHms'),
            'threeDAuthHtml' => 'html://3D',
            'threeDToken' => '3D_dummy',
        );
        // CreditClientService（BaseClientService）モック化
        $this->object = $this->createCreditClientService(false, $getResults);
        $this->object->error = array('エラーメッセージ');

        /*
         * 3Dセキュア実行
         */
        // Falseが返ること
        $this->assertFalse($this->object->doSecureTran($Order->getId(), $listParam, $PaymentExtension));

        /** @var YamatoOrderPayment $newYamatoOrderPayment */
        $newYamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済完了ページ表示用データが変更ないこと
        $this->assertEquals($expected['memo02'], $newYamatoOrderPayment->getMemo02());
        // 決済状況が更新されていること
        $this->assertNotEquals($expected['memo04'], $newYamatoOrderPayment->getMemo04());
        // 決済データが更新されていること
        $this->assertNotEquals($expected['memo05'], $newYamatoOrderPayment->getMemo05());
        // 決済ログが更新されていること
        $this->assertNotEquals($expected['memo09'], $newYamatoOrderPayment->getMemo09());
    }

    function test_doSecureTran__3Dセキュア認証遷移失敗__受注IDが一致しない場合__エラーメッセージが返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT']);
        // memo05：受注編集画面の決済内容表示用データをセット
        $memo05 = $YamatoOrderPayment->getMemo05();
        $memo05 = array_merge_recursive($memo05, array(
            'order_no' => $Order->getId() + 1,
            'threeDToken' => '3D_dummy',
        ));
        $YamatoOrderPayment->setMemo05($memo05);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());
        $this->app['orm.em']->flush();

        // パラメータ情報作成
        $listParam = array(
            // オプションサービス区分
            // 購入時カード預かり 0：利用しない 1：利用する
            'register_card' => '1',
            // 預かりカードでの購入 0：利用しない 1：利用する
            'use_registed_card' => '1',
            // 予約商品購入 true：予約商品 false：通常商品
            'tpl_is_reserve' => true,

            // カード情報
            // card_no
            'CARD_NO' => '************1111',
            //
            'CARD_EXP' => '0528',

            // card_key
            'card_key' => 1,
        );

        // 支払方法の設定情報を取得する
        $PaymentExtension = $this->PaymentUtil->getPaymentTypeConfig($Order->getPayment()->getId());

        /*
         * 3Dセキュア実行
         */
        try {
            // error.twigにレンダーされる
            $this->object->doSecureTran($Order->getId(), $listParam, $PaymentExtension);
        } catch (\Exception $e) {
            // レンダーされること（レンダーするとExceptionが発生する)
            $expected = 'Template "error.twig" is not defined.';
            $this->assertContains($expected, $e->getMessage());
        }
    }

    function test_doSecureTran__3Dセキュア認証遷移失敗__決済データが存在しない場合__エラーメッセージが返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT']);
        // memo05：受注編集画面の決済内容表示用データを空にセット
        $YamatoOrderPayment->setMemo05(null);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());
        $this->app['orm.em']->flush();

        // パラメータ情報作成
        $listParam = array(
            // オプションサービス区分
            // 購入時カード預かり 0：利用しない 1：利用する
            'register_card' => '1',
            // 預かりカードでの購入 0：利用しない 1：利用する
            'use_registed_card' => '1',
            // 予約商品購入 true：予約商品 false：通常商品
            'tpl_is_reserve' => true,

            // カード情報
            // card_no
            'CARD_NO' => '************1111',
            //
            'CARD_EXP' => '0528',

            // card_key
            'card_key' => 1,
        );

        // 支払方法の設定情報を取得する
        $PaymentExtension = $this->PaymentUtil->getPaymentTypeConfig($Order->getPayment()->getId());

        /*
         * 3Dセキュア実行
         */
        try {
            // error.twigにレンダーされる
            $this->object->doSecureTran($Order->getId(), $listParam, $PaymentExtension);
        } catch (\Exception $e) {
            // レンダーされること（レンダーするとExceptionが発生する)
            $expected = 'Template "error.twig" is not defined.';
            $this->assertContains($expected, $e->getMessage());
        }
    }

    function test_doPaymentRequest__オプションサービス受注__予約商品__3Dセキュア加入__trueが返ること__受注支払情報が更新されていること()
    {
        // $_SERVERの設定
        $_SERVER['SERVER_PROTOCOL']  = 'HTTP/1.0';
        $_SERVER['HTTP_HOST'] = '';

        // ユーザー設定：オプションサービス 0：契約済み 1:未契約
        $userSettings = $this->PluginUtil->getUserSettings();
        $userSettings['use_option'] = 0;
        $this->PluginUtil->registerUserSettings($userSettings);

        // 支払方法設定：本人認証サービス(3Dセキュア) 0:利用しない 1:利用する
        $memo05 = $this->YamatoPaymentMethod->getMemo05();
        $memo05['TdFlag'] = 1;
        $this->YamatoPaymentMethod->setMemo05($memo05);

        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        // 予約商品出荷予定日を設定
        $OrderDetails = $Order->getOrderDetails();
        foreach($OrderDetails as $OrderDetail){
            /** @var ProductType $ProductType */
            $ProductType = $this->app['eccube.repository.master.product_type']->find(9625);
            /* @var OrderDetail $OrderDetail */
            // 商品種別を予約商品に設定
            $OrderDetail->getProductClass()
                ->setProductType($ProductType);
            $this->app['orm.em']->persist($OrderDetail);
            // 商品IDを取得
            $product_id = $OrderDetail->getProduct()->getId();
            /** @var YamatoProduct $YamatoProduct */
            $YamatoProduct = $this->app['yamato_payment.repository.yamato_product']->find($product_id);
            if(is_null($YamatoProduct)){
                $YamatoProduct = new YamatoProduct();
                $YamatoProduct->setId($product_id);
            }
            // 予約商品出荷予定日を設定
            $YamatoProduct->setReserveDate(20290625);
            $YamatoProduct->setNotDeferredFlg(0);
            $this->app['orm.em']->persist($YamatoProduct);
        }
        $this->app['orm.em']->flush();

        $expected = array(
            // 受注情報から決済完了ページ表示用データを取得
            'memo02' => $YamatoOrderPayment->getMemo02(),
            // 受注情報から決済状況取得
            'memo04' => $YamatoOrderPayment->getMemo04(),
            // 受注情報から決済データ取得
            'memo05' => $YamatoOrderPayment->getMemo05(),
            // 受注情報から決済ログ取得
            'memo09' => $YamatoOrderPayment->getMemo09(),
        );

        // パラメータ情報作成
        $listParam = array(
            // オプションサービス区分
            // 購入時カード預かり 0：利用しない 1：利用する
            'register_card' => '1',
            // 預かりカードでの購入 0：利用しない 1：利用する
            'use_registed_card' => '1',
            // 予約商品購入 true：予約商品 false：通常商品
            'tpl_is_reserve' => true,

            // カード情報
            // card_key
            'card_key' => 1,
        );

        // 支払方法の設定情報を取得する
        $PaymentExtension = $this->PaymentUtil->getPaymentTypeConfig($Order->getPayment()->getId());

        /*
         * 決済モジュール 決済処理 クレジットカードのお預かり処理（MemberClientService）モック作成
         */
        // クレジットカードお預かり情報照会（doGetCard）
        $doGetCard = true;
        // 通信結果（預かりカード一件）
        $getResults = $this->createCardData();
        // MemberClientServiceモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService($doGetCard, $getResults);

        /*
         * 決済モジュール 決済処理 クレジットカード（CreditClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'returnDate' => date('YmdHms'),
            'crdCResCd' => '0' . date('YmdHms'),
            'threeDAuthHtml' => 'html://3D',
            'threeDToken' => '3D_dummy',
        );
        // CreditClientService（BaseClientService）モック化
        $this->object = $this->createCreditClientService(true, $getResults);

        /*
         * クレジット決済実行
         */
        // Trueが返ること
        $this->assertTrue($this->object->doPaymentRequest($OrderExtension, $listParam, $PaymentExtension));

        /** @var YamatoOrderPayment $newYamatoOrderPayment */
        $newYamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済完了ページ表示用データが更新されていること
        $this->assertNotEquals($expected['memo02'], $newYamatoOrderPayment->getMemo02());
        // 決済状況が更新されていること
        $this->assertNotEquals($expected['memo04'], $newYamatoOrderPayment->getMemo04());
        // 決済データが更新されていること
        $this->assertNotEquals($expected['memo05'], $newYamatoOrderPayment->getMemo05());
        // 決済ログが更新されていること
        $this->assertNotEquals($expected['memo09'], $newYamatoOrderPayment->getMemo09());
    }

    function test_doPaymentRequest__通常受注__3Dセキュア加入__trueが返ること__受注支払情報が更新されていること()
    {
        // $_SERVERの設定
        $_SERVER['SERVER_PROTOCOL']  = 'HTTP/1.0';
        $_SERVER['HTTP_HOST'] = '';

        // ユーザー設定：オプションサービス 0：契約済み 1:未契約
        $userSettings = $this->PluginUtil->getUserSettings();
        $userSettings['use_option'] = 1;
        $this->PluginUtil->registerUserSettings($userSettings);

        // 支払方法設定：本人認証サービス(3Dセキュア) 0:利用しない 1:利用する
        $memo05 = $this->YamatoPaymentMethod->getMemo05();
        $memo05['TdFlag'] = 1;
        $this->YamatoPaymentMethod->setMemo05($memo05);

        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());
        $this->app['orm.em']->flush();

        $expected = array(
            // 受注情報から決済完了ページ表示用データを取得
            'memo02' => $YamatoOrderPayment->getMemo02(),
            // 受注情報から決済状況取得
            'memo04' => $YamatoOrderPayment->getMemo04(),
            // 受注情報から決済データ取得
            'memo05' => $YamatoOrderPayment->getMemo05(),
            // 受注情報から決済ログ取得
            'memo09' => $YamatoOrderPayment->getMemo09(),
        );

        // パラメータ情報作成
        $listParam = array();

        // 支払方法の設定情報を取得する
        $PaymentExtension = $this->PaymentUtil->getPaymentTypeConfig($Order->getPayment()->getId());

        /*
         * 決済モジュール 決済処理 クレジットカード（CreditClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'returnDate' => date('YmdHms'),
            'crdCResCd' => '0' . date('YmdHms'),
            'threeDAuthHtml' => 'html://3D',
            'threeDToken' => '3D_dummy',
        );
        // CreditClientService（BaseClientService）モック化
        $this->object = $this->createCreditClientService(true, $getResults);

        /*
         * クレジット決済実行
         */
        // Trueが返ること
        $this->assertTrue($this->object->doPaymentRequest($OrderExtension, $listParam, $PaymentExtension));

        /** @var YamatoOrderPayment $newYamatoOrderPayment */
        $newYamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済完了ページ表示用データが更新されていること
        $this->assertNotEquals($expected['memo02'], $newYamatoOrderPayment->getMemo02());
        // 決済状況が更新されていること
        $this->assertNotEquals($expected['memo04'], $newYamatoOrderPayment->getMemo04());
        // 決済データが更新されていること
        $this->assertNotEquals($expected['memo05'], $newYamatoOrderPayment->getMemo05());
        // 決済ログが更新されていること
        $this->assertNotEquals($expected['memo09'], $newYamatoOrderPayment->getMemo09());
    }

    function test_doPaymentRequest__通常受注__3Dセキュア未加入__falseが返ること__決済状況に変更がないこと()
    {
        // $_SERVERの設定
        $_SERVER['SERVER_PROTOCOL']  = 'HTTP/1.0';
        $_SERVER['HTTP_HOST'] = '';

        // ユーザー設定：オプションサービス 0：契約済み 1:未契約
        $userSettings = $this->PluginUtil->getUserSettings();
        $userSettings['use_option'] = 1;
        $this->PluginUtil->registerUserSettings($userSettings);

        // 支払方法設定：本人認証サービス(3Dセキュア) 0:利用しない 1:利用する
        $memo05 = $this->YamatoPaymentMethod->getMemo05();
        $memo05['TdFlag'] = 0;
        $this->YamatoPaymentMethod->setMemo05($memo05);

        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());
        $this->app['orm.em']->flush();

        $expected = array(
            // 受注情報から決済完了ページ表示用データを取得
            'memo02' => $YamatoOrderPayment->getMemo02(),
            // 受注情報から決済状況取得
            'memo04' => $YamatoOrderPayment->getMemo04(),
            // 受注情報から決済データ取得
            'memo05' => $YamatoOrderPayment->getMemo05(),
            // 受注情報から決済ログ取得
            'memo09' => $YamatoOrderPayment->getMemo09(),
        );

        // パラメータ情報作成
        $listParam = array(
            'info_use_threeD' => $this->const['YAMATO_3D_EXCLUDED'],
        );

        // 支払方法の設定情報を取得する
        $PaymentExtension = $this->PaymentUtil->getPaymentTypeConfig($Order->getPayment()->getId());

        /*
         * 決済モジュール 決済処理 クレジットカード（CreditClientService/BaseClientService）モック作成
         */
        // CreditClientService（BaseClientService）モック化
        $getResults = array(
            'returnDate' => date('YmdHms'),
            'errorCode' => 'Z019999999',
            'creditErrorCode' => 'C01',
        );
        $this->object = $this->createCreditClientService(false, $getResults);
        $this->object->error = array('エラーメッセージ');

        /*
         * クレジット決済実行
         */
        // Falseが返ること
        $this->assertFalse($this->object->doPaymentRequest($OrderExtension, $listParam, $PaymentExtension));

        /** @var YamatoOrderPayment $newYamatoOrderPayment */
        $newYamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済完了ページ表示用データが変更ないこと
        $this->assertEquals($expected['memo02'], $newYamatoOrderPayment->getMemo02());
        // 決済状況が変更ないこと
        $this->assertEquals($expected['memo04'], $newYamatoOrderPayment->getMemo04());
        // 決済データが更新されていること
        $this->assertNotEquals($expected['memo05'], $newYamatoOrderPayment->getMemo05());
        // 決済ログが更新されていること
        $this->assertNotEquals($expected['memo09'], $newYamatoOrderPayment->getMemo09());
    }

    function test_doPaymentRequest__オプションサービス受注__お預かり情報エラー__エラーメッセージが返ること()
    {
        // $_SERVERの設定
        $_SERVER['SERVER_PROTOCOL']  = 'HTTP/1.0';
        $_SERVER['HTTP_HOST'] = '';

        // ユーザー設定：オプションサービス 0：契約済み 1:未契約
        $userSettings = $this->PluginUtil->getUserSettings();
        $userSettings['use_option'] = 0;
        $this->PluginUtil->registerUserSettings($userSettings);

        // 支払方法設定：本人認証サービス(3Dセキュア) 0:利用しない 1:利用する
        $memo05 = $this->YamatoPaymentMethod->getMemo05();
        $memo05['TdFlag'] = 1;
        $this->YamatoPaymentMethod->setMemo05($memo05);

        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());
        $this->app['orm.em']->flush();

        // パラメータ情報作成
        $listParam = array(
            // オプションサービス区分
            // 購入時カード預かり 0：利用しない 1：利用する
            'register_card' => '1',
            // 預かりカードでの購入 0：利用しない 1：利用する
            'use_registed_card' => '1',
            // 予約商品購入 true：予約商品 false：通常商品
            'tpl_is_reserve' => true,

            // カード情報
            // card_key
            'card_key' => 1,
        );

        // 支払方法の設定情報を取得する
        $PaymentExtension = $this->PaymentUtil->getPaymentTypeConfig($Order->getPayment()->getId());

        /*
         * 決済モジュール 決済処理 クレジットカードのお預かり処理（MemberClientService）モック作成
         */
        // クレジットカードお預かり情報照会（doGetCard）
        $doGetCard = false;
        // MemberClientServiceモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService($doGetCard);

        /*
         * クレジット決済実行
         */
        try {
            // error.twigにレンダーされる
            $this->object->doPaymentRequest($OrderExtension, $listParam, $PaymentExtension);
        } catch (\Exception $e) {
            // レンダーされること（レンダーするとExceptionが発生する)
            $expected = '/Template "error.twig" is not defined./u';
            $this->assertRegExp($expected, $e->getMessage());
        }
    }

    protected function createCardData()
    {
        $results = array();
        $results['cardData'] = array(
            'card_key' => '1',
            'maskingCardNo' => '************1111',
            'cardExp' => '0528',
            'cardOwner' => 'KURONEKO YAMATO',
            'subscriptionFlg' => array(1 => '1'),
            'lastCreditDate' => date('Ymd'),
        );
        $results['cardUnit'] = 1;

        return $results;
    }

    private function createCreditClientService($sendOrderRequest = null, $getResults = null)
    {
        $mock = $this->getMock('Plugin\YamatoPayment\Service\Client\CreditClientService', array('sendRequest', 'getResults', 'getError'), array($this->app));
        $mock->expects($this->any())
            ->method('sendRequest')
            ->will($this->returnValue($sendOrderRequest));
        $mock->expects($this->any())
            ->method('getResults')
            ->will($this->returnValue($getResults));
        $mock->expects($this->any())
            ->method('getError')
            ->will($this->returnValue(array('エラーメッセージ')));

        return $mock;
    }

    private function createMemberClientService($doGetCard = null, $getResults = null)
    {
        $mock = $this->getMock('MemberClientService', array('doGetCard', 'getResults', 'getError'));
        $mock->expects($this->any())
            ->method('doGetCard')
            ->will($this->returnValue($doGetCard));
        $mock->expects($this->any())
            ->method('getResults')
            ->will($this->returnValue($getResults));
        $mock->expects($this->any())
            ->method('getError')
            ->will($this->returnValue(array('エラーメッセージ')));

        return $mock;
    }
}
