<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Web\Admin;

use Eccube\Entity\Csv;
use Eccube\Entity\Shipping;
use Plugin\YamatoPayment\Controller\Admin\OrderControllerExtension;
use Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip;

class OrderControllerExtensionTest extends AbstractAdminWebTestCase
{
    /** @var  OrderControllerExtension */
    var $object;

    protected $b2UserSettings;

    public function setUp()
    {
        parent::setUp();

        $this->object = new OrderControllerExtension();

        // enable_flg = 1に設定
        foreach($this->app['eccube.repository.csv']->findAll() as $csv){
            /** @var CSV $csv */
            $csv->setEnableFlg(1);
            $this->app['orm.em']->persist($csv);
        }
        $this->app['orm.em']->flush();

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
            // B2送り状種別設定
            // 発払い：0 コレクト：2 DM便：3 タイムサービス：4 着払い：5 メール便速達サービス：6 ネコポス：7 宅急便コンパクト：8 コンパクトコレクト：9
            'deliv_slip_type' => array(
                10 => 0,
                9 => 0,
                8 => 0,
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
            ),
            // B2クール便区分設定　通常：0 クール冷凍：1 クール冷蔵：2
            'cool_kb' => array(
                1 => 0,
                2 => 0,
                3 => 0,
            ),
            // B2配送時間コード設定
            // 空白：0 0812：1 1214：2 1416：3 1618：4 1820：5 2021：6 0010：7 0017：8
            'b2_delivtime_code' => array(
                1 => array(
                    1 => 0,
                    2 => 0,
                ),
            ),
        );

    }

    public function testExportOrder_受注CSVに追加項目が出力される()
    {
        // 受注データ作成
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataCredit($Order);
        // 受注商品出荷予定日データを作成
        $YamatoOrderScheduledShippingDate = $this->createYamatoOrderScheduledShippingDateData($Order);
        $shippingDate = $YamatoOrderScheduledShippingDate->getScheduledshippingDate();

        // 受注CSVをエクスポート
        $this->client->request(
            'GET',
            $this->app->path('admin_order_export_order')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // CSV文字コード変換
        $callback = function ($output) {
            return mb_convert_encoding($output, 'UTF-8', 'SJIS');
        };
        $this->setOutputCallback($callback);

        // CSV出力検証（『出荷予定日』がCSVに出力されること）
        $this->expectOutputRegex('/出荷予定日/u');
        // CSV出力検証（出荷予定日がCSVに出力されること）
        $this->expectOutputRegex('/' . $shippingDate . '/u');
    }

    public function testExportWebCollect_クレジットカード出荷登録CSVエクスポート正常処理()
    {
        // 受注データ作成
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataCredit($Order);
        // 配送伝票番号データを作成
        $YamatoShippingDelivSlips = $this->createYamatoShippingDelivSlip($Order);
        /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
        $YamatoShippingDelivSlip = $YamatoShippingDelivSlips[0];
        $delivSlip = $YamatoShippingDelivSlip->getDelivSlipNumber();

        // クレジットカード出荷登録CSVをエクスポート
        $this->client->request(
            'GET',
            $this->app->path('admin_order_export_web_collect')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // CSV文字コード変換
        $callback = function ($output) {
            return mb_convert_encoding($output, 'UTF-8', 'SJIS');
        };
        $this->setOutputCallback($callback);

        // CSV出力検証（送り状番号がCSVに出力されること）
        $this->expectOutputRegex('/,' . $delivSlip . ',/u');

        // 受注ステータスがクレジットカード出荷登録済みに更新されること
        $expected = $this->const['ORDER_SHIPPING_REGISTERED'];
        $this->assertEquals($expected, $Order->getOrderStatus()->getId());
    }

    public function testExportWebCollect_配送業者IDが存在しない場合_3項目目は空白になる()
    {
        // 受注データ作成
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataCredit($Order);

        foreach ($Order->getShippings() as $Shipping) {
            /** @var Shipping $Shipping */
            $Shipping->setDelivery(null);
            $this->app['orm.em']->persist($Shipping);
        }
        $this->app['orm.em']->flush();

        // 配送伝票番号データを作成
        $this->createYamatoShippingDelivSlip($Order);
        /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */

        // クレジットカード出荷登録CSVをエクスポート
        $this->client->request(
            'GET',
            $this->app->path('admin_order_export_web_collect')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エクスポートしたCSVファイルをUTF-8に変換
        $csv = mb_convert_encoding($this->getActualOutput(), 'UTF-8', 'SJIS');
        $csv = explode("\n", $csv);

        // 該当する受注IDを検索
        $key = array_search($Order->getId(), $csv);
        $csv = explode(",", $csv[$key]);

        // 3項目目が空白なこと
        $this->assertEmpty($csv[2]);
    }

    public function testExportB2_正常処理()
    {
        // B2用UserSettingに初期値を登録
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // 受注データ作成
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataCredit($Order);
        // 配送伝票番号データを作成
        $this->createYamatoShippingDelivSlip($Order);

        // B2CSVをエクスポート
        $this->client->request(
            'GET',
            $this->app->path('admin_order_export_b2')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エクスポートしたCSVファイルをUTF-8に変換
        $csv = mb_convert_encoding($this->getActualOutput(), 'UTF-8', 'SJIS');
        $csv = explode("\n", $csv);
        // ヘッダの取り出し
        $header = explode(',', $csv[0]);

        // ヘッダ項目数が95であること
        $this->assertEquals(95, count($header));
        // ヘッダが出力されていること
        $this->assertContains('お客様管理番号', $header);
    }

    public function testExportB2_B2設定情報が無ければエラーメッセージが返る()
    {
        // B2用UserSettingにnullを設定
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings(null);

        // B2CSVをエクスポート
        $crawler = $this->client->request(
            'GET',
            $this->app->path('admin_order_export_b2')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エラー画面が表示されること
        $this->assertContains('B2設定情報未登録エラー', $crawler->filterXPath('//h1')->text());
    }

    public function testExportB2_B2設定情報header_outputを0にするとヘッダ行が出力されない()
    {
        // ヘッダ行出力しない
        $this->b2UserSettings['header_output'] = 0;
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($this->b2UserSettings);

        // B2CSVエクスポート
        $this->client->request(
            'GET',
            $this->app->path('admin_order_export_b2')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エクスポートしたCSVファイルをUTF-8に変換
        $csv = mb_convert_encoding($this->getActualOutput(), 'UTF-8', 'SJIS');

        // ヘッダが出力されていないこと
        $this->assertNotContains('お客様管理番号', $csv);
    }

}
