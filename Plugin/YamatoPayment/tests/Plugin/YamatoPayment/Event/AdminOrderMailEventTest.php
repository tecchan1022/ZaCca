<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */

namespace Plugin\YamatoPayment\Event;

use Eccube\Entity\MailHistory;
use Eccube\Entity\Order;
use Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip;

class AdminOrderMailEventTest extends AbstractEventTestCase
{
    /** @var Order */
    protected $Order;
    /** @var array */
    protected $YamatoShippingDelivSlips;
    /** @var array */
    protected $SubData;

    function setUp()
    {
        parent::setUp();
        $this->adminLogIn();

        // 受注情報作成
        $this->Order = $this->createOrderData();
        // 送り状番号情報作成
        $this->YamatoShippingDelivSlips = $this->createYamatoShippingDelivSlip($this->Order);
        // プラグイン設定　追跡情報の表示　0:利用する　1:利用しない
        $this->SubData = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $this->SubData['ycf_deliv_disp'] = 0;
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->SubData);
    }

    /**
     * @param integer $template_id
     * @return array
     */
    function createFormData($template_id)
    {
        /** @var \Faker\Generator $faker */
        $faker = $this->getFaker();
        $form = array(
            'template' => $template_id,
            'subject' => $faker->word,
            'header' => $faker->paragraph,
            'footer' => $faker->paragraph,
            '_token' => 'dummy'
        );
        return $form;
    }

    function testConfirm_ヤマト決済でない場合も正常に処理される()
    {
        // 注文完了メール
        $form = $this->createFormData(1);

        // リクエスト実行
        $this->client->request(
            'POST',
            $this->app->url('admin_order_mail', array('id' => $this->Order->getId())),
            array(
                'mail' => $form,
                'mode' => 'confirm'
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    function testConfirm_ヤマト決済ならメール本文に決済情報が差し込まれる()
    {
        // クレジット決済情報作成
        $this->createOrderPaymentDataCredit($this->Order);

        // 注文完了メール
        $form = $this->createFormData(1);

        // リクエスト実行
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order_mail', array('id' => $this->Order->getId())),
            array(
                'mail' => $form,
                'mode' => 'confirm'
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面のソースを取得
        $source = $crawler->filter('#confirm_box__item_body')->html();

        // メール本文に決済情報が含まれていること
        $this->assertContains('クレジットカード情報', $source);
    }

    function testConfirm_配送完了メール_クレジット決済用の荷物問い合わせ情報が差し込まれる()
    {
        // クレジット決済情報作成
        $this->createOrderPaymentDataCredit($this->Order);

        // 配送完了メール
        $form = $this->createFormData(9625);

        // リクエスト実行
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order_mail', array('id' => $this->Order->getId())),
            array(
                'mail' => $form,
                'mode' => 'confirm'
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面のソースを取得
        $source = $crawler->filter('#confirm_box__item_body')->html();

        // メール本文に荷物問い合わせ情報が含まれていること
        $this->assertContains('クロネコヤマトの荷物お問い合わせシステム', $source);

        // メール本文に送り状番号が含まれていること
        foreach ($this->YamatoShippingDelivSlips as $YamatoShippingDelivSlip) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $delivSlip = $YamatoShippingDelivSlip->getDelivSlipNumber();
            $this->assertContains($delivSlip, $source);
            $delivSlipUrl = $YamatoShippingDelivSlip->getDelivSlipUrl();
            $this->assertContains($delivSlipUrl, $source);
        }
    }

    function testConfirm_配送完了メール_後払い決済用の荷物問い合わせ情報が差し込まれる()
    {
        // 後払い決済情報作成
        $this->createOrderPaymentDataDeferred($this->Order);

        // 配送完了メール
        $form = $this->createFormData(9625);

        // リクエスト実行
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order_mail', array('id' => $this->Order->getId())),
            array(
                'mail' => $form,
                'mode' => 'confirm'
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面のソースを取得
        $source = $crawler->filter('#confirm_box__item_body')->html();

        // メール本文に荷物問い合わせ情報が含まれていること
        $this->assertContains('クロネコヤマトの荷物お問い合わせシステム', $source);

        // メール本文に送り状番号が含まれていること
        foreach ($this->YamatoShippingDelivSlips as $YamatoShippingDelivSlip) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $delivSlip = $YamatoShippingDelivSlip->getDelivSlipNumber();
            $this->assertContains($delivSlip, $source);
            $delivSlipUrl = $this->const['DEFERRED_DELIV_SLIP_URL'];
            $this->assertContains($delivSlipUrl, $source);
        }
    }

    function testConfirm_配送完了メール_追跡情報を表示しない_後払い決済用の荷物問い合わせ情報が差し込まれない()
    {
        // プラグイン設定　追跡情報の表示　1:利用しない
        $this->SubData['ycf_deliv_disp'] = 1;
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->SubData);

        // 後払い決済情報作成
        $this->createOrderPaymentDataDeferred($this->Order);

        // 配送完了メール
        $form = $this->createFormData(9625);

        // リクエスト実行
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order_mail', array('id' => $this->Order->getId())),
            array(
                'mail' => $form,
                'mode' => 'confirm'
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面のソースを取得
        $source = $crawler->filter('#confirm_box__item_body')->html();

        // メール本文に荷物問い合わせ情報が含まれていること
        $this->assertNotContains('クロネコヤマトの荷物お問い合わせシステム', $source);
    }

    function testComplete_ヤマト決済でない場合も正常に処理される()
    {
        // 注文完了メール
        $form = $this->createFormData(1);

        // リクエスト実行
        $this->client->request(
            'POST',
            $this->app->url('admin_order_mail', array('id' => $this->Order->getId())),
            array(
                'mail' => $form,
                'mode' => 'complete'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    function testComplete_ヤマト決済ならメール本文に決済情報が差し込まれる()
    {
        // クレジット決済情報作成
        $this->createOrderPaymentDataCredit($this->Order);

        // 注文完了メール
        $form = $this->createFormData(1);

        // リクエスト実行
        $this->client->request(
            'POST',
            $this->app->url('admin_order_mail', array('id' => $this->Order->getId())),
            array(
                'mail' => $form,
                'mode' => 'complete'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect());

        // メール送信メッセージを取得
        $Messages = $this->getMailCatcherMessages();
        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        $Message = $this->parseMailCatcherSource($Message);

        // 送信メール本文に決済情報が含まれていること
        $this->assertContains('クレジットカード情報', $Message);

        // メール送信履歴データ取得
        /** @var MailHistory $MailHistory */
        $MailHistory = $this->app['eccube.repository.mail_history']->findOneBy(array(
            'Order' => $this->Order->getId(),
        ));

        // 送信履歴メール本文に決済情報が含まれていること
        $this->assertContains('クレジットカード情報', $MailHistory->getMailBody());
    }

    function testComplete_配送完了メール_クレジット決済用の荷物問い合わせ情報が差し込まれる()
    {
        // クレジット決済情報作成
        $this->createOrderPaymentDataCredit($this->Order);

        // 配送完了メール
        $form = $this->createFormData(9625);

        // リクエスト実行
        $this->client->request(
            'POST',
            $this->app->url('admin_order_mail', array('id' => $this->Order->getId())),
            array(
                'mail' => $form,
                'mode' => 'complete'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect());

        // メールメッセージを取得
        $Messages = $this->getMailCatcherMessages();
        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        $Message = $this->parseMailCatcherSource($Message);

        // メール本文に決済情報が含まれていること
        $this->assertContains('クロネコヤマトの荷物お問い合わせシステム', $Message);

        // メール本文に送り状番号が含まれていること
        foreach ($this->YamatoShippingDelivSlips as $YamatoShippingDelivSlip) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $delivSlip = $YamatoShippingDelivSlip->getDelivSlipNumber();
            $this->assertContains($delivSlip, $Message);
            $delivSlipUrl = $YamatoShippingDelivSlip->getDelivSlipUrl();
            $this->assertContains($delivSlipUrl, $Message);
        }

        // メール送信履歴データ取得
        /** @var MailHistory $MailHistory */
        $MailHistory = $this->app['eccube.repository.mail_history']->findOneBy(array(
            'Order' => $this->Order->getId(),
        ));

        // メール本文に送り状番号が含まれていること
        foreach ($this->YamatoShippingDelivSlips as $YamatoShippingDelivSlip) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $delivSlip = $YamatoShippingDelivSlip->getDelivSlipNumber();
            $this->assertContains($delivSlip, $MailHistory->getMailBody());
            $delivSlipUrl = $YamatoShippingDelivSlip->getDelivSlipUrl();
            $this->assertContains($delivSlipUrl, $MailHistory->getMailBody());
        }
    }

    function testComplete_配送完了メール_後払い決済用の荷物問い合わせ情報が差し込まれる()
    {
        // 後払い決済情報作成
        $this->createOrderPaymentDataDeferred($this->Order);

        // 配送完了メール
        $form = $this->createFormData(9625);

        // リクエスト実行
        $this->client->request(
            'POST',
            $this->app->url('admin_order_mail', array('id' => $this->Order->getId())),
            array(
                'mail' => $form,
                'mode' => 'complete'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect());

        // メールメッセージを取得
        $Messages = $this->getMailCatcherMessages();
        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        $Message = $this->parseMailCatcherSource($Message);

        // メール本文に決済情報が含まれていること
        $this->assertContains('クロネコヤマトの荷物お問い合わせシステム', $Message);

        // メール本文に送り状番号が含まれていること
        foreach ($this->YamatoShippingDelivSlips as $YamatoShippingDelivSlip) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $delivSlip = $YamatoShippingDelivSlip->getDelivSlipNumber();
            $this->assertContains($delivSlip, $Message);
            $delivSlipUrl = $this->const['DEFERRED_DELIV_SLIP_URL'];
            $this->assertContains($delivSlipUrl, $Message);
        }

        // メール送信履歴データ取得
        /** @var MailHistory $MailHistory */
        $MailHistory = $this->app['eccube.repository.mail_history']->findOneBy(array(
            'Order' => $this->Order->getId(),
        ));

        // メール本文に送り状番号が含まれていること
        foreach ($this->YamatoShippingDelivSlips as $YamatoShippingDelivSlip) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $delivSlip = $YamatoShippingDelivSlip->getDelivSlipNumber();
            $this->assertContains($delivSlip, $MailHistory->getMailBody());
            $delivSlipUrl = $this->const['DEFERRED_DELIV_SLIP_URL'];
            $this->assertContains($delivSlipUrl, $MailHistory->getMailBody());
        }
    }

    function testComplete_配送完了メール_追跡情報を表示しない_後払い決済用の荷物問い合わせ情報が差し込まれない()
    {
        // プラグイン設定　追跡情報の表示　0:利用する　1:利用しない
        $this->SubData['ycf_deliv_disp'] = 1;
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->SubData);

        // 後払い決済情報作成
        $this->createOrderPaymentDataDeferred($this->Order);

        // 配送完了メール
        $form = $this->createFormData(9625);

        // リクエスト実行
        $this->client->request(
            'POST',
            $this->app->url('admin_order_mail', array('id' => $this->Order->getId())),
            array(
                'mail' => $form,
                'mode' => 'complete'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect());

        // メールメッセージを取得
        $Messages = $this->getMailCatcherMessages();
        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        $Message = $this->parseMailCatcherSource($Message);

        // メール本文に決済情報が含まれないこと
        $this->assertNotContains('クロネコヤマトの荷物お問い合わせシステム', $Message);

        // メール送信履歴データ取得
        /** @var MailHistory $MailHistory */
        $MailHistory = $this->app['eccube.repository.mail_history']->findOneBy(array(
            'Order' => $this->Order->getId(),
        ));

        // メール送信履歴本文に決済情報が含まれないこと
        $this->assertNotContains('クロネコヤマトの荷物お問い合わせシステム', $MailHistory->getMailBody());
    }

    function testMailAllConfirm_ヤマト決済でない場合も正常に処理される()
    {
        $ids = array();
        for ($i = 0; $i < 2; $i++) {
            $Order = $this->createOrderData();
            $ids[] = $Order->getId();
        }

        // 注文完了メール
        $form = $this->createFormData(1);

        // リクエスト実行
        $this->client->request(
            'POST',
            $this->app->url('admin_order_mail_all'),
            array(
                'mail' => $form,
                'mode' => 'confirm',
                'ids' => implode(',', $ids)
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    function testMailAllConfirm_ヤマト決済ならメール本文に決済情報が差し込まれる()
    {
        $ids = array();
        for ($i = 0; $i < 2; $i++) {
            $Order = $this->createOrderData();
            $ids[] = $Order->getId();
            // クレジット決済情報作成
            $this->createOrderPaymentDataCredit($Order);
        }

        // 注文完了メール
        $form = $this->createFormData(1);

        // リクエスト実行
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order_mail_all'),
            array(
                'mail' => $form,
                'mode' => 'confirm',
                'ids' => implode(',', $ids)
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面のソースを取得
        $source = $crawler->filter('#confirm_box__item_body')->html();

        // メール本文に決済情報が含まれていること
        $this->assertContains('クレジットカード情報', $source);
    }

    function testMailAllConfirm_配送完了メール_クレジット決済用の荷物問い合わせ情報が差し込まれる()
    {
        $ids = array();
        for ($i = 0; $i < 2; $i++) {
            $Order = $this->createOrderData();
            $ids[] = $Order->getId();
            // クレジット決済情報作成
            $this->createOrderPaymentDataCredit($Order);
            // 送り状番号情報作成
            $YamatoShippingDelivSlips = $this->createYamatoShippingDelivSlip($Order);
            if ($i == 0) {
                $this->YamatoShippingDelivSlips = $YamatoShippingDelivSlips;
            }
        }

        // 配送完了メール
        $form = $this->createFormData(9625);

        // リクエスト実行
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order_mail_all'),
            array(
                'mail' => $form,
                'mode' => 'confirm',
                'ids' => implode(',', $ids)
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面のソースを取得
        $source = $crawler->filter('#confirm_box__item_body')->html();

        // メール本文に荷物問い合わせ情報が含まれていること
        $this->assertContains('クロネコヤマトの荷物お問い合わせシステム', $source);

        // メール本文に送り状番号が含まれていること
        foreach ($this->YamatoShippingDelivSlips as $YamatoShippingDelivSlip) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $delivSlip = $YamatoShippingDelivSlip->getDelivSlipNumber();
            $this->assertContains($delivSlip, $source);
            $delivSlipUrl = $YamatoShippingDelivSlip->getDelivSlipUrl();
            $this->assertContains($delivSlipUrl, $source);
        }
    }

    function testMailAllConfirm_配送完了メール_後払い決済用の荷物問い合わせ情報が差し込まれる()
    {
        $ids = array();
        for ($i = 0; $i < 2; $i++) {
            $Order = $this->createOrderData();
            $ids[] = $Order->getId();
            // 後払い決済情報作成
            $this->createOrderPaymentDataDeferred($Order);
            // 送り状番号情報作成
            $YamatoShippingDelivSlips = $this->createYamatoShippingDelivSlip($Order);
            if ($i == 0) {
                $this->YamatoShippingDelivSlips = $YamatoShippingDelivSlips;
            }
        }

        // 配送完了メール
        $form = $this->createFormData(9625);

        // リクエスト実行
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order_mail_all'),
            array(
                'mail' => $form,
                'mode' => 'confirm',
                'ids' => implode(',', $ids)
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面のソースを取得
        $source = $crawler->filter('#confirm_box__item_body')->html();

        // メール本文に荷物問い合わせ情報が含まれていること
        $this->assertContains('クロネコヤマトの荷物お問い合わせシステム', $source);

        // メール本文に送り状番号が含まれていること
        foreach ($this->YamatoShippingDelivSlips as $YamatoShippingDelivSlip) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $delivSlip = $YamatoShippingDelivSlip->getDelivSlipNumber();
            $this->assertContains($delivSlip, $source);
            $delivSlipUrl = $this->const['DEFERRED_DELIV_SLIP_URL'];
            $this->assertContains($delivSlipUrl, $source);
        }
    }

    function testMailAllConfirm_配送完了メール_追跡情報を表示しない_後払い決済用の荷物問い合わせ情報が差し込まれない()
    {
        // プラグイン設定　追跡情報の表示　0:利用する　1:利用しない
        $this->SubData['ycf_deliv_disp'] = 1;
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->SubData);

        $ids = array();
        for ($i = 0; $i < 2; $i++) {
            $Order = $this->createOrderData();
            $ids[] = $Order->getId();
            // 後払い決済情報作成
            $this->createOrderPaymentDataDeferred($Order);
            // 送り状番号情報作成
            $YamatoShippingDelivSlips = $this->createYamatoShippingDelivSlip($Order);
            if ($i == 0) {
                $this->YamatoShippingDelivSlips = $YamatoShippingDelivSlips;
            }
        }

        // 配送完了メール
        $form = $this->createFormData(9625);

        // リクエスト実行
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order_mail_all'),
            array(
                'mail' => $form,
                'mode' => 'confirm',
                'ids' => implode(',', $ids)
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面のソースを取得
        $source = $crawler->filter('#confirm_box__item_body')->html();

        // メール本文に荷物問い合わせ情報が含まれないこと
        $this->assertNotContains('クロネコヤマトの荷物お問い合わせシステム', $source);
    }

    function testMailAllComplete_ヤマト決済でない場合も正常に処理される()
    {
        $ids = array();
        for ($i = 0; $i < 2; $i++) {
            $Order = $this->createOrderData();
            $ids[] = $Order->getId();
        }

        // 注文完了メール
        $form = $this->createFormData(1);

        // リクエスト実行
        $this->client->request(
            'POST',
            $this->app->url('admin_order_mail_all'),
            array(
                'mail' => $form,
                'mode' => 'complete',
                'ids' => implode(',', $ids)
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    function testMailAllComplete_ヤマト決済ならメール本文に決済情報が差し込まれる()
    {
        $ids = array();
        for ($i = 0; $i < 2; $i++) {
            $Order = $this->createOrderData();
            $ids[] = $Order->getId();
            // クレジット決済情報作成
            $this->createOrderPaymentDataCredit($Order);
            if ($i == 0) {
                $this->Order = $Order;
            }
        }

        // 注文完了メール
        $form = $this->createFormData(1);

        // リクエスト実行
        $this->client->request(
            'POST',
            $this->app->url('admin_order_mail_all'),
            array(
                'mail' => $form,
                'mode' => 'complete',
                'ids' => implode(',', $ids)
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect());

        // メール送信メッセージを取得
        $Messages = $this->getMailCatcherMessages();
        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        $Message = $this->parseMailCatcherSource($Message);

        // 送信メール本文に決済情報が含まれていること
        $this->assertContains('クレジットカード情報', $Message);

        // メール送信履歴データ取得
        /** @var MailHistory $MailHistory */
        $MailHistory = $this->app['eccube.repository.mail_history']->findOneBy(array(
            'Order' => $this->Order->getId(),
        ));

        // 送信履歴メール本文に決済情報が含まれていること
        $this->assertContains('クレジットカード情報', $MailHistory->getMailBody());
    }

    function testMailAllComplete_配送完了メール_クレジット決済用の荷物問い合わせ情報が差し込まれる()
    {
        $ids = array();
        for ($i = 0; $i < 2; $i++) {
            $Order = $this->createOrderData();
            $ids[] = $Order->getId();
            // クレジット決済情報作成
            $this->createOrderPaymentDataCredit($Order);
            // 送り状番号情報作成
            $YamatoShippingDelivSlips = $this->createYamatoShippingDelivSlip($Order);
            if ($i == 0) {
                $this->YamatoShippingDelivSlips = $YamatoShippingDelivSlips;
                $this->Order = $Order;
            }
        }

        // 配送完了メール
        $form = $this->createFormData(9625);

        // リクエスト実行
        $this->client->request(
            'POST',
            $this->app->url('admin_order_mail_all'),
            array(
                'mail' => $form,
                'mode' => 'complete',
                'ids' => implode(',', $ids)
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect());

        // メールメッセージを取得
        $Messages = $this->getMailCatcherMessages();
        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        $Message = $this->parseMailCatcherSource($Message);

        // メール本文に決済情報が含まれていること
        $this->assertContains('クロネコヤマトの荷物お問い合わせシステム', $Message);

        // メール本文に送り状番号が含まれていること
        foreach ($this->YamatoShippingDelivSlips as $YamatoShippingDelivSlip) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $delivSlip = $YamatoShippingDelivSlip->getDelivSlipNumber();
            $this->assertContains($delivSlip, $Message);
            $delivSlipUrl = $YamatoShippingDelivSlip->getDelivSlipUrl();
            $this->assertContains($delivSlipUrl, $Message);
        }

        // メール送信履歴データ取得
        /** @var MailHistory $MailHistory */
        $MailHistory = $this->app['eccube.repository.mail_history']->findOneBy(array(
            'Order' => $this->Order->getId(),
        ));

        // メール本文に送り状番号が含まれていること
        foreach ($this->YamatoShippingDelivSlips as $YamatoShippingDelivSlip) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $delivSlip = $YamatoShippingDelivSlip->getDelivSlipNumber();
            $this->assertContains($delivSlip, $MailHistory->getMailBody());
            $delivSlipUrl = $YamatoShippingDelivSlip->getDelivSlipUrl();
            $this->assertContains($delivSlipUrl, $MailHistory->getMailBody());
        }
    }

    function testMailAllComplete_配送完了メール_後払い決済用の荷物問い合わせ情報が差し込まれる()
    {
        $ids = array();
        for ($i = 0; $i < 2; $i++) {
            $Order = $this->createOrderData();
            $ids[] = $Order->getId();
            // 後払い決済情報作成
            $this->createOrderPaymentDataDeferred($Order);
            // 送り状番号情報作成
            $YamatoShippingDelivSlips = $this->createYamatoShippingDelivSlip($Order);
            if ($i == 0) {
                $this->YamatoShippingDelivSlips = $YamatoShippingDelivSlips;
                $this->Order = $Order;
            }
        }

        // 配送完了メール
        $form = $this->createFormData(9625);

        // リクエスト実行
        $this->client->request(
            'POST',
            $this->app->url('admin_order_mail_all'),
            array(
                'mail' => $form,
                'mode' => 'complete',
                'ids' => implode(',', $ids)
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect());

        // メールメッセージを取得
        $Messages = $this->getMailCatcherMessages();
        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        $Message = $this->parseMailCatcherSource($Message);

        // メール本文に決済情報が含まれていること
        $this->assertContains('クロネコヤマトの荷物お問い合わせシステム', $Message);

        // メール本文に送り状番号が含まれていること
        foreach ($this->YamatoShippingDelivSlips as $YamatoShippingDelivSlip) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $delivSlip = $YamatoShippingDelivSlip->getDelivSlipNumber();
            $this->assertContains($delivSlip, $Message);
            $delivSlipUrl = $this->const['DEFERRED_DELIV_SLIP_URL'];
            $this->assertContains($delivSlipUrl, $Message);
        }

        // メール送信履歴データ取得
        /** @var MailHistory $MailHistory */
        $MailHistory = $this->app['eccube.repository.mail_history']->findOneBy(array(
            'Order' => $this->Order->getId(),
        ));

        // メール本文に送り状番号が含まれていること
        foreach ($this->YamatoShippingDelivSlips as $YamatoShippingDelivSlip) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $delivSlip = $YamatoShippingDelivSlip->getDelivSlipNumber();
            $this->assertContains($delivSlip, $MailHistory->getMailBody());
            $delivSlipUrl = $this->const['DEFERRED_DELIV_SLIP_URL'];
            $this->assertContains($delivSlipUrl, $MailHistory->getMailBody());
        }
    }

    function testMailAllComplete_配送完了メール_追跡情報を表示しない_後払い決済用の荷物問い合わせ情報が差し込まれない()
    {
        // プラグイン設定　追跡情報の表示　0:利用する　1:利用しない
        $this->SubData['ycf_deliv_disp'] = 1;
        $this->app['yamato_payment.util.plugin']->registerUserSettings($this->SubData);

        $ids = array();
        for ($i = 0; $i < 2; $i++) {
            $Order = $this->createOrderData();
            $ids[] = $Order->getId();
            // 後払い決済情報作成
            $this->createOrderPaymentDataDeferred($Order);
            // 送り状番号情報作成
            $YamatoShippingDelivSlips = $this->createYamatoShippingDelivSlip($Order);
            if ($i == 0) {
                $this->YamatoShippingDelivSlips = $YamatoShippingDelivSlips;
                $this->Order = $Order;
            }
        }

        // 配送完了メール
        $form = $this->createFormData(9625);

        // リクエスト実行
        $this->client->request(
            'POST',
            $this->app->url('admin_order_mail_all'),
            array(
                'mail' => $form,
                'mode' => 'complete',
                'ids' => implode(',', $ids)
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect());

        // メールメッセージを取得
        $Messages = $this->getMailCatcherMessages();
        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        $Message = $this->parseMailCatcherSource($Message);

        // メール本文に決済情報が含まれないこと
        $this->assertNotContains('クロネコヤマトの荷物お問い合わせシステム', $Message);

        // メール送信履歴データ取得
        /** @var MailHistory $MailHistory */
        $MailHistory = $this->app['eccube.repository.mail_history']->findOneBy(array(
            'Order' => $this->Order->getId(),
        ));

        // メール送信履歴本文に決済情報が含まれないこと
        $this->assertNotContains('クロネコヤマトの荷物お問い合わせシステム', $MailHistory->getMailBody());
    }
    
}
