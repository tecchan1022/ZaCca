<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;

class AdminSettingShopPaymentEditEventTest extends AbstractEventTestCase
{
    function setUp()
    {
        parent::setUp();
        $this->adminLogIn();

        // クレジットカード支払方法設定
        $payment_method = array(
            'pay_way' => array(0, 1, 2),
            'TdFlag' => 1,
            'order_mail_title' => "お支払いについて",
            'order_mail_body' => "お支払いクレジットカード",
        );
        /** @var YamatoPaymentMethod $YamatoPaymentMethodCredit */
        $YamatoPaymentMethodCredit = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']));
        $YamatoPaymentMethodCredit->setMemo05($payment_method);
        $this->app['orm.em']->flush();
    }

    /**
     * @param string $method
     * @return array
     */
    function createFormData($method)
    {
        $form = array(
            'method' => $method,
            'charge' => 0,
            'rule_min' => 0,
            'rule_max' => 100000,
            'charge_flg' => 1,
            'fix_flg' => 1,
            '_token' => 'dummy'
        );
        return $form;
    }

    /**
     * クレジット決済入力データ作成
     * @param string $method
     * @return array
     */
    function createCreditFormData($method)
    {
        /** @var \Faker\Generator $faker */
        $faker = $this->getFaker();
        $form = $this->createFormData($method);
        $form['pay_way'] = array(0, 1);
        $form['TdFlag'] = '1';
        $form['order_mail_title'] = $faker->word;
        $form['order_mail_body'] = $faker->paragraph;

        return $form;
    }

    /**
     * コンビニ決済入力データ作成
     * @param string $method
     * @return array
     */
    function createConvenienceFormData($method)
    {
        /** @var \Faker\Generator $faker */
        $faker = $this->getFaker();
        $form = $this->createFormData($method);
        $form['conveni'] = array(21, 22, 26, 24);
        $form['order_mail_title_21'] = $faker->word;
        $form['order_mail_body_21'] = $faker->paragraph;
        $form['order_mail_title_22'] = $faker->word;
        $form['order_mail_body_22'] = $faker->paragraph;
        $form['order_mail_title_23'] = $faker->word;
        $form['order_mail_body_23'] = $faker->paragraph;
        $form['order_mail_title_24'] = $faker->word;
        $form['order_mail_body_24'] = $faker->paragraph;
        $form['order_mail_title_25'] = $faker->word;
        $form['order_mail_body_25'] = $faker->paragraph;
        $form['order_mail_title_26'] = $faker->word;
        $form['order_mail_body_26'] = $faker->paragraph;

        return $form;
    }

    /**
     * 後払い決済入力データ作成
     * @param string $method
     * @return array
     */
    function createDeferredFormData($method)
    {
        /** @var \Faker\Generator $faker */
        $faker = $this->getFaker();
        $form = $this->createFormData($method);
        $form['order_mail_title'] = $faker->word;
        $form['order_mail_body'] = $faker->paragraph;

        return $form;
    }

