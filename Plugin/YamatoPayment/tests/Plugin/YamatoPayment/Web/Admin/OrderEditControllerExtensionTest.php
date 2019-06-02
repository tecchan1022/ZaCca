<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */

namespace Plugin\YamatoPayment\Web\Admin;

use Eccube\Application;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Security\Acl\Exception\Exception;

class OrderEditControllerExtensionTest extends AbstractAdminWebTestCase
{
    protected $subData;

    public function setUp()
    {
        parent::setUp();

        $this->subData = array(
            'ycf_ship_ymd' => 1,
            'ycf_send_div' => 0,
        );

    }

    public function testExportBuyer_正常処理()
    {
        // プラグイン設定のSubDataを設定する
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->subData);

        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer('dummy-user@example.com'));

        // 買手情報一括登録CSVエクスポート実行
        $this->client->request(
            'GET',
            $this->app->url('admin_order_export_buyer', array('id' => $Order->getId()))
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エクスポートしたCSVファイルをUTF-8に変換
        $csv = mb_convert_encoding($this->getActualOutput(), 'UTF-8', 'SJIS');
        $csv = explode("\n", $csv);
        // ヘッダーの取り出し
        $header = explode(',', $csv[0]);

        // 1行目に『,』が(項目数-1)個含まれる CSV が出力されること 項目数105個
        $this->assertEquals(106, count($header));

        // 2行目に「,今日の日付+出荷予定日,」が含まれること
        $expected = date('Ymd', strtotime('+' . $this->subData['ycf_ship_ymd'] . ' day'));
        $this->assertContains($expected, $csv[1]);
    }

    public function testExportBuyer_URLパラメータに受注IDがないならエラーが発生する()
    {
        try {
            // 買手情報一括登録CSVエクスポート実行
            $this->client->request(
                'GET',
                $this->app->url('admin_order_export_buyer')
            );
            $this->fail('エラー画面に遷移しない');
        } catch(MissingMandatoryParametersException $e) {
            // ServiceProviderによるエラー
            $this->assertTrue(true);
        } catch(Exception $e) {
            $this->fail('その他のエラー');
        }

    }

    public function testExportBuyer_受注データが存在しないならエラーが発生する()
    {
        try {
            // 買手情報一括登録CSVエクスポート実行
            $this->client->request(
                'GET',
                $this->app->url('admin_order_export_buyer', array('id' => 0))
            );
            $this->fail('エラー画面に遷移しない');
        } catch(HttpException $e) {
            $this->assertTrue(true);
        } catch(Exception $e) {
            $this->fail('その他のエラー');
        }

    }

    public function testExportBuyer_出荷予定日がnullの場合CSVファイルの2行目に今日の日付が入る()
    {
        // プラグイン設定の出荷予定日をnullに設定する
        $this->subData['ycf_ship_ymd'] = null;
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->subData);

        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer('dummy-user@example.com'));

        // 買手情報一括登録CSVエクスポート実行
        $this->client->request(
            'GET',
            $this->app->url('admin_order_export_buyer', array('id' => $Order->getId()))
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エクスポートしたCSVファイルをUTF-8に変換
        $csv = mb_convert_encoding($this->getActualOutput(), 'UTF-8', 'SJIS');
        $csv = explode("\n", $csv);

        // 2行目に「,今日の日付,」が含まれること
        $expected = date('Ymd');
        $this->assertContains($expected, $csv[1]);
    }

    public function testExportBuyer_氏名カナが全角の場合CSVファイルの2行目は半角カナが返る()
    {
        // プラグイン設定の出荷予定日をnullに設定する
        $this->subData['ycf_ship_ymd'] = null;
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->subData);

        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer('dummy-user@example.com'));
        $Order->setKana01('アイウエオ');
        $Order->setKana02('カキクケコ');
        $this->app['orm.em']->persist($Order);
        $this->app['orm.em']->flush();

        // 買手情報一括登録CSVエクスポート実行
        $this->client->request(
            'GET',
            $this->app->url('admin_order_export_buyer', array('id' => $Order->getId()))
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エクスポートしたCSVファイルをUTF-8に変換
        $csv = mb_convert_encoding($this->getActualOutput(), 'UTF-8', 'SJIS');
        $csv = explode("\n", $csv);

        // 2行目に「半角カナ」が含まれること
        $this->assertContains('ｱｲｳｴｵ', $csv[1]);
    }

}
