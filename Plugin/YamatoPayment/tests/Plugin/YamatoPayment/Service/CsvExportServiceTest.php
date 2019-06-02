<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Service;

use Eccube\Entity\BaseInfo;
use Eccube\Entity\Help;
use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\ShipmentItem;
use Eccube\Entity\Shipping;

class CsvExportServiceTest extends AbstractServiceTestCase
{
    /** @var array */
    var $b2UserSettings;

    /**
     * @var CsvExportService
     */
    protected $object;

    function setUp()
    {
        parent::setUp();
        $this->object = $this->app['yamato_payment.service.csv.export'];

        // B2用UserSetting初期設定
        $this->b2UserSettings = array(
            // 基本設定　ご請求先お客様コード
            'claim_customer_code' => 111111111111,
            // 基本設定　ご請求先分類コード
            'claim_type_code' => null,
            // 基本設定　運賃管理番号
            'transportation_no' => 01,
            // B2動作設定　一行目タイトル行
            'header_output' => 1,
            // B2動作設定　電話番号　ハイフン有：1 無：0
            'tel_hyphenation' => 1,
            // B2動作設定　郵便番号　ハイフン有：1 無：0
            'zip_hyphenation' => 1,
            // B2動作設定　お届け予定eメール 利用しない：0 利用する：1
            'service_deliv_mail_enable' => 0,
            // B2動作設定　お届け予定eメールメッセージ
            'service_deliv_mail_message' => null,
            // B2動作設定　お届け完了eメール 利用しない：0 利用する：1
            'service_complete_mail_enable' => 0,
            // B2動作設定　お届け完了eメールメッセージ
            'service_complete_mail_message' => null,
            // B2動作設定　ご依頼主出力 注文者情報：0 SHOPマスター基本情報：1 特定商取引法：2
            'output_order_type' => 0,
            // B2動作設定　投函予定eメール 利用しない：0 利用する：1
            'posting_plan_mail_enable' => 0,
            // B2動作設定　投函予定eメールメッセージ
            'posting_plan_mail_message' => null,
            // B2動作設定　投函完了eメール 利用しない：0 利用する：1
            'posting_complete_deliv_mail_enable' => 0,
            // B2動作設定　投函完了eメールメッセージ
            'posting_complete_deliv_mail_message' => null,
            /*
             * B2送り状種別設定（支払方法ID => B2送り状種別ID）
             * 0:発払い 2:コレクト 3:DM便 4:タイムサービス 5:着払い 6:メール便速達サービス 7:ネコポス 8:宅急便コンパクト 9:コンパクトコレクト
             */
            'deliv_slip_type' => array(
                10 => 0,
                9 => 0,
                8 => 0,
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
            ),
            /*
             * B2クール便区分設定（配送業者ID => B2クール便区分ID）
             * 0:通常 1:クール冷凍 2:クール冷蔵
             */
            'cool_kb' => array(
                1 => 0,
                2 => 0,
                3 => 0,
            ),
            /*
             * B2配送時間コード設定（配送時間ID => B2配送時間コードID）
             * 空白：0 0812：1 1214：2 1416：3 1618：4 1820：5 2021：6 0010：7 0017：8
             */
            'b2_delivtime_code' => array(
                1 => array(
                    1 => 0,
                    2 => 0,
                ),
            ),
        );

    }

    public function testCreateB2CsvData_正常データ()
    {
        // B2用UserSetting設定
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // 受注情報を作成
        $Order = $this->createOrderData();
        // 配送伝票番号データを作成
        $YamatoShippingDelivSlips = $this->createYamatoShippingDelivSlip($Order);

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        for ($i = 0; $i < $Shippings->count(); $i++) {
            // B2CSVデータ作成
            $csvData = $this->object->createB2CsvData($Order, $Shippings->get($i));

            $this->assertEquals('0', $csvData[1]);
            $this->assertEquals('0', $csvData[2]);
            $this->assertEquals($YamatoShippingDelivSlips[$i]['deliv_slip_number'], $csvData[3]);
        }
    }

