<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Web\Admin;

use Eccube\Entity\Delivery;
use Eccube\Entity\DeliveryTime;
use Eccube\Entity\Payment;
use Plugin\YamatoPayment\Controller\Admin\PluginB2ConfigController;

class PluginB2ConfigControllerTest extends AbstractAdminWebTestCase
{
    /** @var  PluginB2ConfigController */
    var $object;

    protected $b2UserSettings;
    protected $b2PaymentTypes;
    protected $b2DeliveryTypes;

    public function setUp()
    {
        parent::setUp();

        $this->object = new PluginB2ConfigController();

        // B2用UserSettingを取得
        $this->b2UserSettings = array(
            // 基本設定　ご請求先お客様コード
            'claim_customer_code' => 111111111111,
            // 基本設定　運賃管理番号
            'transportation_no' => 01,
            // B2動作設定　一行目タイトル行
            'header_output' => 1,
        );

        // 支払い方法マスタ取得
        $Payments = $this->app['eccube.repository.payment']->findBy(
            array('del_flg' => 0),
            array('rank' => 'DESC')
        );

        $this->b2PaymentTypes = array();
        foreach($Payments as $Payment){
            /** @var Payment $Payment */
            // B2用UserSetting「B2送り状種別設定」を設定
            // 発払い：0 コレクト：2 DM便：3 タイムサービス：4 着払い：5 メール便速達サービス：6 ネコポス：7 宅急便コンパクト：8 コンパクトコレクト：9
            $this->b2UserSettings['deliv_slip_type'][$Payment->getId()] = 0;

            // B2送り状種別設定フォーム作成
            $b2PaymentType = array();
            $b2PaymentType['payment_id'] = $Payment->getId();
            $b2PaymentType['payment_method'] = $Payment->getMethod();
            $b2PaymentType['deliv_slip_type'] = $this->b2UserSettings['deliv_slip_type'][$Payment->getId()];
            $this->b2PaymentTypes[] = $b2PaymentType;
        }

        // 配送方法マスタ取得
        $Deliveries = $this->app['eccube.repository.delivery']->findBy(
            array('del_flg' => 0),
            array('rank' => 'ASC')
        );


        $this->b2DeliveryTypes = array();
        foreach ($Deliveries as $Delivery) {
            /** @var Delivery $Delivery */

            // B2用UserSetting「B2クール便区分設定」を設定
            // B2クール便区分設定　通常：0 クール冷凍：1 クール冷蔵：2
            $this->b2UserSettings['cool_kb'][$Delivery->getId()] = 0;

            // B2用UserSetting「B2配送時間コード設定」を設定
            // 空白：0 0812：1 1214：2 1416：3 1618：4 1820：5 2021：6 0010：7 0017：8
            $DeliveryTimes = $this->app['eccube.repository.delivery_time']->findBy(array('Delivery' => $Delivery));
            if (!empty($DeliveryTimes)) {
                foreach ($DeliveryTimes as $DeliveryTime) {
                    /** @var DeliveryTime $DeliveryTime */
                    $this->b2UserSettings['b2_delivtime_code'][$Delivery->getId()][$DeliveryTime->getId()] = 0;
                }
            }

            // B2用UserSetting「B2配送サービスコード設定」を設定
            // ヤマト配送：0 ヤマト配送以外：99
            $this->b2UserSettings['delivery_service_code'][$Delivery->getId()] = 0;

            // B2クール便区分設定フォーム作成
            // B2配送時間コード設定フォーム作成
            // B2配送サービスコード設定フォーム作成
            $b2DeliveryType = array();
            $b2DeliveryType['delivery_id'] = $Delivery->getId();
            $b2DeliveryType['delivery_name'] = $Delivery->getName();
            $b2DeliveryType['cool_kb'] = $this->b2UserSettings['cool_kb'][$Delivery->getId()];
            $b2DeliveryType['delivery_service_code'] = $this->b2UserSettings['delivery_service_code'][$Delivery->getId()];
            $b2DeliveryType['b2_delivtime_code'] = array();

            $DeliveryTimes = $Delivery->getDeliveryTimes();
            for ($time_index = 0; $time_index < count($DeliveryTimes); $time_index++) {
                /** @var DeliveryTime $DeliveryTime */
                $DeliveryTime = $DeliveryTimes[$time_index];

                $b2DeliveryTimeType = array();
                $b2DeliveryTimeType['delivery_time_id'] = $DeliveryTime->getId();
                $b2DeliveryTimeType['delivery_time'] = '配送時間' . ($time_index + 1) . ' ' . $DeliveryTime->getDeliveryTime();
                $b2DeliveryTimeType['b2_delivtime_code'] = $this->b2UserSettings['b2_delivtime_code'][$Delivery->getId()][$DeliveryTime->getId()];
                $b2DeliveryType['b2_delivtime_code'][] = $b2DeliveryTimeType;
            }
            $this->b2DeliveryTypes[] = $b2DeliveryType;
        }
    }

