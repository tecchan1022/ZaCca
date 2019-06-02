<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Web;

use Eccube\Application;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\ProductType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderDetail;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Entity\ProductStock;
use Eccube\Service\OrderService;
use Eccube\Service\ShoppingService;
use Eccube\Util\Str;
use Plugin\YamatoPayment\Controller\PaymentController;
use Plugin\YamatoPayment\Entity\PaymentExtension;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Plugin\YamatoPayment\Util\PluginUtil;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class PaymentControllerTest extends AbstractWebTestCase
{
    /** @var  PaymentController */
    var $object;

    /** @var Order */
    var $Order;

    /** @var PluginUtil */
    var $PluginUtil;

    /** @var  array */
    var $userSettings;

    protected $const;

    public function setUp()
    {
        parent::setUp();
        $this->object = new PaymentController();
        $this->const = $this->app['config']['YamatoPayment']['const'];

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

        // 複数配送を有効にする
        $this->setMultipleShipping(1);

        // 受注情報作成
        $pre_order_id = sha1(Str::random(32));
        $this->Order = $this->createOrder($this->createCustomer());
        $this->Order->setPreOrderId($pre_order_id);

        // 購入数を1に設定
        foreach ($this->Order->getOrderDetails() as $OrderDetail) {
            /** @var OrderDetail $OrderDetail */
            $OrderDetail->setQuantity(1);
        }

        $this->app['orm.em']->flush();

        // プラグイン設定
        $this->PluginUtil = $this->app['yamato_payment.util.plugin'];
        $this->userSettings = $this->PluginUtil->getUserSettings();

        // カート画面
        $this->scenarioCartIn($pre_order_id);

        // ShoppingServiceのモック作成
        $this->app['eccube.service.shopping'] = $this->createShoppingService(true);
    }

    protected function createFormDataCredit(Order $Order)
    {
        // 支払方法の設定情報を取得する（決済モジュール管理対象である場合、内部識別コードを同時に設定する）
        $paymentExtension = $this->app['yamato_payment.util.payment']->getPaymentTypeConfig($Order->getPayment()->getId());
        $paymentInfo = $paymentExtension->getArrPaymentConfig();

        $sendPayMethod = $this->app['yamato_payment.util.payment']->getCreditPayMethod();
        $listPayMethod = array();
        if (isset($paymentInfo['pay_way'])) {
            foreach ((array)$paymentInfo['pay_way'] as $pay_method) {
                if (!is_null($sendPayMethod[$pay_method])) {
                    $listPayMethod[$pay_method] = $sendPayMethod[$pay_method];
                }
            }
        }

        $form = array(
            '_token' => 'dummy',
            // カード番号
            'card_no' => '1234567890123456',
            // 有効期限(月)
            'card_exp_month' => '05',
            // 有効期限(年)
            'card_exp_year' => '28',
            // カード名義
            'card_owner' => 'KURONEKO YAMATO',
            // セキュリティコード
            'security_code' => '1234',
            // 支払方法
            'pay_way' => array_rand($listPayMethod, 1),
            // このカードを登録する 登録する：1 登録しない：0
            'register_card' => 1,
            // 登録されたカードを使用する 使用する：1 使用しない：0
            'use_registed_card' => false,
        );
        return $form;
    }

    function testIndex_creditProcessに遷移する()
    {
        // 支払方法IDを取得（クレジットカード決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // PaymentControllerのモック作成
        $this->object = $this->createPaymentContoller('creditProcess');

        /*
         * クレジットカード決済画面遷移実行
         */
        // 「creditProcessが一度よばれること」メソッドが一度だけ呼び出されること
        $this->object->index($this->app, new Request());
    }

    function testIndex_creditProcessに遷移する_trueが返る()
    {
        // 支払方法IDを取得（クレジットカード決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // PaymentControllerのモック作成
        $this->object = $this->createPaymentContoller_bool('creditProcess');

        /*
         * クレジットカード決済画面遷移実行
         */
        // 「creditProcessがよばれること」trueが返ること
        $this->assertTrue($this->object->index($this->app, new Request()));
    }

    function testIndex_cvsProcessに遷移する()
    {
        // 支払方法IDを取得（コンビニ決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CVS']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // PaymentControllerのモック作成
        $this->object = $this->createPaymentContoller('cvsProcess');

        /*
         * コンビニ決済画面遷移実行
         */
        // 「cvsProcessが一度よばれること」メソッドが一度だけ呼び出されること
        $this->object->index($this->app, new Request());
    }

    function testIndex_cvsProcessに遷移する_trueが返る()
    {
        // 支払方法IDを取得（コンビニ決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CVS']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // PaymentControllerのモック作成
        $this->object = $this->createPaymentContoller_bool('cvsProcess');

        /*
         * コンビニ決済画面遷移実行
         */
        // 「cvsProcessがよばれること」trueが返ること
        $this->assertTrue($this->object->index($this->app, new Request()));
    }

    function testIndex_deferredProcessに遷移する()
    {
        // 支払方法IDを取得（クロネコ代金後払い決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_DEFERRED']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // PaymentControllerのモック作成
        $this->object = $this->createPaymentContoller('deferredProcess');

        /*
         * クロネコ代金後払い決済画面遷移実行
         */
        // 「deferredProcessが一度よばれること」メソッドが一度だけ呼び出されること
        $this->object->index($this->app, new Request());
    }

    function testIndex_deferredProcessに遷移する_trueが返る()
    {
        // 支払方法IDを取得（クロネコ代金後払い決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_DEFERRED']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // PaymentControllerのモック作成
        $this->object = $this->createPaymentContoller_bool('deferredProcess');

        /*
         * クロネコ代金後払い決済画面遷移実行
         */
        // 「deferredProcessがばれること」trueが返ること
        $this->assertTrue($this->object->index($this->app, new Request()));
    }

    function testIndex_不正なページ移動がある場合_エラー画面に遷移する()
    {
        // 前ページの正当性を削除
        $this->app['session']->remove('yamato_payment.pre_regist_success');
        $this->app['session']->save();

        /*
         * クレジットカード決済実行
         */
        $this->client->request(
            'GET',
            $this->app->url('yamato_shopping_payment')
        );

        // エラー画面へリダイレクトすること
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('shopping_error')));
    }

    function testIndex_カートの中に予約商品と通常商品が混在する場合_カート画面に遷移する()
    {
        // カートのロックを外す
        $this->app['eccube.service.cart']->unlock();
        $this->scenarioReserveItemCartIn($this->app['config']['YamatoPayment']['const']['PRODUCT_TYPE_ID_RESERVE'], 2);

        /*
         * クレジットカード決済実行
         */
        $this->client->request(
            'GET',
            $this->app->url('yamato_shopping_payment')
        );

        // エラー画面へリダイレクトすること
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('cart')));
    }

    function testIndex_カートがロックされていない場合_カート画面に遷移する()
    {
        // カートのロックを外す
        $this->app['eccube.service.cart']->unlock();

        /*
         * クレジットカード決済実行
         */
        $this->client->request(
            'GET',
            $this->app->url('yamato_shopping_payment')
        );

        // エラー画面へリダイレクトすること
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('cart')));
    }

    function testIndex_商品公開ステータスチェックにエラーがある場合_エラー画面に遷移する()
    {
        // 受注情報の作成
        $this->createErrorOrder();

        // ShoppingServiceのセット
        $this->app['eccube.service.shopping'] = new ShoppingService(
            $this->app,
            $this->app['eccube.service.cart'],
            $this->app['eccube.service.order']
        );

        // 商品公開ステータスを非公開に設定
        foreach ($this->Order->getOrderDetails() as $OrderDetail) {
            /** @var OrderDetail $OrderDetail */
            $Product = $OrderDetail->getProduct();
            $Disp = $this->app['eccube.repository.master.disp']->find(2);
            $Product->setStatus($Disp);
            $this->app['orm.em']->persist($Product);
        }
        $this->app['orm.em']->flush();

        /*
         * クレジットカード決済実行
         */
        $this->client->request(
            'GET',
            $this->app->url('yamato_shopping_payment')
        );

        // エラー画面へリダイレクトすること
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('shopping_error')));

    }

    function testIndex_商品制限数チェックにエラーがある場合_エラー画面に遷移する()
    {
        // 受注情報の作成
        $this->createErrorOrder();

        // ShoppingServiceのセット
        $this->app['eccube.service.shopping'] = new ShoppingService(
            $this->app,
            $this->app['eccube.service.cart'],
            $this->app['eccube.service.order']
        );

        // 商品制限数を1に設定 受注数を2に設定
        foreach ($this->Order->getOrderDetails() as $OrderDetail) {
            /** @var OrderDetail $OrderDetail */
            $OrderDetail->setQuantity(2);
            $ProductClass = $OrderDetail->getProductClass();
            $ProductClass->setSaleLimit(1);
            $this->app['orm.em']->persist($ProductClass);
        }
        $this->app['orm.em']->flush();

        /*
         * クレジットカード決済実行
         */
        $this->client->request(
            'GET',
            $this->app->url('yamato_shopping_payment')
        );

        // エラー画面へリダイレクトすること
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('shopping_error')));
    }

    function testIndex_在庫チェックにエラーがある場合_エラー画面に遷移する()
    {
        // 受注情報の作成
        $this->createErrorOrder();

        // ShoppingServiceのセット
        $this->app['eccube.service.shopping'] = new ShoppingService(
            $this->app,
            $this->app['eccube.service.cart'],
            $this->app['eccube.service.order']
        );

        // 在庫を0に設定
        foreach ($this->Order->getOrderDetails() as $OrderDetail) {
            /** @var OrderDetail $OrderDetail */
            $ProductStock = $this->app['orm.em']->getRepository('Eccube\Entity\ProductStock')->find(
                $OrderDetail->getProductClass()->getProductStock()->getId());
            /** @var ProductStock $ProductStock */
            $ProductStock->setStock(0);
            $this->app['orm.em']->persist($ProductStock);
        }
        $this->app['orm.em']->flush();

        /*
         * クレジットカード決済実行
         */
        $this->client->request(
            'GET',
            $this->app->url('yamato_shopping_payment')
        );

        // エラー画面へリダイレクトすること
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('shopping_error')));
    }

    private function createErrorOrder()
    {
        // 支払方法IDを取得（クレジットカード決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // クレジットカード決済の受注情報を作成
        $this->createOrderPaymentDataCredit($this->Order);
    }

    private function createPaymentContoller($process)
    {
        $mock = $this->getMock(
            'Plugin\YamatoPayment\Controller\PaymentController',
            array($process),
            array($this->app, new Request())
        );
        $mock->expects($this->once())
            ->method($process);

        return $mock;
    }

    private function createShoppingService($bool)
    {

        $mock = $this->getMock(
            'Eccube\Service\ShoppingService',
            array('isOrderProduct'),
            array($this->app, $this->app['eccube.service.cart'], new OrderService($this->app))
        );
        $mock->expects($this->any())
            ->method('isOrderProduct')
            ->will($this->returnValue($bool));

        return $mock;
    }

    private function createPaymentContoller_bool($process)
    {
        $mock = $this->getMock(
            'Plugin\YamatoPayment\Controller\PaymentController',
            array($process),
            array($this->app, new Request())
        );
        $mock->expects($this->any())
            ->method($process)
            ->will($this->returnValue(true));

        return $mock;
    }

    function testIndex_nullが返る()
    {
        // 支払方法IDを取得（ヤマト決済以外）
        $Payment = $this->app['eccube.repository.payment']->find(1);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // Nullが返ること
        $this->assertNull($this->object->index($this->app, new Request()));
    }

    function testIndex_受注データ無し_エラーメッセージが返る()
    {
        // 受注情報のないpreOrderIdをカートに設定
        $pre_order_id = sha1(Str::random(32));
        $this->app['eccube.service.cart']->setPreOrderId($pre_order_id);
        $this->app['orm.em']->flush();

        /*
         * 決済画面表示
         */
        $this->client->request(
            'GET',
            $this->app->url('yamato_shopping_payment')
        );

        // リダイレクトされること
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('shopping_error')));
    }

    function testCreditProcess_預かりカード有りの場合_カード情報の存在するクレジットカード入力画面に遷移する_最終利用日が一番新しいカードが選択されている()
    {
        // プラグイン設定
        // オプションサービスを利用する
        $this->userSettings['use_option'] = 0;
        $this->PluginUtil->registerUserSettings($this->userSettings);

        // 支払方法IDを取得（クレジットカード決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // クレジットカード決済の受注情報を作成
        $this->createOrderPaymentDataCredit($this->Order);

        /*
         * クレジットカードお預かり情報照会（MemberClientService）モック作成
         */
        // 預かりカード情報取得 三件
        $results = $this->createCardData();

        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(true, $results);

        /*
         * 決済画面表示
         */
        $crawler = $this->client->request(
            'GET',
            $this->app->url('yamato_shopping_payment')
        );

        // 正しく画面が表示されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 最終利用日が一番新しいカード（card_key=2）が選択されていること
        $actual = $crawler->filter('#card_data')->html();
        $this->assertContains('<input type="radio" id="card_key" name="regist_credit[card_key]" value="2" checked>', $actual);
    }

    function testCreditProcess_オプションサービスを利用しない_支払方法情報がない場合_カード情報の存在しないクレジットカード入力画面に遷移する()
    {
        // プラグイン設定
        // オプションサービスを利用しない
        $this->userSettings['use_option'] = 1;
        $this->PluginUtil->registerUserSettings($this->userSettings);

        // 支払方法IDを取得（クレジットカード決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // クレジットカード決済の受注情報を作成
        $this->createOrderPaymentDataCredit($this->Order);

        /*
         * PaymentUtilモック作成
         */
        $paymentTypeConfig = $this->createPaymentExtension($payment_id);
        $paymentTypeConfig->setArrPaymentConfig(array());

        // PaymentUtilをモック化
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil($paymentTypeConfig, null, null, null,null, null, true);

        /*
         * 決済画面表示
         */
        $crawler = $this->client->request(
            'GET',
            $this->app->url('yamato_shopping_payment')
        );

        // 正しく画面が表示されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // お預かり情報が存在していないこと
        $this->assertEmpty($crawler->filter('#detail_box__use_select_card'));
    }

    function testCreditProcess_クレジットカード決済_決済完了画面にリダイレクトする_注文完了メールが送信される_送信メール履歴が保存される()
    {
        // プラグイン設定
        // オプションサービスを利用する
        $this->userSettings['use_option'] = 0;
        $this->PluginUtil->registerUserSettings($this->userSettings);

        // 支払方法IDを取得（クレジットカード決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->Order->setPaymentTotal(1000);
        // 受注情報の手数料をnullに設定
        $this->Order->setCharge(null);
        $this->app['orm.em']->flush();

        // クレジットカード決済の受注情報を作成
        $this->createOrderPaymentDataCredit($this->Order);

        /*
         * クレジットカードお預かり情報照会（MemberClientService）モック作成
         */
        // 預かりカード情報取得 三件
        $results = $this->createCardData();

        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(true, $results);

        /*
         * 決済モジュール 決済処理 クレジットカード（CreditClientService）モック作成
         */
        // クレジットカード決済処理（CreditClientService）モック作成
        // 決済結果取得
        $getResults = array(
            'errorCode' => 'A012050002',
            'info_use_threeD' => null,
            'threeDAuthHtml' => '',
            'threeDToken' => '',
        );
        $this->app['yamato_payment.service.client.credit'] = $this->createCreditClientService(false, true, $getResults);

        // フォームデータの作成
        $formData = $this->createFormDataCredit($this->Order);

        // クレジット決済の支払方法をセット
        $_POST['type_submit'] = 'regist';

        /*
         * クレジットカード決済実行
         */
        $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment')
            , array('regist_credit' => $formData)
        );

        // リダイレクトすること
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('shopping_complete')));

        // 送信したメールを取得
        $Messages = $this->getMailCatcherMessages();
        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        // 注文完了メールが送信されること
        $MailTemplate = $this->app['eccube.repository.mail_template']->find(1);
        $this->assertContains($MailTemplate->getSubject(), $Message->subject);

        // 送信メール履歴が保存されること
        $this->assertNotNull($this->app['eccube.repository.mail_history']->findOneBy(array('Order' => $this->Order)));
    }

    function testCreditProcess_クレジットカード決済_決済処理の事前チェック処理の返り値がtrueの場合_決済完了画面にリダイレクトする()
    {
        // プラグイン設定
        // オプションサービスを利用する
        $this->userSettings['use_option'] = 0;
        $this->PluginUtil->registerUserSettings($this->userSettings);

        // 支払方法IDを取得（クレジットカード決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);

        // 受注状況を作成
        $orderStatus = $this->app['eccube.repository.order_status']->find($this->app['config']['order_new']);
        $this->Order->setOrderStatus($orderStatus);

        $this->app['orm.em']->flush();

        // クレジットカード決済の受注情報を作成
        $this->createOrderPaymentDataCredit($this->Order);

        /*
         * クレジットカードお預かり情報照会（MemberClientService）モック作成
         */
        // 預かりカード情報取得 三件
        $results = $this->createCardData();

        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(true, $results);

        // フォームデータの作成
        $formData = $this->createFormDataCredit($this->Order);

        /*
         * クレジットカード決済実行
         */
        $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment')
            , array('regist_credit' => $formData)
        );

        // リダイレクトすること
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('shopping_complete')));
    }

    function testCreditProcess_クレジットカード決済_決済処理の事前チェック処理の返り値が配列の場合_エラー画面に遷移する()
    {
        // プラグイン設定
        // オプションサービスを利用する
        $this->userSettings['use_option'] = 0;
        $this->PluginUtil->registerUserSettings($this->userSettings);

        // 支払方法IDを取得（クレジットカード決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);

        // 受注状況を作成
        $orderStatus = $this->app['eccube.repository.order_status']->find($this->app['config']['order_cancel']);
        $this->Order->setOrderStatus($orderStatus);

        $this->app['orm.em']->flush();

        // クレジットカード決済の受注情報を作成
        $this->createOrderPaymentDataCredit($this->Order);

        /*
         * クレジットカードお預かり情報照会（MemberClientService）モック作成
         */
        // 預かりカード情報取得 三件
        $results = $this->createCardData();

        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(true, $results);

        // フォームデータの作成
        $formData = $this->createFormDataCredit($this->Order);

        /*
         * クレジットカード決済実行
         */
        $crawler = $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment')
            , array('regist_credit' => $formData)
        );

        // 正しく画面が表示されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エラーメッセージが返ること
        $this->assertContains('注文情報が無効です。この手続きは無効となりました。', $crawler->filter('div > p')->text());
    }

    function testCreditProcess_クレジットカード決済_預かりカード情報を削除_お預かり情報を再取得したクレジットカード入力画面に遷移する()
    {
        // プラグイン設定
        // オプションサービスを利用する
        $this->userSettings['use_option'] = 0;
        $this->PluginUtil->registerUserSettings($this->userSettings);

        // ログインする
        $this->logIn($this->Order->getCustomer());

        // 支払方法IDを取得（クレジットカード決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // クレジットカード決済の受注情報を作成
        $this->createOrderPaymentDataCredit($this->Order);

        /*
         * クレジットカードお預かり情報照会（MemberClientService）モック作成
         */
        // 預かりカード情報取得 三件
        $results = $this->createCardData();

        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(true, $results);

        /*
         * PaymentUtilモック作成
         */
        $paymentTypeConfig = $this->createPaymentExtension($payment_id);

        // PaymentUtilをモック化
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil($paymentTypeConfig, null, null, null,null, null, true);

        // フォームデータの作成
        $data = $this->createFormDataCredit($this->Order);
        $formData = array(
            '_token' => 'dummy',
            'card_key' => 1,
            'pay_way' => $data['pay_way'],
            'use_registed_card' => true,
        );

        /*
         * クレジットカード決済実行
         */
        $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment')
            , array('regist_credit' => $formData, 'mode' => 'deleteCard',)
        );

        // 正しく画面が表示されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    function testCreditProcess_決済実行_3Dセキュア利用の場合_3Dセキュア認証画面へのhtmlが表示されること()
    {
        // プラグイン設定
        // オプションサービスを利用する
        $this->userSettings['use_option'] = 0;
        $this->PluginUtil->registerUserSettings($this->userSettings);

        // 支払方法IDを取得（クレジットカード決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->Order->setPaymentTotal(1000);
        // 受注情報の手数料をnullに設定
        $this->Order->setCharge(null);
        $this->app['orm.em']->flush();

        // クレジットカード決済の受注情報を作成
        $this->createOrderPaymentDataCredit($this->Order);

        /*
         * クレジットカードお預かり情報照会（MemberClientService）モック作成
         */
        // 預かりカード情報取得 三件
        $results = $this->createCardData();

        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(true, $results);

        /*
         * 決済モジュール 決済処理 クレジットカード（CreditClientService）モック作成
         */
        // クレジットカード決済処理（CreditClientService）モック作成
        // 決済結果取得
        $getResults = array(
            'errorCode' => '',
            'info_use_threeD' => null,
            'threeDAuthHtml' => '<![CDATA[<html>dummyHTML</html>]]>',
            'threeDToken' => 'dummyToken',
        );
        $this->app['yamato_payment.service.client.credit'] = $this->createCreditClientService(true, true, $getResults);

        // フォームデータの作成
        $formData = $this->createFormDataCredit($this->Order);

        // クレジット決済の支払方法をセット
        $_POST['type_submit'] = 'regist';

        /*
         * クレジットカード決済実行
         */
       try {
            // 3Dセキュアのページへリダイレクトされる
            $this->client->request(
                'POST',
                $this->app->url('yamato_shopping_payment')
                , array('regist_credit' => $formData)
             );

        } catch (\Exception $e) {
            // リダイレクトされること（リダイレクトするとExceptionが発生する）
            $this->assertContains('headers already sent', $e->getMessage());
        }
    }

    function testCreditProcess_3Dセキュア認証後_3Dセキュア決済エラーの場合_エラーメッセージが返ること()
    {
        // プラグイン設定
        // オプションサービスを利用する
        $this->userSettings['use_option'] = 0;
        $this->PluginUtil->registerUserSettings($this->userSettings);

        // 支払方法IDを取得（クレジットカード決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->Order->setPaymentTotal(1000);
        $this->app['orm.em']->flush();

        // クレジットカード決済の受注情報を作成
        $this->createOrderPaymentDataCredit($this->Order);

        /*
         * PaymentUtilモック作成
         */
        $paymentTypeConfig = $this->createPaymentExtension($payment_id);

        //受注情報の拡張データを取得
        $OrderExtension = $this->app['yamato_payment.util.payment']->getOrderPayData($this->Order->getId());

        // PaymentUtilをモック化
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil($paymentTypeConfig, null, null, $OrderExtension, null, null, true);

        /*
         * クレジットカードお預かり情報照会（MemberClientService）モック作成
         */
        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(false);

        /*
         * 決済モジュール 決済処理 クレジットカード（CreditClientService）モック作成
         */
        // クレジットカード決済処理（CreditClientService）モック作成
        // CreditClientServiceをモック化
        $this->app['yamato_payment.service.client.credit'] = $this->createCreditClientService(null, null, null, false);

        $formData = array(
            'COMP_CD' => '0000',
            'CARD_NO' => '0000',
            'CARD_EXP' => '0000',
            'ITEM_PRICE' => '0000',
            'ITEM_TAX' => '0000',
            'CUST_CD' => '0000',
            'SHOP_ID' => '000000000',
            'TERM_CD' => '0000',
            'CRD_RES_CD' => '0000',
            'RES_VE' => '0000',
            'RES_PA' => '0000',
            'RES_CODE' => '0000',
            '3D_INF' => '0000',
            '3D_TRAN_ID' => '0000',
            'SEND_DT' => '0000',
            'HASH_VALUE' => '0000',
            '_token' => 'dummy',
        );

        /*
         * クレジットカード決済実行（3Dセキュア認証後）
         */
        $crawler = $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment', array('mode' => '3dTran'))
            , $formData
        );

        // 正しく画面が表示されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エラーメッセージが返ること
        $this->assertContains('決済でエラーが発生しました', $crawler->filter('.col-md-10 .message .errormsg')->text());
    }

    private function createPaymentExtension($payment_id)
    {
        $PaymentExtension = new PaymentExtension();

        // 内部識別コード(config.yml # 支払方法種別ID)の設定を同時に行う。
        $PaymentExtension->setPaymentCode($this->const['YAMATO_PAYCODE_CREDIT']);

        // 取得した情報をヤマト支払方法情報に設定する
        // 支払方法情報取得
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->find($payment_id);
        $memo05 = $YamatoPaymentMethod->getMemo05();
        $PaymentExtension->setArrPaymentConfig($memo05);
        $PaymentExtension->setYamatoPaymentMethod($YamatoPaymentMethod);

        return $PaymentExtension;
    }

    function testCreditProcess_入力内容に不備がある場合_エラーメッセージが返る()
    {
        // プラグイン設定
        // オプションサービスを利用しない
        $this->userSettings['use_option'] = 1;
        $this->PluginUtil->registerUserSettings($this->userSettings);

        // 支払方法IDを取得（クレジットカード決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // クレジットカード決済の受注情報を作成
        $this->createOrderPaymentDataCredit($this->Order);

        // フォームデータの作成
        $formData = $this->createFormDataCredit($this->Order);
        $formData['card_no'] = '';

        /*
         * PaymentUtilモック作成
         */
        $paymentTypeConfig = $this->createPaymentExtension($payment_id);
        // PaymentUtilをモック化
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil($paymentTypeConfig, null, null, null, null, null, true);

        // クレジットカードお預かり情報照会（MemberClientService）モック作成
        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(false);

        /*
         * クレジットカード決済実行
         */
        $crawler = $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment')
            , array('regist_credit' => $formData)
        );

        // 正しく画面が表示されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エラーメッセージが返ること
        $this->assertContains('※ 入力内容に不備があります。内容をご確認ください。', $crawler->filter('.col-md-10 .message .errormsg')->text());
    }

    function testCreditProcess_預かりカード利用なし_入力内容に不備がある場合_エラーメッセージが返る()
    {
        // プラグイン設定
        // オプションサービスを利用する
        $this->userSettings['use_option'] = 0;
        $this->PluginUtil->registerUserSettings($this->userSettings);

        // 支払方法IDを取得（クレジットカード決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // クレジットカード決済の受注情報を作成
        $this->createOrderPaymentDataCredit($this->Order);

        /*
         * クレジットカードお預かり情報照会（MemberClientService）モック作成
         */
        // 預かりカード情報取得 三件
        $results = $this->createCardData();

        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(true, $results);

        // フォームデータの作成
        $formData = $this->createFormDataCredit($this->Order);
        $formData['card_no'] = '';

        /*
         * クレジットカード決済実行
         */
        $crawler = $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment')
            , array('regist_credit' => $formData)
        );

        // エラーメッセージが返ること
        $this->assertContains('※ 入力内容に不備があります。内容をご確認ください。', $crawler->filter('.col-md-10 .message .errormsg')->text());

        // 「登録カードを利用する」チェックボックスにチェックが入っていない事
        $this->assertNotContains('checked', $crawler->filter('div.checkbox')->html());

    }

    function testCreditProcess_預かりカード利用_入力内容に不備がある場合_エラーメッセージが返る()
    {
        // プラグイン設定
        // オプションサービスを利用する
        $this->userSettings['use_option'] = 0;
        $this->PluginUtil->registerUserSettings($this->userSettings);

        // 支払方法IDを取得（クレジットカード決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // クレジットカード決済の受注情報を作成
        $this->createOrderPaymentDataCredit($this->Order);

        /*
         * クレジットカードお預かり情報照会（MemberClientService）モック作成
         */
        // 預かりカード情報取得 三件
        $results = $this->createCardData();

        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(true, $results);

        // フォームデータの作成
        $formData = array(
            '_token' => 'dummy',
            // カードキー
            'card_key' => '1',
            // セキュリティコード
            'security_code' => '',
            // 支払方法
            'pay_way' => 1,
            // 登録されたカードを使用する
            'use_registed_card' => true,
        );

        /*
         * クレジットカード決済実行
         */
        $crawler = $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment')
            , array('regist_credit' => $formData)
        );

        // エラーメッセージが返ること
        $this->assertContains('※ 入力内容に不備があります。内容をご確認ください。', $crawler->filter('.col-md-10 .message .errormsg')->text());

        // 「登録カードを利用する」チェックボックスにチェックが入っている事
        $this->assertContains('checked', $crawler->filter('div.checkbox')->html());
    }

    function testCvsProcess_コンビニ選択画面に遷移する()
    {
        // 支払方法IDを取得（コンビニ決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CVS']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // コンビニ決済の受注情報を作成
        $this->createOrderPaymentDataCvs($this->Order);

        /*
         * コンビニ決済実行
         */
        $crawler = $this->client->request(
            'GET',
            $this->app->url('yamato_shopping_payment')
        );

        // 正しく画面が表示されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // コンビニ選択一覧が表示されること
        $this->assertContains('コンビニ選択', $crawler->filter('#detail_box__body_inner')->text());
    }

    function testCvsProcess_コンビニ決済_決済完了画面にリダイレクトする_注文完了メールが送信される_送信メール履歴が保存される()
    {
        // 支払方法IDを取得（コンビニ決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CVS']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->Order->setPaymentTotal(1000);
        $this->app['orm.em']->flush();

        // コンビニ決済の受注情報を作成
        $this->createOrderPaymentDataCvs($this->Order);

        /*
         * コンビニ決済 決済処理（CvsClientService）モック作成
         */
        // CvsClientServiceをモック化
        $this->app['yamato_payment.service.client.cvs'] = $this->createCvsClientService(true);

        // フォームデータの作成
        $formData = array(
            '_token' => 'dummy',
            // コンビニ　セブンイレブン
            'cvs' => 21
        );

        /*
         * コンビニ決済実行
         */
        $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment')
            , array('regist_cvs' => $formData)
        );

        // リダイレクトされること
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('shopping_complete')));

        // 送信したメールを取得
        $Messages = $this->getMailCatcherMessages();
        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        // 注文完了メールが送信されること
        $MailTemplate = $this->app['eccube.repository.mail_template']->find(1);
        $this->assertContains($MailTemplate->getSubject(), $Message->subject);

        // 送信メール履歴が保存されること
        $this->assertNotNull($this->app['eccube.repository.mail_history']->findOneBy(array('Order' => $this->Order)));
    }

    function testCvsProcess_コンビニ決済_決済処理の事前チェック処理の返り値がtrueの場合_決済完了画面にリダイレクトする()
    {
        // 支払方法IDを取得（コンビニ決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CVS']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);

        // 受注状況を作成
        $orderStatus = $this->app['eccube.repository.order_status']->find($this->app['config']['order_new']);
        $this->Order->setOrderStatus($orderStatus);

        $this->app['orm.em']->flush();

        // コンビニ決済の受注情報を作成
        $this->createOrderPaymentDataCvs($this->Order);

        /*
         * コンビニ決済 決済処理（CvsClientService）モック作成
         */
        // CvsClientServiceをモック化
        $this->app['yamato_payment.service.client.cvs'] = $this->createCvsClientService(true);

        // フォームデータの作成
        $formData = array(
            '_token' => 'dummy',
            // コンビニ　セブンイレブン
            'cvs' => 21
        );

        /*
         * コンビニ決済実行
         */
        $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment')
            , array('regist_cvs' => $formData)
        );

        // リダイレクトすること
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('shopping_complete')));
    }

    function testCvsProcess_コンビニ決済_決済処理の事前チェック処理の返り値が配列の場合_エラー画面に遷移する()
    {
        // 支払方法IDを取得（コンビニ決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CVS']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);

        // 受注状況を作成
        $orderStatus = $this->app['eccube.repository.order_status']->find($this->app['config']['order_cancel']);
        $this->Order->setOrderStatus($orderStatus);

        $this->app['orm.em']->flush();

        // コンビニ決済の受注情報を作成
        $this->createOrderPaymentDataCvs($this->Order);

        /*
         * コンビニ決済 決済処理（CvsClientService）モック作成
         */
        // CvsClientServiceをモック化
        $this->app['yamato_payment.service.client.cvs'] = $this->createCvsClientService(true);

        // フォームデータの作成
        $formData = array(
            '_token' => 'dummy',
            // コンビニ　セブンイレブン
            'cvs' => 21
        );

        /*
         * コンビニ決済実行
         */
        $crawler = $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment')
            , array('regist_cvs' => $formData)
        );

        // 正しく画面が表示されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エラーメッセージが返ること
        $this->assertContains('注文情報が無効です。この手続きは無効となりました。', $crawler->filter('div > p')->text());
    }

    function testDeferredProcess_クロネコ代金後払い決済_決済完了画面にリダイレクトする_注文完了メールが送信される_送信メール履歴が保存される()
    {
        // 支払方法IDを取得（クロネコ代金後払い決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_DEFERRED']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->Order->setPaymentTotal($Payment->getRuleMax() - 1);
        $this->app['orm.em']->flush();

        // クロネコ代金後払い決済の受注情報を作成
        $this->createOrderPaymentDataDeferred($this->Order);

        /*
         * クロネコ代金後払い決済 決済処理（DeferredClientService）モック作成
         */
        // DeferredClientServiceをモック化
        $this->app['yamato_payment.service.client.deferred'] = $this->createDeferredClientService(true);

        // フォームデータの作成
        $formData = array(
            '_token' => 'dummy',
        );

        /*
         * クロネコ代金後払い決済実行
         */
        $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment')
            , array(
                'form' => $formData,
                'mode' => '',
            )
        );

        // リダイレクトされること
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('shopping_complete')));

        // 送信したメールを取得
        $Messages = $this->getMailCatcherMessages();
        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        // 注文完了メールが送信されること
        $MailTemplate = $this->app['eccube.repository.mail_template']->find(1);
        $this->assertContains($MailTemplate->getSubject(), $Message->subject);

        // 送信メール履歴が保存されること
        $this->assertNotNull($this->app['eccube.repository.mail_history']->findOneBy(array('Order' => $this->Order)));
    }

    function testDeferredProcess_クロネコ代金後払い決済_決済処理の事前チェック処理の返り値がtrueの場合_決済完了画面にリダイレクトする()
    {
        // 支払方法IDを取得（クロネコ代金後払い決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_DEFERRED']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);

        // 受注状況を作成
        $orderStatus = $this->app['eccube.repository.order_status']->find($this->app['config']['order_new']);
        $this->Order->setOrderStatus($orderStatus);

        $this->app['orm.em']->flush();

        // クロネコ代金後払い決済の受注情報を作成
        $this->createOrderPaymentDataDeferred($this->Order);

        /*
         * クロネコ代金後払い決済 決済処理（DeferredClientService）モック作成
         */
        // DeferredClientServiceをモック化
        $this->app['yamato_payment.service.client.deferred'] = $this->createDeferredClientService(true);

        // フォームデータの作成
        $formData = array(
            '_token' => 'dummy',
        );

        /*
         * クロネコ代金後払い決済実行
         */
        $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment')
            , array(
                'form' => $formData,
                'mode' => '',
            )
        );

        // リダイレクトすること
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('shopping_complete')));
    }

    function testDeferredProcess_クロネコ代金後払い決済_決済処理の事前チェック処理の返り値が配列の場合_エラー画面に遷移する()
    {
        // 支払方法IDを取得（クロネコ代金後払い決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_DEFERRED']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);

        // 受注状況を作成
        $orderStatus = $this->app['eccube.repository.order_status']->find($this->app['config']['order_cancel']);
        $this->Order->setOrderStatus($orderStatus);

        $this->app['orm.em']->flush();

        // クロネコ代金後払い決済の受注情報を作成
        $this->createOrderPaymentDataDeferred($this->Order);

        // フォームデータの作成
        $formData = array(
            '_token' => 'dummy',
        );

        /*
         * クロネコ代金後払い決済 決済処理（DeferredClientService）モック作成
         */
        // DeferredClientServiceをモック化
        $this->app['yamato_payment.service.client.deferred'] = $this->createDeferredClientService(true);

        /*
         * クロネコ代金後払い決済実行
         */
        $crawler = $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment')
            , array(
                'form' => $formData,
                'mode' => '',
            )
        );

        // 正しく画面が表示されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エラーメッセージが返ること
        $this->assertContains('注文情報が無効です。この手続きは無効となりました。', $crawler->filter('div > p')->text());
    }

    function testDeferredProcess_クロネコ代金後払い決済_決済処理でエラーが発生した場合_エラーメッセージが返る()
    {
        // 支払方法IDを取得（クロネコ代金後払い決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_DEFERRED']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->Order->setPaymentTotal($Payment->getRuleMax() - 1);
        $this->app['orm.em']->flush();

        // クロネコ代金後払い決済の受注情報を作成
        $this->createOrderPaymentDataDeferred($this->Order);

        /*
         * クロネコ代金後払い決済 決済処理（DeferredClientService）モック作成
         */
        // DeferredClientServiceをモック化
        $this->app['yamato_payment.service.client.deferred'] = $this->createDeferredClientService(false);

        // フォームデータの作成
        $formData = array(
            '_token' => 'dummy',
        );

        /*
         * クロネコ代金後払い決済実行
         */
        $crawler = $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment')
            , array(
                'form' => $formData,
                'mode' => '',
            )
        );

        // 正しく画面が表示されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エラーメッセージが返ること
        $this->assertContains('決済でエラーが発生しました。', $crawler->filter('.col-md-10 .message .errormsg')->text());
    }

    function testDeferredProcess_クロネコ代金後払い決済_入力内容チェックでエラーが発生した場合_エラーメッセージが返る()
    {
        // 支払方法IDを取得（クロネコ代金後払い決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_DEFERRED']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->Order->setPaymentTotal($Payment->getRuleMax() - 1);
        $this->app['orm.em']->flush();

        // クロネコ代金後払い決済の受注情報を作成
        $this->createOrderPaymentDataDeferred($this->Order);

        // フォームデータの作成
        $formData = array(
            '_token' => 'dummy',
        );

        /*
         * DeferredPageHelperをモック化
         */
        // DeferredPageHelperをモック化
        $this->app['yamato_payment.helper.deferred_page'] = $this->createDeferredPageHelper($formData, $this->Order);

        /*
         * クロネコ代金後払い決済実行
         */
        $crawler = $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment')
            , array(
                'form' => $formData,
                'mode' => '',
            )
        );

        // 正しく画面が表示されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エラーメッセージが返ること
        $this->assertContains('エラーメッセージ', $crawler->filter('.col-md-10 .message .errormsg')->text());
    }

    function testDeferredProcess_クロネコ代金後払い決済_フォームデータに不正があった場合_エラーメッセージが返る()
    {
        // 支払方法IDを取得（クロネコ代金後払い決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_DEFERRED']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->Order->setPaymentTotal($Payment->getRuleMax() - 1);
        $this->app['orm.em']->flush();

        // クロネコ代金後払い決済の受注情報を作成
        $this->createOrderPaymentDataDeferred($this->Order);

        // フォームデータの作成
        $formData = array();

        /*
         * クロネコ代金後払い決済実行
         */
        $crawler = $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment')
            , array(
                'form' => $formData,
                'mode' => '',
            )
        );

        // 正しく画面が表示されること
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エラーメッセージが返ること
        $this->assertContains(
            '後払い決済の与信を行いましたが、大変申し訳ございません、今回のご注文分に関してはお取引できません。'
            , $crawler->filter('#form1')->text()
        );
    }

    function testGoBack_確認画面に返ること()
    {
        // 支払方法IDを取得（クロネコ代金後払い決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_DEFERRED']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        $this->client->request(
            'GET',
            $this->app->url('yamato_shopping_payment_back')
        );

        // リダイレクトされること
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('shopping')));

        // 受注状況が購入処理中になっていること
        $actual = $this->Order->getOrderStatus()->getId();
        $this->assertEquals($this->app['config']['order_processing'], $actual);
    }

    function testUpdatePrder()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'updateOrder');
        $method->setAccessible(true);

        // ログイン状態にする
        $Customer = $this->createCustomer();
        $token = new UsernamePasswordToken($Customer, null, 'customer', array('ROLE_USER'));
        $this->app['security.token_storage']->setToken($token);

        // 購入金額合計を取得する
        /** @var Customer $user */
        $user = $this->app->user();
        $expected = $user->getBuyTotal();
        /*
         * 会員の場合、購入金額を更新する
         */
        $method->invoke($this->object, $this->Order, $this->app);
        $actual = $user->getBuyTotal();
        $this->assertNotEquals($expected, $actual);
    }

    function testPrepareOrderData_決済処理の事前チェック処理_NULLが返る()
    {
        // 支払方法IDを取得（クロネコ代金後払い決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_DEFERRED']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // 支払方法の設定情報を取得
        /** @var PaymentExtension $PaymentExtension */
        $PaymentExtension = $this->app['yamato_payment.util.payment']->getPaymentTypeConfig($this->Order->getPayment()->getId());
        $paymentCode = $PaymentExtension->getPaymentCode();

        // テスト対象メソッド実行
        $actual = $this->object->prepareOrderData($this->Order, $this->app, $paymentCode);
        $this->assertNull($actual);
    }

    function testPrepareOrderData_受注状況が新規受付_入金済みの場合_購入完了画面のリダイレクトレスポンスが返る()
    {
        // 受注状況を作成
        $orderStatus = $this->app['eccube.repository.order_status']->find($this->app['config']['order_new']);
        $this->Order->setOrderStatus($orderStatus);
        $this->app['orm.em']->flush();
        /** @var RedirectResponse $actual */
        $actual = $this->object->prepareOrderData($this->Order, $this->app, null);

        // 購入完了画面のリダイレクトレスポンスが返ること
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $actual);
        $this->assertEquals($this->app->url('shopping_complete'), $actual->getTargetUrl());
    }

    function testPrepareOrderData_受注状況が入金待ち_modeがpgreturn以外の場合_購入完了画面のリダイレクトレスポンスが返る()
    {
        // 受注状況を作成
        $orderStatus = $this->app['eccube.repository.order_status']->find($this->app['config']['order_pay_wait']);
        $this->Order->setOrderStatus($orderStatus);
        $this->app['orm.em']->flush();

        /** @var RedirectResponse $actual */
        $actual = $this->object->prepareOrderData($this->Order, $this->app, null);

        // 購入完了画面のリダイレクトレスポンスが返ること
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $actual);
        $this->assertEquals($this->app->url('shopping_complete'), $actual->getTargetUrl());
    }

    function testPrepareOrderData_受注状況が新規受付_入金済み_入金待ち以外_modeがpgreturn以外_決済状況がnullでない場合_エラーページのレスポンスが返る()
    {
        // 受注状況を作成
        $orderStatus = $this->app['eccube.repository.order_status']->find($this->app['config']['order_cancel']);
        $this->Order->setOrderStatus($orderStatus);
        $this->app['orm.em']->flush();

        $actual = $this->object->prepareOrderData($this->Order, $this->app, null);

        // エラーページのレスポンスが返ること
        $this->assertContains('注文情報が無効です。この手続きは無効となりました。' ,$actual);
    }

    function testPrepareOrderData_paymentCodeが空の場合_エラーページのレスポンスが返る()
    {
        $actual = $this->object->prepareOrderData($this->Order, $this->app, '');

        // エラーページのレスポンスが返ること
        $this->assertContains('注文情報の決済方法と決済モジュールの設定が一致していません。この手続きは無効となりました。' ,$actual);
    }

    function testDoGetCard_お預かりカード情報が返る()
    {
        // リクエストの値作成
        $request_all = array();
        // 会員ID作成
        $customer_id = $this->createCustomer()->getId();

        /*
         * クレジットカードお預かり情報照会（MemberClientService）モック作成
         */
        // 預かりカード三件
        $results = $this->createCardData();
        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(true, $results);

        $actual = $this->object->doGetCard($request_all, $customer_id, $this->app);

        // カード情報が返ること
        list($registcard_lists, $default_card_key) = $actual;
        foreach ($registcard_lists as $registcard_list) {
            $this->assertContains('カード番号', $registcard_list['data']);
        }

        // カード最終利用日が一番新しいカードのcardKey（2）が返ること
        $this->assertEquals(2, $default_card_key);
    }

    function testDoGetCard_お預かりカードでエラーの場合_nullが返る_エラーメッセージが返る()
    {
        // リクエストの値作成
        $request_all = array();
        // 会員ID作成
        $customer_id = $this->createCustomer()->getId();

        // クレジットカードお預かり情報照会（MemberClientService）モック作成
        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(false);

        $actual = $this->object->doGetCard($request_all, $customer_id, $this->app);

        // null が返る
        $this->assertEquals(array(null, null), $actual);

        // エラーメッセージが返る
        $this->assertContains('※ お預かり照会でエラーが発生しました。', $this->object->error['payment']);
    }

    protected function createCardData()
    {
        $results['cardData'] = array(
            0 => array(
                'cardKey' => '1',
                'maskingCardNo' => '************1111',
                'cardExp' => '0528',
                'cardOwner' => 'KURONEKO YAMATO ONE',
                'subscriptionFlg' => '1',
                'lastCreditDate' => '20160604',
            ),
            1 => array(
                'cardKey' => '2',
                'maskingCardNo' => '************2222',
                'cardExp' => '0828',
                'cardOwner' => 'KURONEKO YAMATO TWO',
                'subscriptionFlg' => '1',
                'lastCreditDate' => '20160606',
            ),
            2 => array(
                'cardKey' => '3',
                'maskingCardNo' => '************3333',
                'cardExp' => '1028',
                'cardOwner' => 'KURONEKO YAMATO THREE',
                'subscriptionFlg' => '1',
                'lastCreditDate' => '20160603',
            )
        );
        $results['cardUnit'] = 3;

        return $results;
    }

    private function createMemberClientService($doGetCard = false, $results = null, $bool = false)
    {
        $mock = $this->getMock('MemberClientService', array('doGetCard', 'getResults', 'getError', 'doRegistCard', 'doDeleteCard'));
        $mock->expects($this->any())
            ->method('doGetCard')
            ->will($this->returnValue($doGetCard));
        $mock->expects($this->any())
            ->method('getResults')
            ->will($this->returnValue($results));
        $mock->expects($this->any())
            ->method('getError')
            ->will($this->returnValue(array('エラーメッセージ')));
        $mock->expects($this->any())
            ->method('doRegistCard')
            ->will($this->returnValue($bool));
        $mock->expects($this->any())
            ->method('doDeleteCard')
            ->will($this->returnValue($bool));

        return $mock;
    }

    private function createPaymentUtil($paymentTypeConfig = null, $isReservedOrder = false, $isOption = null, $OrderExtension = null,
                                       $isReserve = false, $getMaxScheduledShippingDate = null, $checkCartProductType = false)
    {
        $sendPayMethod = $this->app['yamato_payment.util.payment']->getCreditPayMethod();

        $mock = $this->getMock('PaymentUtil', array('getPaymentTypeConfig', 'isReservedOrder', 'isOption', 'getCreditPayMethod',
            'getOrderPayData', 'isReserve', 'getMaxScheduledShippingDate', 'doDeleteCard', 'getConveni', 'checkCartProductType'));
        $mock->expects($this->any())
            ->method('getPaymentTypeConfig')
            ->will($this->returnValue($paymentTypeConfig));
        $mock->expects($this->any())
            ->method('isReservedOrder')
            ->will($this->returnValue($isReservedOrder));
        $mock->expects($this->any())
            ->method('isOption')
            ->will($this->returnValue($isOption));
        $mock->expects($this->any())
            ->method('getCreditPayMethod')
            ->will($this->returnValue($sendPayMethod));
        $mock->expects($this->any())
            ->method('getOrderPayData')
            ->will($this->returnValue($OrderExtension));
        $mock->expects($this->any())
            ->method('isReserve')
            ->will($this->returnValue($isReserve));
        $mock->expects($this->any())
            ->method('getMaxScheduledShippingDate')
            ->will($this->returnValue($getMaxScheduledShippingDate));
        $mock->expects($this->any())
            ->method('doDeleteCard')
            ->will($this->returnValue(null));
        $mock->expects($this->any())
            ->method('checkCartProductType')
            ->will($this->returnValue($checkCartProductType));

        return $mock;
    }

    private function createCreditClientService($doPaymentRequest = false, $doPaymentRequest2 = false, $getResults = array(), $doSecureTran = null)
    {
        $mock = $this->getMock('CreditClientService', array('doPaymentRequest', 'getResults', 'getError', 'doSecureTran'));
        $mock->expects($this->any())
            ->method('doPaymentRequest')
            ->will($this->onConsecutiveCalls($doPaymentRequest, $doPaymentRequest2));
        $mock->expects($this->any())
            ->method('getResults')
            ->will($this->returnValue($getResults));
        $mock->expects($this->any())
            ->method('getError')
            ->will($this->returnValue(array('エラーメッセージ')));
        $mock->expects($this->any())
            ->method('doSecureTran')
            ->will($this->returnValue($doSecureTran));

        return $mock;
    }

    private function createCvsClientService($doPaymentRequest = false)
    {
        $mock = $this->getMock('CvsClientService', array('doPaymentRequest', 'getError'));
        $mock->expects($this->any())
            ->method('doPaymentRequest')
            ->will($this->returnValue($doPaymentRequest));
        $mock->expects($this->any())
            ->method('getError')
            ->will($this->returnValue(array('エラーメッセージ')));

        return $mock;
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

    private function createDeferredPageHelper($listParam, $Order)
    {
        $mock = $this->getMock(
            'Plugin\YamatoPayment\Helper\DeferredPageHelper',
            array('checkError'),
            array($this->app, $listParam, $Order)
        );
        $mock->expects($this->any())
            ->method('checkError')
            ->will($this->returnValue(array('エラーメッセージ')));

        return $mock;
    }

    protected function scenarioCartIn($pre_order_id)
    {
        // カートイン
        $this->client->request(
            'POST',
            $this->app->path('cart_add'),
            array('product_class_id' => 1)
        );

        // カートをロック
        $this->app['eccube.service.cart']->lock();
        $this->app['eccube.service.cart']->setPreOrderId($pre_order_id);

        // 正常なページ遷移を記録
        $this->app['yamato_payment.util.plugin']->savePagePath($this->app->url('shopping'));
        $this->app['yamato_payment.util.plugin']->setRegistSuccess();
        $this->app['session']->save();
    }

    protected function scenarioReserveItemCartIn($product_type_id, $product_id)
    {
        /** @var Product $Product */
        $Product = $this->app['eccube.repository.product']->find($product_id);
        $product_class_id = $Product->getProductClasses()->get(0)->getId();

        /** @var ProductType $ProductType */
        $ProductType = $this->app['eccube.repository.master.product_type']->find($product_type_id);

        // 商品種別を更新
        foreach ($Product->getProductClasses() as $productClass) {
            /** @var ProductClass $productClass */
            $productClass->setProductType($ProductType);

            $this->app['orm.em']->persist($productClass);
        }
        $this->app['orm.em']->flush();

        // カートイン
        $this->client->request(
            'POST',
            $this->app->path('cart_add'),
            array('product_class_id' => $product_class_id)
        );

        // カートロック
        $this->app['eccube.service.cart']->lock();

        // 正常なページ遷移を記録
        $this->app['yamato_payment.util.plugin']->savePagePath($this->app->url('shopping'));
        $this->app['yamato_payment.util.plugin']->setRegistSuccess();
        $this->app['session']->save();
    }

}