    public function testCreateB2CsvData_送り状種別設定が未登録の場合_CSV出力データの2項目目は空白が返る()
    {
        // B2用UserSetting設定
        $this->b2UserSettings['deliv_slip_type'] = array();
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // 受注情報作成
        $Order = $this->createOrderData();

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        foreach ($Shippings as $Shipping) {
            // B2CSVデータ作成
            $csvData = $this->object->createB2CsvData($Order, $Shipping);
            $this->assertEquals('', $csvData[1]);
        }
    }

    public function testCreateB2CsvData_クール区分設定が未登録の場合_CSV出力データの3項目目は空白が返る()
    {
        // B2用UserSetting設定
        $this->b2UserSettings['cool_kb'] = array();
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // 受注情報を作成
        $Order = $this->createOrderData();

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        foreach ($Shippings as $Shipping) {
            // B2CSVデータ作成
            $csvData = $this->object->createB2CsvData($Order, $Shipping);
            $this->assertEquals('', $csvData[2]);
        }

    }

    public function testCreateB2CsvData_お届け予定日が設定されている場合_CSV出力データの6項目目はお届け予定日が返る()
    {
        // 受注情報を作成
        $Order = $this->createOrderData();

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        foreach ($Shippings as $Shipping) {
            // お届け予定日設定
            /** @var Shipping $Shipping */
            $Shipping->setShippingDeliveryDate(new \DateTime(date('ymd')));

            // B2CSVデータ作成
            $csvData = $this->object->createB2CsvData($Order, $Shipping);
            $this->assertEquals(date_format($Shipping->getShippingDeliveryDate(), 'Y/m/d'), $csvData[5]);
        }
    }

    public function testCreateB2CsvData_配達時間帯が設定されている場合_CSV出力データの7項目目は配達時間帯が返る()
    {
        // B2用UserSetting設定
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // 受注情報を作成
        $Order = $this->createOrderData();

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        foreach ($Shippings as $Shipping) {
            // 配達時間帯設定
            $DeriveryTime = $this->app['eccube.repository.delivery_time']->find(1);
            /** @var Shipping $Shipping */
            $Shipping->setDeliveryTime($DeriveryTime);

            // B2CSVデータ作成
            $csvData = $this->object->createB2CsvData($Order, $Shipping);

            $delivTimeCode = $this->app['yamato_payment.util.payment']->getDelivTimeCode();
            $delivery_id = $Shipping->getDelivery()->getId();
            $delivery_time_id = $Shipping->getDeliveryTime()->getId();
            $this->assertEquals($delivTimeCode[$this->b2UserSettings['b2_delivtime_code'][$delivery_id][$delivery_time_id]], $csvData[6]);
        }
    }

    public function testCreateB2CsvData_お届け先名称略カナとご依頼主略称カナが全角カナの場合_CSV出力データの17項目と26項目目は半角カナが返る()
    {
        // B2用UserSetting設定
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // 受注情報を作成
        $Order = $this->createOrderData();

        // ご依頼主名称略カナに全角カナを設定
        $Order->setKana01('アイウエオ');
        $Order->setKana02('カキクケコ');

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        for ($i = 0; $i < $Shippings->count(); $i++) {

            // お届け先名称略カナに全角カナを設定
            $Shipping = $Shippings->get($i);
            $Shippings->get($i)->setKana01('アイウエオ');
            $Shippings->get($i)->setKana02('カキクケコ');

            // B2CSVデータ作成
            $csvData = $this->object->createB2CsvData($Order, $Shipping);

            $this->assertEquals('ｱｲｳｴｵ ｶｷｸｹｺ', $csvData[16]);
            $this->assertEquals('ｱｲｳｴｵ ｶｷｸｹｺ', $csvData[25]);
        }
    }

