<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */

namespace Plugin\YamatoPayment\Web\Admin;

use Eccube\Application;
use Eccube\Entity\Order;
use Eccube\Entity\OrderDetail;
use Eccube\Entity\Shipping;
use Plugin\YamatoPayment\Controller\Admin\OrderB2Controller;
use Plugin\YamatoPayment\Entity\YamatoOrderPayment;
use Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class OrderB2ControllerTest extends AbstractAdminWebTestCase
{
    protected $filepath;

    /** @var  OrderB2Controller */
    var $object;

    public function setUp()
    {
        parent::setUp();
        $this->filepath = $this->app['config']['root_dir']  . '/app/cache/order_b2.csv';

        $this->object = new OrderB2Controller();

        // 設定値書き換え
        $pluginUtil = $this->app['yamato_payment.util.plugin'];
        $b2UserSettings = $pluginUtil->getB2UserSettings();
        // B2フォーマット(95項目)
        $b2UserSettings['use_b2_format'] = '1';
        // 取込時出荷情報登録API 1:利用する
        $b2UserSettings['shpping_info_regist'] = '1';
        // B2CSV一行目ヘッダー出力 1:利用する
        $b2UserSettings['header_output'] = '1';
        $pluginUtil->b2Data['user_settings'] = $b2UserSettings;
        $this->app['yamato_payment.util.plugin'] = $pluginUtil;
    }

    public function tearDown()
    {
        if (file_exists($this->filepath)) {
            unlink($this->filepath);
        }
        parent::tearDown();
    }

    /**
     * CSVを生成するための配列を返す.
     *
     * @param boolean $has_header ヘッダ行を含める場合 true
     * @return array CSVを生成するための配列
     * @see CsvImportController::getProductCsvHeader()
     */
    public function createCsvAsArray($has_header = true)
    {
        $Order = $this->createOrder($this->createCustomer());
        $Shippings = $Order->getShippings();

        return $this->createCsvFromShipping($Shippings[0], $has_header);
    }

    /**
     * @param Shipping $Shipping
     * @param bool|true $has_header
     * @return array
     */
    public function createCsvFromShipping($Shipping, $has_header = true)
    {
        $b2UserSettings = $this->app['yamato_payment.util.plugin']->getB2UserSettings();

        $Order = $Shipping->getOrder();
        $csv = array(
            '注文番号_発送情報ID' => $Order->getId() . '_' . $Shipping->getId(),
            '送り状種別' => null,
            'クール区分' => null,
            '送り状番号' => '123456789013',
            '出荷予定日' => null,
            'お届け予定' => null,
            '配達時間帯' => null,
            'お届け先コード' => null,
            'お届け先電話番号' => null,
            'お届け先電話番号枝番' => null,
            'お届け先郵便番号' => null,
            'お届け先住所' => null,
            'お届け先住所（アパートマンション名）' => null,
            'お届け先会社・部門名１' => null,
            'お届け先会社・部門名２' => null,
            'お届け先名' => null,
            'お届け先名称略カナ' => null,
            '敬称' => null,
            'ご依頼主コード' => null,
            'ご依頼主電話番号' => null,
            'ご依頼主電話番号枝番' => null,
            'ご依頼主郵便番号' => null,
            'ご依頼主住所' => null,
            'ご依頼主住所（アパートマンション名）' => null,
            'ご依頼主名' => null,
            'ご依頼主略称カナ' => null,
            '品名コード１' => null,
            '品名１' => null,
            '品名コード２' => null,
            '品名２' => null,
            '荷扱い１' => null,
            '荷扱い２' => null,
            '記事' => null,
            'コレクト代金引換額（税込）' => null,
            'コレクト内消費税額等' => null,
            '営業所止置き' => null,
            '営業所コード' => null,
            '発行枚数' => null,
            '個数口枠の印字' => null,
            'ご請求先顧客コード' => null,
            'ご請求先分類コード' => null,
            '運賃管理番号' => null,
            '注文時カード払いデータ登録' => null,
            '注文時カード払い加盟店番号' => null,
            '注文時カード払い申込受付番号１' => null,
            '注文時カード払い申込受付番号２' => null,
            '注文時カード払い申込受付番号３' => null,
            'お届け予定eメール利用区分' => null,
            'お届け予定eメールe-mailアドレス' => null,
            '入力機種' => null,
            'お届け予定eメールメッセージ' => null,
            'お届け完了eメール利用区分' => null,
            'お届け完了eメールe-mailアドレス' => null,
            'お届け完了eメールメッセージ' => null,
            'クロネコ収納代行利用区分' => null,
            '予備' => null,
            '収納代行請求金額（税込）' => null,
            '収納代行内消費税額等' => null,
            '収納代行請求先郵便番号' => null,
            '収納代行請求先住所' => null,
            '収納代行請求先住所（ｱﾊﾟｰﾄﾏﾝｼｮﾝ名)' => null,
            '収納代行請求先会社･部門名１' => null,
            '収納代行請求先会社･部門名２' => null,
            '収納代行請求先名（漢字）' => null,
            '収納代行請求先名（カナ）' => null,
            '収納代行問合せ先名（カナ）' => null,
            '収納代行問合せ先郵便番号' => null,
            '収納代行問合せ先住所' => null,
            '収納代行問合せ先住所（ｱﾊﾟｰﾄﾏﾝｼｮﾝ名）' => null,
            '収納代行問合せ先電話番号' => null,
            '収納代行管理番号' => null,
            '収納代行品名' => null,
            '収納代行備考' => null,
            '予備０１' => null,
            '予備０２' => null,
            '予備０３' => null,
            '予備０４' => null,
            '予備０５' => null,
            '予備０６' => null,
            '予備０７' => null,
            '予備０８' => null,
            '予備０９' => null,
            '予備１０' => null,
            '予備１１' => null,
            '予備１２' => null,
            '予備１３' => null,
            '投函予定メール利用区分' => null,
            '投函予定メールe-mailアドレス' => null,
            '投函予定メールメッセージ' => null,
            '投函完了メール(受人宛て)利用区分' => null,
            '投函完了メール(受人宛て)e-mailアドレス' => null,
            '投函完了メール(受人宛て)メッセージ' => null,
            '投函完了メール(出人宛て)利用区分' => null,
            '投函完了メール(出人宛て)e-mailアドレス' => null,
            '投函完了メール(出人宛て)メッセージ' => null,
            '連携管理番号' => null,
            '通知メールアドレス' => null,
        );

        // 2項目の場合
        if ($b2UserSettings['use_b2_format'] == '0') {
            $csv = array(
                '注文番号_発送情報ID' => $Order->getId() . '_' . $Shipping->getId(),
                '送り状番号' => '123456789013',
            );
        }

        $result = array();
        if ($has_header) {
            $result[] = array_keys($csv);
        }
        $result[] = array_values($csv);
        return $result;
    }

    /**
     * 引数の配列から CSV を生成し, リソースを返す.
     *
     * @param array $csv
     * @return string
     * @throws \Exception
     */
    public function createCsvFromArray(array $csv)
    {
        $fp = fopen($this->filepath, 'w');
        if ($fp !== false) {
            foreach ($csv as $row) {
                fputcsv($fp, $row);
            }
        } else {
            throw new \Exception('create error!');
        }
        fclose($fp);
        return $this->filepath;
    }

    public function testCsvB2_全項目のB2CSVアップロード_新規登録()
    {
        // 既存の送り状番号をDBからすべて削除
        $delivSlipAll = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']->findAll();
        foreach ($delivSlipAll as $item) {
            $this->app['orm.em']->remove($item);
        }
        $this->app['orm.em']->flush();

        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer());

        // B2CSVデータ生成
        $Shippings = $Order->getShippings();
        $Shipping = $Shippings[0];
        $csv = $this->createCsvFromShipping($Shipping);

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $expected = '送り状番号CSVファイルをアップロードしました。';
        $this->assertContains($expected, $crawler->filter('div.alert-success')->text());

        // 配送伝票番号情報取得
        $YamatoShippingDelivSlip = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']
            ->find($Shipping->getId());

        // 配送伝票番号情報が登録されていること
        $this->assertNotEmpty($YamatoShippingDelivSlip);

        // 配送伝票番号が正しく登録されていること
        $this->expected = $csv[1][3];
        $this->actual = $YamatoShippingDelivSlip->getDelivSlipNumber();
        $this->verify();
    }

    public function testCsvB2_2項目のB2CSVアップロード_新規登録()
    {
        // 既存の送り状番号をDBからすべて削除
        $delivSlipAll = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']->findAll();
        foreach ($delivSlipAll as $item) {
            $this->app['orm.em']->remove($item);
        }
        $this->app['orm.em']->flush();

        // 設定値書き換え
        $b2UserSettings = $this->app['yamato_payment.util.plugin']->getB2UserSettings();
        // B2フォーマット(2項目)
        $b2UserSettings['use_b2_format'] = '0';
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($b2UserSettings);

        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer());

        // B2CSVデータ生成
        $Shippings = $Order->getShippings();
        $Shipping = $Shippings[0];
        $csv = $this->createCsvFromShipping($Shipping);

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $expected = '送り状番号CSVファイルをアップロードしました。';
        $this->assertContains($expected, $crawler->filter('div.alert-success')->text());

        // 配送伝票番号情報取得
        $YamatoShippingDelivSlip = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']
            ->find($Shipping->getId());

        // 配送伝票番号情報が登録されていること
        $this->assertNotEmpty($YamatoShippingDelivSlip);

        // 配送伝票番号が正しく登録されていること
        $this->expected = $csv[1][1];
        $this->actual = $YamatoShippingDelivSlip->getDelivSlipNumber();
        $this->verify();
    }

    public function testCsvB2_全項目のB2CSVアップロード_出荷情報登録は利用しない_新規登録()
    {
        // 既存の送り状番号をDBからすべて削除
        $delivSlipAll = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']->findAll();
        foreach ($delivSlipAll as $item) {
            $this->app['orm.em']->remove($item);
        }
        $this->app['orm.em']->flush();

        // 設定値書き換え
        $b2UserSettings = $this->app['yamato_payment.util.plugin']->getB2UserSettings();
        // 取込時出荷情報登録API　0:利用しない
        $b2UserSettings['shpping_info_regist'] = '0';
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($b2UserSettings);

        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer());

        // B2CSVデータ生成
        $Shippings = $Order->getShippings();
        $Shipping = $Shippings[0];
        $csv = $this->createCsvFromShipping($Shipping);

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $expected = '送り状番号CSVファイルをアップロードしました。';
        $this->assertContains($expected, $crawler->filter('div.alert-success')->text());

        // 配送伝票番号情報取得
        $YamatoShippingDelivSlip = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']
            ->find($Shipping->getId());

        // 配送伝票番号情報が登録されていること
        $this->assertNotEmpty($YamatoShippingDelivSlip);

        // 配送伝票番号が正しく登録されていること
        $this->expected = $csv[1][3];
        $this->actual = $YamatoShippingDelivSlip->getDelivSlipNumber();
        $this->verify();
    }

    public function testCsvB2_全項目のB2CSVアップロード_更新登録()
    {
        // 既存の送り状番号をDBからすべて削除
        $delivSlipAll = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']->findAll();
        foreach ($delivSlipAll as $item) {
            $this->app['orm.em']->remove($item);
        }
        $this->app['orm.em']->flush();

        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer());
        $this->createYamatoShippingDelivSlip($Order);

        // B2CSVデータ生成
        $Shippings = $Order->getShippings();
        $Shipping = $Shippings[0];
        $csv = $this->createCsvFromShipping($Shipping);

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $expected = '送り状番号CSVファイルをアップロードしました。';
        $this->assertContains($expected, $crawler->filter('div.alert-success')->text());

        // 更新後の配送伝票番号情報取得
        $YamatoShippingDelivSlip = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']
            ->find($Shipping->getId());

        // 配送伝票番号が更新されていること
        $this->expected = $csv[1][3];
        $this->actual = $YamatoShippingDelivSlip->getDelivSlipNumber();
        $this->verify();
    }

    public function testCsvB2_全項目のB2CSVアップロード_3件の受注_同一伝票番号_3受注目で同梱限度額を超えてエラーの場合_2件目までは出荷登録完了()
    {
        // 既存の送り状番号をDBからすべて削除
        $delivSlipAll = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']->findAll();
        foreach ($delivSlipAll as $item) {
            $this->app['orm.em']->remove($item);
        }
        $this->app['orm.em']->flush();

        $customer = $this->createCustomer();

        // 受注情報3件作成
        $Order = array();
        $csv = array();
        for($i=0; $i<3; $i++){
            $Order[$i] = $this->createOrder($customer);
            /** @var Order $Order */
            foreach ($Order[$i]->getOrderDetails() as $orderDetail){
                /** @var OrderDetail $orderDetail */
                $orderDetail->setPrice(100000);
                $orderDetail->setQuantity(1);
            }
            $this->createOrderPaymentDataCredit($Order[$i]);
            $this->createYamatoShippingDelivSlip($Order[$i]);
            // B2CSVデータ生成
            $Shippings = $Order[$i]->getShippings();
            $Shipping = $Shippings[0];

            $csv = array_merge($csv, $this->createCsvFromShipping($Shipping));
        }
        unset($csv[2], $csv[4]);

        $this->app['orm.em']->flush();

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // UtilClientServiceモック
        $this->app['yamato_payment.service.client.util'] = $this->createUtilClientService_add(true, true, false);

        // CSVファイルのアップロード
        $this->scenario();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $i = 0;
        foreach($Order as $item){
            $YamatoOrderPayment[$i] = $this->app['yamato_payment.repository.yamato_order_payment']->find($item->getId());
            $i++;
        }

        /** @var YamatoOrderPayment $YamatoOrderPayment */
        // 1件目の受注情報の取引状況は精算確定待ちになっていること
        $this->assertEquals($this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT'], $YamatoOrderPayment[0]->getMemo04());

        // 2件目の受注情報の取引状況は精算確定待ちになっていること
        $this->assertEquals($this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT'], $YamatoOrderPayment[1]->getMemo04());

        // 2件目の受注情報の取引状況は与信完了のままなこと
        $this->assertEquals($this->const['YAMATO_ACTION_STATUS_COMP_AUTH'], $YamatoOrderPayment[2]->getMemo04());
    }

    public function testCsvB2_全項目のB2CSVアップロード_1受注に対して伝票番号が99件_正常に登録完了()
    {
        // 既存の送り状番号をDBからすべて削除
        $delivSlipAll = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']->findAll();
        foreach ($delivSlipAll as $item) {
            $this->app['orm.em']->remove($item);
        }
        $this->app['orm.em']->flush();

        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer());

        // 98件の配送先を追加し、配送先を99件にする
        for ($i = 0; $i < 98; $i++) {
            $Order->addShipping($this->createShipping($Order));
        }
        $this->app['orm.em']->flush();

        // 決済情報を作成
        $this->createOrderPaymentDataCredit($Order);

        // 伝票番号の登録
        $delivSlipAll = $this->createDelivSlipNumber($Order);

        // B2CSVデータ生成
        $Shippings = $Order->getShippings();

        $csv = array();
        $i = 0;
        foreach ($Shippings as $Shipping) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $YamatoShippingDelivSlip = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']->find($Shipping->getId());

            $data = $this->createCsvFromShipping($Shipping);
            $data[1][3] = $YamatoShippingDelivSlip->getDelivSlipNumber();
            if ($i != 0) {
                unset($data[0]);
            }
            $csv = array_merge($csv, $data);

            $i++;
        }

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // UtilClientServiceのモック作成
        $this->app['yamato_payment.service.client.util'] = $this->createUtilClientService(true, $delivSlipAll);

        // CSVファイルのアップロード
        $crawler = $this->scenario();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $expected = '送り状番号CSVファイルをアップロードしました。';
        $this->assertContains($expected, $crawler->filter('div.alert-success')->text());
    }

    public function testCsvB2_全項目のB2CSVアップロード_1受注に対して伝票番号が100件_エラーメッセージが返る()
    {
        // 既存の送り状番号をDBからすべて削除
        $delivSlipAll = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']->findAll();
        foreach ($delivSlipAll as $item) {
            $this->app['orm.em']->remove($item);
        }
        $this->app['orm.em']->flush();

        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer());

        // 99件の配送先を追加し、配送先を100件にする
        for ($i = 0; $i < 99; $i++) {
            $Order->addShipping($this->createShipping($Order));
        }
        $this->app['orm.em']->flush();

        // 決済情報を作成
        $this->createOrderPaymentDataCredit($Order);

        // 伝票番号の登録
        $this->createDelivSlipNumber($Order);

        // B2CSVデータ生成
        $Shippings = $Order->getShippings();

        $csv = array();
        $i = 0;
        foreach ($Shippings as $Shipping) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $YamatoShippingDelivSlip = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']->find($Shipping->getId());

            $data = $this->createCsvFromShipping($Shipping);
            $data[1][3] = $YamatoShippingDelivSlip->getDelivSlipNumber();
            if ($i != 0) {
                unset($data[0]);
            }
            $csv = array_merge($csv, $data);

            $i++;
        }

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $expected = '1つの注文に対する出荷情報の上限（' . $this->const['YAMATO_DELIV_ADDR_MAX'] . '件）を超えております。';
        $this->assertContains($expected, $crawler->filter('div.text-danger')->text());
    }

    public function testCsvB2_CSVファイルのヘッダー行が存在しない場合()
    {
        // 設定値書き換え
        $b2UserSettings = $this->app['yamato_payment.util.plugin']->getB2UserSettings();
        // B2CSV一行目ヘッダー出力 0:利用しない
        $b2UserSettings['header_output'] = '0';
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($b2UserSettings);

        // B2CSVデータ生成(ヘッダー無し)
        $csv = $this->createCsvAsArray(false);

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        $expected = '送り状番号CSVファイルをアップロードしました。';
        $this->assertContains($expected, $crawler->filter('div.alert-success')->text());
    }

    public function testCsvB2_全項目のB2CSVアップロードCSVの項目数が94項目以下の場合エラーメッセージが返る()
    {
        // B2CSVデータ生成
        $csv = $this->createCsvAsArray();
        // 5列削除
        for ($i=0; $i<5; $i++) {
            unset($csv[0][$i]);
            unset($csv[1][$i]);
        }

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('CSVのフォーマットが一致しません',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvB2_2項目のB2CSVアップロードCSVの項目数が2項目でない場合エラーメッセージが返る()
    {
        // 設定値書き換え
        $b2UserSettings = $this->app['yamato_payment.util.plugin']->getB2UserSettings();
        // B2フォーマット(2項目)
        $b2UserSettings['use_b2_format'] = '0';
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($b2UserSettings);

        // B2CSVデータ生成
        $csv = $this->createCsvAsArray();
        // 1列削除
        unset($csv[0][1]);
        unset($csv[1][1]);

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('CSVのフォーマットが一致しません',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvB2_CSVファイルのデータが存在しない場合エラーメッセージが返る()
    {
        // データ行削除
        $csv = array();
        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('空のファイルは許可されていません。',
            $crawler->filter('')->text());
    }

    public function testCsvB2_注文番号_発送情報IDが空白の場合エラーメッセージが返る()
    {
        // B2CSVデータ生成
        $csv = $this->createCsvAsArray();
        // 注文番号_発送情報IDに空白をセット
        $csv[1][0] = '';

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/行目の注文番号_発送情報IDが設定されていません。/u',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvB2_注文番号が空白の場合エラーメッセージが返る()
    {
        // B2CSVデータ生成
        $csv = $this->createCsvAsArray();

        // 注文番号に空を設定
        preg_match('/_(\d+)/', $csv[1][0], $match);
        $csv[1][0] = $match[0];

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/行目の注文番号_発送情報IDが不正です。/u',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvB2_発送情報IDが空白の場合エラーメッセージが返る()
    {
        // B2CSVデータ生成
        $csv = $this->createCsvAsArray();

        // 発送情報IDに空を設定
        preg_match('/(^\d+)_/', $csv[1][0], $match);
        $csv[1][0] = $match[0];

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/行目の注文番号_発送情報IDが不正です。/u',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvB2_注文番号が数字でない場合エラーメッセージが返る()
    {
        // B2CSVデータ生成
        $csv = $this->createCsvAsArray();

        // 注文番号を数字以外に書き換え
        preg_match('/_(\d+)/', $csv[1][0], $match);
        $csv[1][0] = 'abcd' . $match[0];

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/行目の注文番号_発送情報IDが不正です。/u',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvB2_発送情報IDが数字でない場合エラーメッセージが返る()
    {
        // B2CSVデータ生成
        $csv = $this->createCsvAsArray();

        // 発送情報IDに数字以外を設定
        preg_match('/(^\d+)_/', $csv[1][0], $match);
        $csv[1][0] = $match[0] . 'abcd';

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/行目の注文番号_発送情報IDが不正です。/u',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvB2_配送情報が存在しない場合エラーメッセージが返る()
    {
        // B2CSVデータ生成
        $csv = $this->createCsvAsArray();

        // 発送IDを取得
        preg_match('/_(\d+)/', $csv[1][0], $match);
        $shipping_id = $match[1];

        // 配送情報を削除
        /** @var Shipping $Shipping */
        $Shipping = $this->app['eccube.repository.shipping']->find($shipping_id);
        $this->app['orm.em']->remove($Shipping);
        $this->app['orm.em']->flush();

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルアップロード
        $crawler = $this->scenario();

        // パターンに変数を入れるとエラーが返るため、パターンを分けている
        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/行目の注文番号_発送情報ID/u',
            $crawler->filter('div.text-danger')->text());

        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/が存在しません。/u',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvB2_配送情報の受注IDと注文番号が一致しない場合エラーメッセージが返る()
    {
        // B2CSVデータ生成
        $csv = $this->createCsvAsArray();

        list($order_id, $shipping_id) = explode('_', $csv[1][0]);

        // 注文番号を変更
        $csv[1][0] = $order_id + 1 . '_' . $shipping_id;

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルアップロード
        $crawler = $this->scenario();

        // パターンに変数を入れるとエラーが返るため、パターンを分けている
        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/行目の注文番号_発送情報ID/u',
            $crawler->filter('div.text-danger')->text());

        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/が存在しません。/u',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvB2_配送情報の出荷IDと発送情報IDが一致しない場合エラーメッセージが返る()
    {
        // B2CSVデータ生成
        $csv = $this->createCsvAsArray();

        list($order_id, $shipping_id) = explode('_', $csv[1][0]);

        // 注文番号を変更
        $csv[1][0] = $order_id . '_' . ($shipping_id + 1);

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルアップロード
        $crawler = $this->scenario();

        // パターンに変数を入れるとエラーが返るため、パターンを分けている
        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/行目の注文番号_発送情報ID/u',
            $crawler->filter('div.text-danger')->text());

        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/が存在しません。/u',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvB2_送り状番号が空白の場合エラーメッセージが返る()
    {
        // B2CSVデータ生成
        $csv = $this->createCsvAsArray();

        // 注文番号_発送情報IDに空白をセット
        $csv[1][3] = '';

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/行目の送り状番号が設定されていません。/u',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvB2_送り状番号が有効な値でない場合エラーメッセージが返る()
    {
        // B2CSVデータ生成
        $csv = $this->createCsvAsArray();

        // 送り状番号に不正な値をセット（セブンチェックエラー）
        $csv[1][3] = 123456789012;

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/行目の送り状番号が不正です。/u',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvB2_出荷情報登録前確認処理でエラーが発生した場合エラーメッセージが返る()
    {
        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer());
        // クレジット決済に設定
        $this->createOrderPaymentDataCredit($Order);

        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil('エラーメッセージ');

        // B2CSVデータ生成
        $Shippings = $Order->getShippings();
        $Shipping = $Shippings[0];
        $csv = $this->createCsvFromShipping($Shipping);

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 適切なエラーメッセージが取得できること
        $this->assertContains('エラーメッセージ',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvB2_出荷情報登録前確認処理でエラーが発生した場合()
    {
        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer());
        // クレジット決済に設定
        $this->createOrderPaymentDataCredit($Order);

        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil(array());

        $this->app['yamato_payment.service.client.util'] = $this->createUtilClientService(false, null);

        // B2CSVデータ生成
        $Shippings = $Order->getShippings();
        $Shipping = $Shippings[0];
        $csv = $this->createCsvFromShipping($Shipping);

        // CSVファイル生成
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 適切なエラーメッセージが取得できること
        $this->assertContains('error.',
            $crawler->filter('#upload_file_box__upload_message')->text());
    }

    public function testCsvTemplate_全項目のテンプレートダウンロード()
    {
        // CSVテンプレートファイルのダウンロード
        $this->client->request(
            'GET',
            $this->app->path('yamato_order_b2_csv_template', array('type' => 'b2'))
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        // 出力されたCSVを取得
        $csv = $this->getActualOutput();
        $csv = explode("\n", $csv);
        // ヘッダ項目数が95であること
        $header = explode(',', $csv[0]);
        $this->assertEquals(97, count($header));
    }
    public function testCsvTemplate_2項目のテンプレートダウンロード()
    {
        // 設定値書き換え
        $b2UserSettings = $this->app['yamato_payment.util.plugin']->getB2UserSettings();
        // B2フォーマット(2項目)
        $b2UserSettings['use_b2_format'] = '0';
        $this->app['yamato_payment.util.plugin']->registerB2UserSettings($b2UserSettings);
        // CSVテンプレートファイルのダウンロード
        $this->client->request(
            'GET',
            $this->app->path('yamato_order_b2_csv_template', array('type' => 'b2'))
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        // 出力されたCSVを取得
        $csv = $this->getActualOutput();
        $csv = explode("\n", $csv);
        // ヘッダ項目数が2であること
        $header = explode(',', $csv[0]);
        $this->assertEquals(2, count($header));
    }

    public function testAddOrderId_クレジットカード決済()
    {
        // 受注データ作成
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataCredit($Order);

        // 受注IDが保持されていないことを確認
        $this->assertEmpty($this->object->listCreditOrderId);

        // テスト対象メソッド実行
        $this->object->addOrderId($Order->getId(), $this->app);

        // 受注IDが保持されていること
        $this->assertNotEmpty($this->object->listCreditOrderId);
    }

    public function testAddOrderId_後払い決済()
    {
        // 受注データ作成
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataDeferred($Order);

        // 受注IDが保持されていないことを確認
        $this->assertEmpty($this->object->listDeferredOrderId);

        // テスト対象メソッド実行
        $this->object->addOrderId($Order->getId(), $this->app);

        // 受注IDが保持されていること
        $this->assertNotEmpty($this->object->listDeferredOrderId);
    }

    public function testDoShipmentEntry_クレジットカード決済出荷情報登録成功()
    {
        // 受注データ作成
        $Order = $this->createOrderData();

        // クレジットカード決済用受注IDを設定
        $this->object->listCreditOrderId= array($Order->getId());

        // 送り状番号作成
        $listSuccessSlip = array('123456789013');

        // Mock作成
        $this->app['yamato_payment.service.client.util'] = $this->createUtilClientService(true, $listSuccessSlip);

        // 出荷情報登録結果メッセージが空であることを確認
        $this->assertEmpty($this->object->listRowShipmentEntryReport);

        // テスト対象メソッド実行
        $this->object->doShipmentEntry($this->app);

        // 出荷情報登録結果メッセージが追加されていることを確認
        $actual = $this->object->listRowShipmentEntryReport;
        $this->assertContains('出荷情報登録成功しました', $actual[0]);
    }

    public function testDoShipmentEntry_クレジットカード決済出荷情報登録失敗()
    {
        // 受注データ作成
        $Order = $this->createOrderData();

        // クレジットカード決済用受注IDを設定
        $this->object->listCreditOrderId= array($Order->getId());

        // 送り状番号作成
        $listSuccessSlip = array('123456789013');

        // Mock作成
        $this->app['yamato_payment.service.client.util'] = $this->createUtilClientService(false, $listSuccessSlip);

        // 出荷情報登録結果メッセージが空であることを確認
        $this->assertEmpty($this->object->listRowShipmentEntryReport);

        // テスト対象メソッド実行
        $this->object->doShipmentEntry($this->app);

        // 出荷情報登録結果メッセージが追加されていることを確認
        $actual = $this->object->listRowShipmentEntryReport;
        $this->assertContains('error.', $actual[0]);
    }

    public function testDoShipmentEntry_クロネコ代金後払い決済出荷情報登録成功()
    {
        // 受注データ作成
        $Order = $this->createOrderData();

        // クロネコ代金後払い決済用受注IDを設定
        $this->object->listDeferredOrderId= array($Order->getId());

        // 登録処理成功回数
        $success_cnt = '3';
        // 登録処理失敗回数
        $failure_cnt = '0';

        // Mock作成
        $this->app['yamato_payment.service.client.deferred_util'] = $this->createDeferredUtilClientService(true, $success_cnt, $failure_cnt);

        // 出荷情報登録結果メッセージが空であることを確認
        $this->assertEmpty($this->object->listRowShipmentEntryReport);

        // テスト対象メソッド実行
        $this->object->doShipmentEntry($this->app);

        // 出荷情報登録結果メッセージが追加されていることを確認
        $actual = $this->object->listRowShipmentEntryReport;
        $this->assertContains('出荷情報登録成功しました', $actual[0]);
    }

    public function testDoShipmentEntry_クロネコ代金後払い決済出荷情報登録失敗()
    {
        // 受注データ作成
        $Order = $this->createOrderData();

        // クロネコ代金後払い決済用受注IDを設定
        $this->object->listDeferredOrderId= array($Order->getId());

        // 登録処理成功回数
        $success_cnt = '3';
        // 登録処理失敗回数
        $failure_cnt = '0';

        // Mock作成
        $this->app['yamato_payment.service.client.deferred_util'] = $this->createDeferredUtilClientService(false, $success_cnt, $failure_cnt);

        // 出荷情報登録結果メッセージが空であることを確認
        $this->assertEmpty($this->object->listRowShipmentEntryReport);

        // テスト対象メソッド実行
        $this->object->doShipmentEntry($this->app);

        // 出荷情報登録結果メッセージが追加されていることを確認
        $actual = $this->object->listRowShipmentEntryReport;
        $this->assertContains('error.', $actual[0]);
    }

    public function testAddRowShipmentEntryReport()
    {
        // 出荷情報登録結果のメッセージが空なことを確認
        $this->assertEmpty($this->object->listRowShipmentEntryReport);

        // 受注ID取得
        $Order = $this->createOrderData();
        $order_id = $Order->getId();

        $message = 'abcdefg';

        $this->object->addRowShipmentEntryReport($order_id, $message);

        // 出荷情報登録結果のメッセージをプロパティへ追加されていること
        $this->assertNotEmpty($this->object->listRowShipmentEntryReport);
    }

    public function testCheckErrorShipmentEntry_クレジットカード決済及びクロネコ代金後払い決済が1件もない場合は空の配列を返す()
    {
        // クレジットカード決済の受注IDに空を設定
        $this->object->listCreditOrderId = array();
        // クロネコ代金後払い決済の受注IDに空を設定
        $this->object->listDeferredOrderId = array();

        // クレジットカード決済及びクロネコ代金後払い決済が1件もない場合は空白を返すこと
        $this->assertEmpty($this->object->checkErrorShipmentEntry($this->app));
    }

    public function testCheckErrorShipmentEntry_クレジットカード決済でエラーのない場合は空の配列を返す()
    {
        // 受注ID取得
        $Order = $this->createOrderData();
        $order_id = $Order->getId();

        // クレジットカード決済の受注IDに空を設定
        $this->object->listCreditOrderId[] = $order_id;

        // PaymentUtilをmockで上書き
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil(null);

        // クレジットカード決済にエラーのない場合は空の配列を返すこと
        $listErr = $this->object->checkErrorShipmentEntry($this->app);
        $this->assertEmpty($listErr);
        $this->assertTrue(is_array($listErr));
    }

    public function testCheckErrorShipmentEntry_クレジットカード決済でエラーがある場合はエラーメッセージの配列を返す()
    {
        // 受注ID取得
        $Order = $this->createOrderData();
        $order_id = $Order->getId();

        // クレジットカード決済の受注IDに空を設定
        $this->object->listCreditOrderId[] = $order_id;

        // PaymentUtilをmockで上書き
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil('error.');

        // クレジットカード決済にエラーがある場合はエラーメッセージを返すこと
        $listErr = $this->object->checkErrorShipmentEntry($this->app);
        $this->assertNotEmpty($listErr);
        $this->assertContains('error.', $listErr[0]);
    }

    public function testCheckErrorShipmentEntry_クロネコ代金後払い決済でエラーのない場合は空の配列を返す()
    {
        // 受注ID取得
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataDeferred($Order);
        $order_id = $Order->getId();

        // クロネコ代金後払い決済の受注IDに空を設定
        $this->object->listDeferredOrderId[] = $order_id;

        // PaymentUtilをmockで上書き
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil(null);

        // クロネコ代金後払い決済にエラーのない場合は空の配列を返すこと
        $listErr = $this->object->checkErrorShipmentEntry($this->app);
        $this->assertEmpty($listErr);
        $this->assertTrue(is_array($listErr));
    }

    public function testCheckErrorShipmentEntry_クロネコ代金後払い決済でエラーがある場合はエラーメッセージの配列を返す()
    {
        // 受注ID取得
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataDeferred($Order);
        $order_id = $Order->getId();

        // クロネコ代金後払い決済の受注IDに空を設定
        $this->object->listDeferredOrderId[] = $order_id;

        // PaymentUtilをmockで上書き
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil('error.');

        // クロネコ代金後払い決済にエラーがある場合はエラーメッセージを返すこと
        $listErr = $this->object->checkErrorShipmentEntry($this->app);
        $this->assertNotEmpty($listErr);
        $this->assertContains('error.', $listErr[0]);
    }

    /**
     * $this->filepath のファイルを CSV アップロードし, 完了画面の crawler を返す.
     *
     * @param string $bind
     * @param string $original_name
     * @return Crawler
     */
    private function scenario($bind = 'yamato_order_b2_csv_upload', $original_name = 'order_b2.csv')
    {
        $file = new UploadedFile(
            $this->filepath,    // file path
            $original_name,     // original name
            'text/csv',         // mimeType
            null,               // file size
            null,               // error
            true                // test mode
        );

        $crawler = $this->client->request(
            'POST',
            $this->app->path($bind),
            array(
                'admin_csv_import' => array(
                    '_token' => 'dummy',
                    'import_file' => $file
                )
            ),
            array('import_file' => $file)
        );
        return $crawler;
    }
    
    private function createUtilClientService($bool, $listSuccessSlip)
    {
        $mock = $this->getMock('UtilClientService', array('doShipmentEntry', 'getError', 'doShipmentRollback'));
        $mock->expects($this->any())
            ->method('doShipmentEntry')
            ->will($this->returnValue(array($bool, $listSuccessSlip)));
        $mock->expects($this->any())
            ->method('getError')
            ->will($this->returnValue(array('error.')));
        $mock->expects($this->any())
            ->method('doShipmentRollback')
            ->will($this->returnValue(null));
        return $mock;
    }

    private function createUtilClientService_add($bool, $bool2, $bool3)
    {
        $mock = $this->getMock('Plugin\YamatoPayment\Service\Client\UtilClientService'
            , array('getError', 'doShipmentRollback', 'sendOrderRequest')
            , array($this->app));
        $mock->expects($this->any())
            ->method('getError')
            ->will($this->returnValue(array('error.')));
        $mock->expects($this->any())
            ->method('doShipmentRollback')
            ->will($this->returnValue(null));
        $mock->expects($this->any())
            ->method('sendOrderRequest')
            ->will($this->onConsecutiveCalls($bool, $bool2, $bool3));
        return $mock;
    }

    private function createDeferredUtilClientService($bool, $success_cnt, $failure_cnt)
    {
        $mock = $this->getMock('DeferredUtilClientService', array('doShipmentEntry', 'getError'));
        $mock->expects($this->any())
            ->method('doShipmentEntry')
            ->will($this->returnValue(array($bool, $success_cnt, $failure_cnt)));
        $mock->expects($this->any())
            ->method('getError')
            ->will($this->returnValue(array('error.')));
        return $mock;
    }

    private function createPaymentUtil($message)
    {
        $mock = $this->getMock('Plugin\YamatoPayment\Util\PaymentUtil'
            , array('checkErrorShipmentEntryForCredit', 'checkErrorShipmentEntryForDeferred')
            ,array($this->app)
        );

        $mock->expects($this->any())
            ->method('checkErrorShipmentEntryForCredit')
            ->will($this->returnValue($message));
        $mock->expects($this->any())
            ->method('checkErrorShipmentEntryForDeferred')
            ->will($this->returnValue($message));
        return $mock;
    }

    /**
     * @param Order $Order
     * @return array $delivSlipAll
     */
    private function createDelivSlipNumber($Order)
    {
        /** @var \Faker\Generator $faker */
        $faker = $this->getFaker();

        // 伝票番号の登録
        $num = 0;
        $delivSlip = null;
        $delivSlipAll = array();
        foreach ($Order->getShippings() as $Shipping) {

            // 伝票番号の作成
            $delivSlip = str_split((string)(10000000000 + $num));
            //セブンチェック（先頭11桁÷7の余りが末尾1桁）
            $tempMod = 0;
            for ($i = 0; $i < 11; $i++) {
                $tempMod = $tempMod * 10 + (int)$delivSlip[$i];
                $tempMod %= 7;
            }
            $delivSlip[11] = $tempMod;
            $delivSlip = implode($delivSlip);

            $num++;

            /** @var Shipping $Shipping */
            // 配送伝票番号データ作成
            $YamatoShippingDelivSlip = new YamatoShippingDelivSlip();
            $YamatoShippingDelivSlip
                ->setId($Shipping->getId())
                ->setOrderId($Order->getId())
                ->setDelivSlipNumber($delivSlip)
                ->setLastDelivSlipNumber($delivSlip)
                ->setDelivSlipUrl($faker->url());
            $this->app['orm.em']->persist($YamatoShippingDelivSlip);
            $this->app['orm.em']->flush();

            $delivSlipAll[] = $delivSlip;
        }

        return $delivSlipAll;
    }
}