    function testNewRender_新規登録画面の表示()
    {
        // 編集画面表示
        $crawler = $this->client->request(
            'GET',
            $this->app->url('admin_setting_shop_payment_new')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // ヤマト決済用のtwigが表示されていないこと
        $expected = '各種ロゴを下記のURLよりご利用いただけます。';
        $actual = $crawler->filter('#detail_box__body')->html();
        $this->assertNotContains($expected, $actual);
    }

    function testEditRender_ヤマト決済以外の表示()
    {
        // 編集画面表示
        $crawler = $this->client->request(
            'GET',
            $this->app->url('admin_setting_shop_payment_edit', array('id' => 1))
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // ヤマト決済用のtwigが表示されていないこと
        $expected = '/各種ロゴを下記のURLよりご利用いただけます。/u';
        $actual = $crawler->filter('#detail_box__body')->html();
        $this->assertNotRegExp($expected, $actual);
    }

    function testEditRender_クレジット決済の表示()
    {
        // クレジット決済情報取得
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array(
            'memo03' => $this->const['YAMATO_PAYID_CREDIT'],
        ));

        // 編集画面表示
        $crawler = $this->client->request(
            'GET',
            $this->app->url('admin_setting_shop_payment_edit', array('id' => $YamatoPaymentMethod->getId()))
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面ソース取得
        $source = $crawler->filter('#form1')->html();

        // ヤマト決済用のtwigが表示されていること
        $expected = '/各種ロゴを下記のURLよりご利用いただけます。/u';
        $this->assertRegExp($expected, $source);

        // クレジット用のtwigが表示されていること
        $expected = '/本人認証サービス/u';
        $this->assertRegExp($expected, $source);
    }

    function testEditRender_コンビニ決済の表示()
    {
        // コンビニ決済情報取得
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array(
            'memo03' => $this->const['YAMATO_PAYID_CVS'],
        ));

        // 編集画面表示
        $crawler = $this->client->request(
            'GET',
            $this->app->url('admin_setting_shop_payment_edit', array('id' => $YamatoPaymentMethod->getId()))
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面ソース取得
        $source = $crawler->filter('#form1')->html();

        // ヤマト決済用のtwigが表示されていること
        $expected = '/各種ロゴを下記のURLよりご利用いただけます。/u';
        $this->assertRegExp($expected, $source);

        // コンビニ用のtwigが表示されていること
        $expected = '/コンビニ選択/u';
        $this->assertRegExp($expected, $source);
    }

    function testEditRender_コンビニ決済の表示_初期値未登録の場合()
    {
        // コンビニ決済情報取得
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array(
            'memo03' => $this->const['YAMATO_PAYID_CVS'],
        ));
        // 初期値を空に更新
        $YamatoPaymentMethod->setMemo05(null);

        // 編集画面表示
        $crawler = $this->client->request(
            'GET',
            $this->app->url('admin_setting_shop_payment_edit', array('id' => $YamatoPaymentMethod->getId()))
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 初期値がセットされていること
        $this->expected = file_get_contents(__DIR__ . '/../../../../Resource/template/admin/mail/cvs_21.twig');
        $this->actual = $crawler->filter('#payment_register_order_mail_body_21')->text();
        $this->verify('cvs_21');
    }

    function testEditRender_後払い決済の表示()
    {
        // 後払い決済情報取得
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array(
            'memo03' => $this->const['YAMATO_PAYID_DEFERRED'],
        ));

        // 編集画面表示
        $crawler = $this->client->request(
            'GET',
            $this->app->url('admin_setting_shop_payment_edit', array('id' => $YamatoPaymentMethod->getId()))
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面ソース取得
        $source = $crawler->filter('#form1')->html();

        // ヤマト決済用のtwigが表示されていること
        $expected = '/各種ロゴを下記のURLよりご利用いただけます。/u';
        $this->assertRegExp($expected, $source);

        // 後払い用のtwigが表示されていること
        $expected = '/クロネコ代金後払い設定/u';
        $this->assertRegExp($expected, $source);
    }

    function testEditRender_不正データの表示がエラーにならないこと()
    {
        // クレジット決済情報取得
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array(
            'memo03' => $this->const['YAMATO_PAYID_CREDIT'],
        ));

        // 決済種別を不正な値に更新
        $YamatoPaymentMethod->setMemo03('99');

        // 編集画面表示
        $crawler = $this->client->request(
            'GET',
            $this->app->url('admin_setting_shop_payment_edit', array('id' => $YamatoPaymentMethod->getId()))
        );
        // エラーにならない
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面ソース取得
        $source = $crawler->filter('#form1')->html();

        // クレジット用のtwigが表示されていないこと
        $expected = '/本人認証サービス/u';
        $this->assertNotRegExp($expected, $source);
    }

    function testNewComplete_新規登録処理が正常に処理されること()
    {
        // ヤマト決済以外の入力データ
        $form = $this->createFormData('テスト');

        // 更新実行
        $this->client->request(
            'POST',
            $this->app->url('admin_setting_shop_payment_new'),
            array(
                'payment_register' => $form,
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_setting_shop_payment')));
    }

    function testEditComplete_ヤマト決済以外の更新処理が正常に処理されること()
    {
        // ヤマト決済以外の入力データ
        $form = $this->createFormData('銀行振込');

        // 更新実行
        $this->client->request(
            'POST',
            $this->app->url('admin_setting_shop_payment_edit', array('id' => 1)),
            array(
                'payment_register' => $form,
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_setting_shop_payment')));
    }

    function testEditComplete_クレジット決済の更新処理が正常に処理されること()
    {
        // クレジット決済の入力データ
        $form = $this->createCreditFormData('クレジットカード決済');

        // クレジット決済情報取得
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array(
            'memo03' => $this->const['YAMATO_PAYID_CREDIT'],
        ));
        $expected = $YamatoPaymentMethod->getMemo05();

        // 更新実行
        $this->client->request(
            'POST',
            $this->app->url('admin_setting_shop_payment_edit', array('id' => $YamatoPaymentMethod->getId())),
            array(
                'payment_register' => $form,
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_setting_shop_payment')));

        // 決済情報の更新確認
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array(
            'memo03' => $this->const['YAMATO_PAYID_CREDIT'],
        ));
        $this->assertNotEquals($expected, $YamatoPaymentMethod->getMemo05());
    }

    function testEditComplete_コンビニ決済の更新処理が正常に処理されること()
    {
        // コンビニ決済の入力データ
        $form = $this->createConvenienceFormData('コンビニ決済');

        // コンビニ決済情報取得
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array(
            'memo03' => $this->const['YAMATO_PAYID_CVS'],
        ));
        $expected = $YamatoPaymentMethod->getMemo05();

        // 更新実行
        $this->client->request(
            'POST',
            $this->app->url('admin_setting_shop_payment_edit', array('id' => $YamatoPaymentMethod->getId())),
            array(
                'payment_register' => $form,
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_setting_shop_payment')));

        // 決済情報の更新確認
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array(
            'memo03' => $this->const['YAMATO_PAYID_CVS'],
        ));
        $this->assertNotEquals($expected, $YamatoPaymentMethod->getMemo05());
    }

    function testEditComplete_後払い決済の更新処理が正常に処理されること()
    {
        // 後払い決済の入力データ
        $form = $this->createDeferredFormData('後払い決済');

        // 後払い決済情報取得
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array(
            'memo03' => $this->const['YAMATO_PAYID_DEFERRED'],
        ));
        $YamatoPaymentMethod->setMemo05(array(
            'order_mail_title' => 'title',
            'order_mail_body' => 'body',
        ));
        $this->app['orm.em']->flush();
        $expected = $YamatoPaymentMethod->getMemo05();

        // 更新実行
        $this->client->request(
            'POST',
            $this->app->url('admin_setting_shop_payment_edit', array('id' => $YamatoPaymentMethod->getId())),
            array(
                'payment_register' => $form,
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_setting_shop_payment')));

        // 決済情報の更新確認
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array(
            'memo03' => $this->const['YAMATO_PAYID_DEFERRED'],
        ));
        $this->assertNotEquals($expected, $YamatoPaymentMethod->getMemo05());
    }

    function testEditComplete_不正データの更新処理が正常に処理されること()
    {
        // クレジット決済の入力データ
        $form = $this->createFormData('不正なデータ');

        // クレジット決済情報取得
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array(
            'memo03' => $this->const['YAMATO_PAYID_CREDIT'],
        ));

        // 決済種別を不正な値に更新
        $YamatoPaymentMethod->setMemo03('99');

        // 事前確認
        $this->assertNotEmpty($YamatoPaymentMethod->getMemo05());

        // 更新実行
        $this->client->request(
            'POST',
            $this->app->url('admin_setting_shop_payment_edit', array('id' => $YamatoPaymentMethod->getId())),
            array(
                'payment_register' => $form,
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_setting_shop_payment')));

        // 決済情報の更新確認
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array(
            'memo03' => '99',
        ));
        $this->assertEmpty($YamatoPaymentMethod->getMemo05());
    }

}