    public function testCreateB2CsvData_ご依頼主コード設定が1の場合_CSV出力データの19項目目から26項目目はShopマスターの情報が返る()
    {
        /*
         * ご依頼主コード設定が1:Shopマスターの場合
         */
        // B2用UserSetting設定
        $this->b2UserSettings['output_order_type'] = 1;
        $this->b2UserSettings['tel_hyphenation'] = '1';
        $this->b2UserSettings['zip_hyphenation'] = '1';
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // ハイフン設定を取得
        $tel_hyphenation = ($this->b2UserSettings['tel_hyphenation'] == '1') ? '-' : '';
        $zip_hyphenation = ($this->b2UserSettings['zip_hyphenation'] == '1') ? '-' : '';

        // SHOPマスター基本情報を取得
        /** @var BaseInfo $BaseInfo */
        $BaseInfo = $this->app['eccube.repository.base_info']->get();
        $BaseInfo
            ->setCompanyName('会社名')
            ->setCompanyKana('カイシャメイ')
            ->setZip01('000')
            ->setZip01('000')
            ->setZip02('0000')
            ->setPref($this->app['eccube.repository.master.pref']->find(1))
            ->setAddr01('千代田区')
            ->setAddr02('1-1')
            ->setTel01('111')
            ->setTel02('1112')
            ->setTel03('1113');

        $this->app['orm.em']->flush();

        // 受注情報を作成
        $Order = $this->createOrderData();

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        foreach ($Shippings as $Shipping) {
            // B2CSVデータ作成
            $csvData = $this->object->createB2CsvData($Order, $Shipping);
            // SHOPマスター基本情報が返る
            $this->assertEquals('', $csvData[18]);
            $this->assertEquals($BaseInfo->getTel01() . $tel_hyphenation . $BaseInfo->getTel02() . $tel_hyphenation . $BaseInfo->getTel03(), $csvData[19]);
            $this->assertEquals('', $csvData[20]);
            $this->assertEquals($BaseInfo->getZip01() . $zip_hyphenation . $BaseInfo->getZip02(), $csvData[21]);
            $this->assertEquals($BaseInfo->getPref()->getName() . $BaseInfo->getAddr01(), $csvData[22]);
            $this->assertEquals($BaseInfo->getAddr02(), $csvData[23]);
            $this->assertEquals($BaseInfo->getCompanyName(), $csvData[24]);
            $this->assertEquals('ｶｲｼｬﾒｲ', $csvData[25]);
        }

    }

    public function testCreateB2CsvData_ご依頼主コード設定が2の場合_CSV出力データの19項目目から26項目目は特定商取引の情報が返る()
    {
        /*
         * ご依頼主コード設定が2:特定商取引法の場合
         */
        // B2用UserSetting設定
        $this->b2UserSettings['output_order_type'] = 2;
        $this->b2UserSettings['tel_hyphenation'] = '1';
        $this->b2UserSettings['zip_hyphenation'] = '1';
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // ハイフン設定を取得
        $tel_hyphenation = ($this->b2UserSettings['tel_hyphenation'] == '1') ? '-' : '';
        $zip_hyphenation = ($this->b2UserSettings['zip_hyphenation'] == '1') ? '-' : '';

        // 特定商取引法を取得
        /** @var Help $Help */
        $Help = $this->app['eccube.repository.help']->get();
        $Help
            ->setLawCompany('会社名')
            ->setLawZip01('000')
            ->setLawZip01('000')
            ->setLawZip02('0000')
            ->setLawPref($this->app['eccube.repository.master.pref']->find(1))
            ->setLawAddr01('千代田区')
            ->setLawAddr02('1-1')
            ->setLawTel01('111')
            ->setLawTel02('1112')
            ->setLawTel03('1113');

        $this->app['orm.em']->flush();

        // 受注情報を作成
        $Order = $this->createOrderData();

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        foreach ($Shippings as $Shipping) {
            // B2CSVデータ作成
            $csvData = $this->object->createB2CsvData($Order, $Shipping);
            // 特定商取引法が返る
            $this->assertEquals('', $csvData[18]);
            $this->assertEquals($Help->getLawTel01() . $tel_hyphenation . $Help->getLawTel02() . $tel_hyphenation . $Help->getLawTel03(), $csvData[19]);
            $this->assertEquals('', $csvData[20]);
            $this->assertEquals($Help->getLawZip01() . $zip_hyphenation . $Help->getLawZip02(), $csvData[21]);
            $this->assertEquals($Help->getLawPref()->getName() . $Help->getLawAddr01(), $csvData[22]);
            $this->assertEquals($Help->getLawAddr02(), $csvData[23]);
            $this->assertEquals($Help->getLawCompany(), $csvData[24]);
            $this->assertEquals('', $csvData[25]);
        }
    }

