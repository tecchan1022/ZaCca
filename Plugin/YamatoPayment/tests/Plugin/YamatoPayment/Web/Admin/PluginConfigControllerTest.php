<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Web\Admin;

class PluginConfigControllerTest extends AbstractAdminWebTestCase
{
    protected $subData;

    public function setUp()
    {
        parent::setUp();

        // SubData用UserSettingを取得
        $this->subData = array(
            // 基本設定　動作モード
            // テスト環境：0 本番環境：1
            'exec_mode' => 0,
            // 基本設定　有効にする決済方法
            'enable_payment_type' => array(
                0 => $this->const['YAMATO_PAYID_CREDIT'],
                1 => $this->const['YAMATO_PAYID_CVS'],
                2 => $this->const['YAMATO_PAYID_DEFERRED'],
            ),
            // クロネコWebコレクト設定　クロネコｗｅｂコレクト加盟店コード
            'shop_id' => 123456789,
            // クロネコWebコレクト設定　アクセスキー
            'access_key' => 1111111,
            // クロネコWebコレクト設定　予約販売機能
            'advance_sale' => 1,
            // クロネコ代金後払い設定　クロネコ代金後払い加盟店コード
            'ycf_str_code' => 12345678901,
            // クロネコ代金後払い設定　クロネコ代金後払いパスワード
            'ycf_str_password' => 11111111,
            // クロネコ代金後払い設定　請求書の同梱
            // 同梱しない：0 同梱する：1
            'ycf_send_div' => 0,
            // クロネコ代金後払い設定　出荷予定日
            'ycf_ship_ymd' => 00,
            // クロネコ代金後払い設定　メールの追跡情報表示機能
            // 利用する：0 利用しない：1
            'ycf_deliv_disp' => 0,
            // クロネコ代金後払い設定　メールの追跡情報表示機能
            'ycf_invoice_reissue_mail_address' => 'dev_test@test.com',
            // クロネコ代金後払い設定　メールの追跡情報表示機能
            'ycf_invoice_reissue_mail_header' => 'あいうえおアイウエオＡＩＵＥＯａｉｕｅｏ ｱｲｳｴｵaiueoAIUEO　!#$%！＃＄％＆',
            // クロネコ代金後払い設定　メールの追跡情報表示機能
            'ycf_invoice_reissue_mail_footer' => 'あいうえおアイウエオＡＩＵＥＯａｉｕｅｏ ｱｲｳｴｵaiueoAIUEO　!#$%！＃＄％＆',
        );
    }

    protected function createFormData($subData)
    {
        $form = array(
            '_token' => 'dummy',
            // 基本設定　動作モード
            'exec_mode' => $subData['exec_mode'],
            // 基本設定　有効にする決済方法
            'enable_payment_type' => $subData['enable_payment_type'],
            // クロネコWebコレクト設定　クロネコｗｅｂコレクト加盟店コード
            'shop_id' => $subData['shop_id'],
            // クロネコWebコレクト設定　アクセスキー
            'access_key' => $subData['access_key'],
            // クロネコWebコレクト設定　予約販売機能
            'advance_sale' => $subData['advance_sale'],
            // クロネコ代金後払い設定　クロネコ代金後払い加盟店コード
            'ycf_str_code' => $subData['ycf_str_code'],
            // クロネコ代金後払い設定　クロネコ代金後払いパスワード
            'ycf_str_password' => $subData['ycf_str_password'],
            // クロネコ代金後払い設定　請求書の同梱
            'ycf_send_div' => $subData['ycf_send_div'],
            // クロネコ代金後払い設定　出荷予定日
            'ycf_ship_ymd' => $subData['ycf_ship_ymd'],
            // クロネコ代金後払い設定　メールの追跡情報表示機能
            'ycf_deliv_disp' => $subData['ycf_deliv_disp'],
            // クロネコ代金後払い設定　請求書再発行通知メール：受取メールアドレス
            'ycf_invoice_reissue_mail_address' => $subData['ycf_invoice_reissue_mail_address'],
            // クロネコ代金後払い設定　請求書再発行通知メール：メールヘッダー
            'ycf_invoice_reissue_mail_header' => $subData['ycf_invoice_reissue_mail_header'],
            // クロネコ代金後払い設定　請求書再発行通知メール：メールフッター
            'ycf_invoice_reissue_mail_footer' => $subData['ycf_invoice_reissue_mail_footer'],
        );
        return $form;
    }

    public function testEdit__初期表示()
    {
        // プラグインの初期値を登録
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->subData);

        // フォームの作成
        $form = $this->createFormData($this->subData);

        $this->client->request(
            'GET',
            $this->app->path('plugin_YamatoPayment_config')
            , array('yamato_plugin_config' => $form)
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testEdit__登録処理__True()
    {
        // プラグインの初期値を登録
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->subData);

        // フォームの作成
        $form = $this->createFormData($this->subData);

        $crawler = $this->client->request(
            'POST',
            $this->app->url('plugin_YamatoPayment_config'),
            array('yamato_plugin_config' => $form)
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 登録が完了すること
        $this->assertRegexp('/登録が完了しました。/u',
            $crawler->filter('div.alert-success')->text());
    }

    public function testEdit__登録処理__False()
    {
        // 基本設定　動作モードを削除する
        $this->subData['exec_mode'] = null;
        // クロネコ代金後払い設定　請求書再発行通知メール：受取メールアドレスを削除する
        $this->subData['ycf_invoice_reissue_mail_address'] = null;
        // クロネコ代金後払い設定　請求書再発行通知メール：メールヘッダーを削除する
        $this->subData['ycf_invoice_reissue_mail_header'] = null;

        // プラグインの初期値を登録
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->subData);

        // フォームの作成
        $form = $this->createFormData($this->subData);

        $crawler = $this->client->request(
            'POST',
            $this->app->url('plugin_YamatoPayment_config'),
            array('yamato_plugin_config' => $form)
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エラーメッセージを取得すること
        $this->assertRegexp('/登録できませんでした。/u',
            $crawler->filter('div.alert-danger')->text());
    }
}