    protected function createFormData($b2UserSettings)
    {
        $form = array(
            '_token' => 'dummy',
            // ご請求先顧客コード
            'claim_customer_code' => $b2UserSettings['claim_customer_code'],
            // 運賃管理番号
            'transportation_no' => $b2UserSettings['transportation_no'],
            // 一行目タイトル行
            'header_output' => $b2UserSettings['header_output'],
            // B2送り状種別設定フォーム
            'b2_payment_type' => $this->b2PaymentTypes,
            // B2クール便区分設定フォーム
            // B2配送時間コード設定フォーム
            // B2配送サービスコード設定フォーム
            'b2_delivery_type' => $this->b2DeliveryTypes,
        );
        return $form;
    }

    public function testEdit__初期表示()
    {
        // B2用UserSettingに初期値を登録
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // フォームの作成
        $form = $this->createFormData($this->b2UserSettings);

        $this->client->request(
            'GET',
            $this->app->path('plugin_YamatoPayment_b2_config')
            , array('yamato_b2_config' => $form)
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testEdit__登録処理__True()
    {
        // B2用UserSettingに初期値を登録
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // フォームの作成
        $form = $this->createFormData($this->b2UserSettings);

        $crawler = $this->client->request(
            'POST',
            $this->app->url('plugin_YamatoPayment_b2_config'),
            array('yamato_b2_config' => $form)
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 登録が完了すること
        $this->assertRegexp('/登録が完了しました。/u',
            $crawler->filter('div.alert-success')->text());
    }

    public function testEdit__登録処理__False()
    {
        // 基本設定　ご請求先お客様コードを削除する
        $this->b2UserSettings['claim_customer_code'] = null;
        // B2用UserSettingに初期値を登録
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // フォームの作成
        $form = $this->createFormData($this->b2UserSettings);

        $crawler = $this->client->request(
            'POST',
            $this->app->url('plugin_YamatoPayment_b2_config')
            , array('yamato_b2_config' => $form)
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エラーメッセージを取得すること
        $this->assertRegexp('/登録できませんでした。/u',
            $crawler->filter('div.alert-danger')->text());
    }

    public function testProcessSetUserSettingsData__DB登録用のデータが返る()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'processSetUserSettingsData');
        $method->setAccessible(true);

        // フォームの作成
        $formData = $this->createFormData($this->b2UserSettings);

        // $formData['b2_payment_type']、$formData['b2_delivery_type']が存在することを確認
        $this->assertTrue(isset($formData['b2_payment_type']));
        $this->assertTrue(isset($formData['b2_delivery_type']));

        // $formData['deliv_slip_type']、$formData['cool_kb'] 、$formData['delivery_service_code']、
        //$formData['b2_delivtime_code']が存在しないことを確認
        $this->assertFalse(isset($formData['deliv_slip_type']));
        $this->assertFalse(isset($formData['cool_kb']));
        $this->assertFalse(isset($formData['delivery_service_code']));
        $this->assertFalse(isset($formData['b2_delivtime_code']));

        $formData = $method->invoke($this->object, $formData);

        // $formData['b2_payment_type']、$formData['b2_delivery_type']が削除されていること
        $this->assertFalse(isset($formData['b2_payment_type']));
        $this->assertFalse(isset($formData['b2_delivery_type']));

        // $formData['deliv_slip_type']、$formData['cool_kb'] 、$formData['delivery_service_code']、
        //$formData['b2_delivtime_code']が追加されていること
        $this->assertTrue(isset($formData['deliv_slip_type']));
        $this->assertTrue(isset($formData['cool_kb']));
        $this->assertTrue(isset($formData['delivery_service_code']));
        $this->assertTrue(isset($formData['b2_delivtime_code']));
    }
}