    public function testCreateB2CsvData_受注商品が2つの場合_CSV出力データの27項目目から30項目目は品目名と品目コードが返る()
    {
        // 受注情報を作成
        $Order = $this->createOrderData();

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        // 受注商品情報を作成(品名コード)
        foreach ($Shippings as $Shipping) {
            /** @var Shipping $Shipping */
            foreach ($Shipping->getShipmentItems() as $ShipmentItem) {
                /** @var ShipmentItem $ShipmentItem */
                $ShipmentItem->setProductCode(1);
            }

            // 追加用受注情報・出荷情報を作成
            for($i=0; $i<2; $i++) {
                $addOrder = $this->createOrderData();
                foreach ($addOrder->getShippings() as $addShipping) {
                    // 追加受注商品情報を取得
                    $addShipmentItems = $addShipping->getShipmentItems();
                    foreach ($addShipmentItems as $addShipmentItem) {
                        /** @var ShipmentItem $addShipmentItem */
                        // 追加受注商品情報を作成(品名コード)
                        $addShipmentItem->setProductCode(2);
                        // 受注商品情報を追加
                        $Shipping->addShipmentItem($addShipmentItem);
                    }
                }
            }

            // B2CSVデータ作成
            $csvData = $this->object->createB2CsvData($Order, $Shipping);
            $this->assertNotEmpty($csvData[26]);
            $this->assertNotEmpty($csvData[27]);
            $this->assertNotEmpty($csvData[28]);
            $this->assertNotEmpty($csvData[29]);
        }
    }

    public function testCreateB2CsvData_B2送り状種別設定がコレクトの場合_CSV出力データの34項目はコレクト代金引換額と35項目目はコレクト内消費税額が返る()
    {
        // B2用UserSetting設定
        $this->b2UserSettings['deliv_slip_type'] = array(
                10 => 2,
                9 => 2,
                8 => 2,
                1 => 2,
                2 => 2,
                3 => 2,
                4 => 2,
            );

        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // 受注情報作成
        $Order = $this->createOrderData();

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        foreach ($Shippings as $Shipping) {
            // B2CSVデータ作成
            $csvData = $this->object->createB2CsvData($Order, $Shipping);

            // コレクト代金引換額が返ること
            $extpected = $Order->getPaymentTotal();
            $this->assertEquals($extpected, $csvData[33]);

            // コレクト内消費税額が返ること
            $extpected = $Order->getTax();
            $this->assertEquals($extpected, $csvData[34]);
        }
    }

    public function testCreateB2CsvData_支払方法がクレジットカード決済の場合_43項目目は1が返る()
    {
        // B2用UserSetting設定
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // 受注情報を作成
        $Order = $this->createOrderData();

        // クレジットカード決済の受注情報を作成
        $this->createOrderPaymentDataCredit($Order);

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        for ($i = 0; $i < $Shippings->count(); $i++) {
            // B2CSVデータ作成
            $csvData = $this->object->createB2CsvData($Order, $Shippings->get($i));

            $this->assertEquals('1', $csvData[42]);
        }
    }

    public function testCreateB2CsvData_お届け予定eメールを利用するの場合_CSV出力データの49項目から51項目目はお届け予定eメールの情報が返る()
    {
        // B2用UserSetting設定
        $this->b2UserSettings['service_deliv_mail_enable'] = 1;
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // 受注情報作成
        $Order = $this->createOrderData();

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        /*
         * 入力機種がPCの場合
         */
        // 入力機種を設定
        $DeviceType = $this->app['eccube.repository.master.device_type']->find(DeviceType::DEVICE_TYPE_PC);
        $Order->setDeviceType($DeviceType);

        foreach ($Shippings as $Shipping) {
            // B2CSVデータ作成
            $csvData = $this->object->createB2CsvData($Order, $Shipping);

            // お届け予定eメールアドレスが返ること
            $extpected = $Order->getEmail();
            $this->assertEquals($extpected, $csvData[48]);

            // 入力機種が返ること
            $this->assertEquals(1, $csvData[49]);

            // お届け予定eメールメッセージが返ること
            $extpected = $this->b2UserSettings['service_deliv_mail_message'];
            $this->assertEquals($extpected, $csvData[50]);
        }
    }

    public function testCreateB2CsvData_お届け完了eメールを利用する場合_CSV出力データの53項目と54項目目はお届け完了eメールの情報が返る()
    {
        // B2用UserSetting設定
        $this->b2UserSettings['service_complete_mail_enable'] = 1;
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // 受注情報作成
        $Order = $this->createOrderData();

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        foreach ($Shippings as $Shipping) {
            // B2CSVデータ作成
            $csvData = $this->object->createB2CsvData($Order, $Shipping);

            // お届け予定eメールアドレスが返ること
            $extpected = $Order->getEmail();
            $this->assertEquals($extpected, $csvData[52]);

            // お届け予定eメールメッセージが返ること
            $extpected = $this->b2UserSettings['service_complete_mail_message'];
            $this->assertEquals($extpected, $csvData[53]);
        }
    }

    public function testCreateB2CsvData_B2送り状種別設定がネコポス_投函予定メールを利用する場合_CSV出力データの87項目から89項目目は投函予定メールの情報が返る()
    {
        // B2用UserSetting設定
        $this->b2UserSettings['posting_plan_mail_enable'] = 1;
        $this->b2UserSettings['deliv_slip_type'] = array(
                10 => 7,
                9 => 7,
                8 => 7,
                1 => 7,
                2 => 7,
                3 => 7,
                4 => 7,
            );
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // 受注情報作成
        $Order = $this->createOrderData();

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        /*
         * PC宛の場合
         */
        // 入力機種を設定
        $DeviceType = $this->app['eccube.repository.master.device_type']->find(DeviceType::DEVICE_TYPE_PC);
        $Order->setDeviceType($DeviceType);

        foreach ($Shippings as $Shipping) {
            // B2CSVデータ作成
            $csvData = $this->object->createB2CsvData($Order, $Shipping);

            // 投函予定メール利用区分が返ること
            $this->assertEquals(1, $csvData[86]);

            // 投函予定eメールアドレスが返ること
            $extpected = $Order->getEmail();
            $this->assertEquals($extpected, $csvData[87]);

            // 投函予定eメールメッセージが返ること
            $extpected = $this->b2UserSettings['posting_plan_mail_message'];
            $this->assertEquals($extpected, $csvData[88]);
        }
    }

    public function testCreateB2CsvData_B2送り状種別設定がネコポス_投函完了メールを利用する場合_CSV出力データの93項目から95項目目は投函完了メールの情報が返る()
    {
        // B2用UserSetting設定
        $this->b2UserSettings['posting_complete_deliv_mail_enable'] = 1;
        $this->b2UserSettings['deliv_slip_type'] = array(
                10 => 7,
                9 => 7,
                8 => 7,
                1 => 7,
                2 => 7,
                3 => 7,
                4 => 7,
            );
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // 受注情報作成
        $Order = $this->createOrderData();

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        /*
         * PC宛の場合
         */
        // 入力機種を設定
        $DeviceType = $this->app['eccube.repository.master.device_type']->find(DeviceType::DEVICE_TYPE_PC);
        $Order->setDeviceType($DeviceType);

        foreach ($Shippings as $Shipping) {
            // B2CSVデータ作成
            $csvData = $this->object->createB2CsvData($Order, $Shipping);

            // 投函完了メール利用区分が返ること
            $this->assertEquals(1, $csvData[92]);

            // 投函完了eメールアドレスが返ること
            $extpected = $Order->getEmail();
            $this->assertEquals($extpected, $csvData[93]);

            // 投函完了eメールメッセージが返ること
            $extpected = $this->b2UserSettings['posting_complete_deliv_mail_message'];
            $this->assertEquals($extpected, $csvData[94]);
        }
    }
}
