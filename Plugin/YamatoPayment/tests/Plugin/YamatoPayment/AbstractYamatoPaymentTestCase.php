<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */

namespace Plugin\YamatoPayment;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Eccube\Tests\EccubeTestCase;
use Eccube\Tests\Mock\CsrfTokenMock;
use Eccube\Util\Str;
use Guzzle\Http\Client;
use Plugin\YamatoPayment\Entity\YamatoOrderPayment;
use Plugin\YamatoPayment\Entity\YamatoOrderScheduledShippingDate;
use Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractYamatoPaymentTestCase extends EccubeTestCase
{
    /**
     * @var Application
     */
    protected $app;
    /**
     * @var array
     */
    protected $const;

    protected $object;

    protected $mailcatcher_url;

    public function setUp()
    {
        parent::setUp();
        $this->const = $this->app['config']['YamatoPayment']['const'];

        // 設定値書き換え
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        // 加盟店コード
        $userSettings['shop_id'] = '12345678';
        // 動作モード:テストモード
        $userSettings['exec_mode'] = '0';
        // 有効にする決済方法:クレカ・コンビニ・後払い
        $userSettings['enable_payment_type'] = array(
            $this->const['YAMATO_PAYID_CREDIT'],
            $this->const['YAMATO_PAYID_CVS'],
            $this->const['YAMATO_PAYID_DEFERRED'],
        );
        // メールの追跡情報:利用する
        $userSettings['ycf_deliv_slip'] = '0';
        // オプションサービス:契約済
        $userSettings['use_option'] = '0';
        // 予約商品販売：する
        $userSettings['advance_sale'] = '0';

        $this->app['yamato_payment.util.plugin']->subData['user_settings'] = $userSettings;

        // HTTP_USER_AGENT設定
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 5.1; rv:38.0) Gecko/20100101 Firefox/38.0';
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        $this->cleanUpMailCatcherMessages();
        parent::tearDown();
    }

    /**
     * {@inheritdoc}
     */
    public function createApplication()
    {
        $app = Application::getInstance();
        $app['debug'] = true;

        $config = $this->getMailCatcherConfig();
        $this->mailcatcher_url = $config['mailcatcher']['url'];

        // initializeMailCatcher() では設定が効かないのでここで設定する（2016/6/1 小林）
        $app['config'] = $app->share($app->extend('config', function($eccubeConfig) use ($config) {
            $eccubeConfig['mail']['transport'] = $config['mailcatcher']['transport'];
            $eccubeConfig['mail']['host'] = $config['mailcatcher']['host'];
            $eccubeConfig['mail']['port'] = $config['mailcatcher']['port'];
            $eccubeConfig['mail']['username'] = null;
            $eccubeConfig['mail']['password'] = null;
            $eccubeConfig['mail']['encryption'] = null;
            $eccubeConfig['mail']['auth_mode'] = null;

            return $eccubeConfig;
        }));

        if (version_compare(Constant::VERSION, '3.0.12', '>=')) {
            // ログの内容をERRORレベルでしか出力しないように設定を上書き
            $app['config'] = $app->share($app->extend('config', function ($config, \Silex\Application $app) {
                $config['log']['log_level'] = 'ERROR';
                $config['log']['action_level'] = 'ERROR';
                $config['log']['passthru_level'] = 'ERROR';

                $channel = $config['log']['channel'];
                foreach (array('monolog', 'front', 'admin') as $key) {
                    $channel[$key]['log_level'] = 'ERROR';
                    $channel[$key]['action_level'] = 'ERROR';
                    $channel[$key]['passthru_level'] = 'ERROR';
                }
                $config['log']['channel'] = $channel;

                return $config;
            }));
            $app->initLogger();
        }

        $app->initialize();
        // front.requestイベントが2回動くのでコメントアウト（2016/7/20 小林）
        //$app->initPluginEventDispatcher();
        $app->initializePlugin();
        $app['session.test'] = true;
        $app['exception_handler']->disable();

        $app['form.csrf_provider'] = $app->share(function () {
            return new CsrfTokenMock();
        });

        if (version_compare(Constant::VERSION, '3.0.11', '>=')) {
            $app->register(new \Eccube\Tests\ServiceProvider\FixtureServiceProvider());
        }

        $app->boot();

        return $app;
    }

    /**
     * MailCatcher を初期化する.
     *
     * このメソッドは主に setUp() メソッドでコールされる.
     * MailCatcher が起動してない場合は, テストをスキップする.
     * MailCatcher については \Eccube\Tests\Service\MailServiceTest のコメントを参照してください
     *
     * @see \Eccube\Tests\Service\MailServiceTest
     * @link http://mailcatcher.me/
     */
    protected function initializeMailCatcher()
    {
        $this->checkMailCatcherStatus();
        $config = $this->app['config'];
        // createApplication()の設定が上書きされてしまうのでコメントアウトしておく（2016/7/1 小林）
        //$config['mail']['transport'] = 'smtp';
        //$config['mail']['host'] = '127.0.0.1';
        //$config['mail']['port'] = 1025;
        $config['mail']['username'] = null;
        $config['mail']['password'] = null;
        $config['mail']['encryption'] = null;
        $config['mail']['auth_mode'] = null;
        $this->app['config'] = $config;
        $this->app['swiftmailer.use_spool'] = false;
        $this->app['swiftmailer.options'] = $this->app['config']['mail'];
    }

    /**
     * MailCatcher 用のコンフィグを取得する.
     *
     * @return array
     */
    private function getMailCatcherConfig()
    {
        $ymlPath = __DIR__.'/../../../../../config/eccube';
        $distPath = __DIR__.'/../../config';

        $config = array();
        $config_yml = $ymlPath.'/mailcatcher.yml';
        if (file_exists($config_yml)) {
            $config = Yaml::parse(file_get_contents($config_yml));
        }

        $config_dist = array();
        $config_yml_dist = $distPath.'/mailcatcher.yml.dist';
        if (file_exists($config_yml_dist)) {
            $config_dist = Yaml::parse(file_get_contents($config_yml_dist));
        }

        $configAll = array_replace_recursive($config_dist, $config);
        return $configAll;
    }


    /**
     * MailCatcher の起動状態をチェックする.
     *
     * MailCatcher が起動していない場合は, テストをスキップする.
     */
    protected function checkMailCatcherStatus()
    {
        try {
            $client = new Client();
            //$request = $client->get(self::MAILCATCHER_URL.'messages');
            $request = $client->get($this->mailcatcher_url.'messages');
            $response = $request->send();
            if ($response->getStatusCode() !== 200) {
                throw new HttpException($response->getStatusCode());
            }
        } catch (HttpException $e) {
            $this->markTestSkipped($e->getMailCatcherMessage().'['.$e->getStatusCode().']');
        } catch (\Exception $e) {
            $message = 'MailCatcher is not alivable';
            $this->markTestSkipped($message);
            $this->app->log($message);
        }
    }

    /**
     * MailCatcher のメッセージをすべて削除する.
     */
    protected function cleanUpMailCatcherMessages()
    {
        try {
            $client = new Client();
            //$request = $client->delete(self::MAILCATCHER_URL.'messages');
            $request = $client->delete($this->mailcatcher_url.'messages');
            $request->send();
        } catch (\Exception $e) {
            $this->app->log('['.get_class().'] '.$e->getMessage());
        }
    }

    /**
     * MailCatcher のメッセージをすべて取得する.
     *
     * @return array MailCatcher のメッセージの配列
     */
    protected function getMailCatcherMessages()
    {
        $client = new Client();
        //$request = $client->get(self::MAILCATCHER_URL.'messages');
        $request = $client->get($this->mailcatcher_url.'messages');
        $response = $request->send();
        return json_decode($response->getBody(true));
    }

    /**
     * MailCatcher のメッセージを ID を指定して取得する.
     *
     * @param integer $id メッセージの ID
     * @return object MailCatcher のメッセージ
     */
    protected function getMailCatcherMessage($id)
    {
        $client = new Client();
        //$request = $client->get(self::MAILCATCHER_URL.'messages/'.$id.'.json');
        $request = $client->get($this->mailcatcher_url.'messages/'.$id.'.json');
        $response = $request->send();
        return json_decode($response->getBody(true));
    }

    /**
     * private変数取得
     *
     * @param string $propertyName メソッド名
     * @return \ReflectionProperty
     */
    public function getPrivateProperty($propertyName)
    {
        $reflection = new \ReflectionClass($this->object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property;
    }

    /**
     * @inheritdoc.
     */
    public function createCustomer($email = null)
    {
        $Customer = parent::createCustomer($email);
        $faker = $this->getFaker();
        /** @var \Faker\Generator $faker */
        $phoneNumber = $faker->phoneNumber;
        $phoneNumbers = explode('-', $phoneNumber);

        // パラメータ追加
        $Customer
            ->setKana01($faker->lastKanaName)
            ->setKana02($faker->firstKanaName)
            ->setTel01($phoneNumbers[0])
            ->setTel02($phoneNumbers[1])
            ->setTel03($phoneNumbers[2]);

        $this->app['orm.em']->persist($Customer);
        $this->app['orm.em']->flush();

        return $Customer;
    }

    /**
     * @inheritdoc.
     */
    public function createProduct($product_name = null, $product_class_num = 3)
    {
        $Product = parent::createProduct($product_name, $product_class_num);
        $faker = $this->getFaker();

        // 決済金額が30万を超えないように単価を4ケタまでにする
        $ProductClasses = $Product->getProductClasses();
        foreach ($ProductClasses as $ProductClass) {
            /** @var \Faker\Generator $faker */
            $ProductClass->setPrice02($faker->randomNumber(4));
            $this->app['orm.em']->persist($ProductClass);
        }
        $this->app['orm.em']->flush();

        return $Product;
    }

    /**
     * @return Order
     */
    protected function createOrderData()
    {
        // 受注データ作成
        $Sex = $this->app['eccube.repository.master.sex']->find(1);
        $Payment = $this->app['eccube.repository.payment']->find(1);
        $OrderStatus = $this->app['eccube.repository.order_status']->find($this->app['config']['order_new']);
        $Customer = $this->createCustomer('user@example.com');
        $Customer->setSex($Sex);
        if (version_compare(Constant::VERSION, '3.0.11', '>=')) {
            $Delivery = $this->app['eccube.repository.delivery']->find(1);
            $Product = $this->app['eccube.repository.product']->find(2);
            $ProductClasses = $Product->getProductClasses();
            $Order = $this->app['eccube.fixture.generator']->createOrder($Customer, $ProductClasses->toArray(), $Delivery);
        } else {
            $Order = $this->createOrder($Customer);
        }
        $preOrderId = sha1(Str::random(32));
        $Order->setPreOrderId($preOrderId);
        $Order->setOrderStatus($OrderStatus);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        return $Order;
    }

    /**
     * @param Order $Order
     * @param int $actionStatus （デフォルト：4 与信完了）
     * @return YamatoOrderPayment
     */
    protected function createOrderPaymentDataCredit(Order $Order, $actionStatus = 4)
    {
        // 受注決済データ作成
        $YamatoOrderPayment = new YamatoOrderPayment();
        $YamatoOrderPayment
            ->setId($Order->getId())
            ->setMemo02(array(
                'title' => array( 'name' => 'クレジットカード', 'value' => '1'),
                'OrderId' => array('name' => 'ご注文番号', 'value' => $Order->getId()),
            ))
            ->setMemo03($this->const['YAMATO_PAYID_CREDIT'])                // 支払方法：クレジット支払い
            ->setMemo04($actionStatus)                                      // 決済ステータス
            ->setMemo05(array(
                'function_div' => 'A01',                                    // 機能区分：クレジット決済登録
                'settle_price' => $Order->getPaymentTotal(),                // お支払い額
            ));
        $this->app['orm.em']->persist($YamatoOrderPayment);

        // 受注データ更新
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']));
        $Payment = $this->app['eccube.repository.payment']->find($YamatoPaymentMethod->getId());
        $Order->setPayment($Payment);

        $this->app['orm.em']->flush();

        return $YamatoOrderPayment;
    }

    /**
     * @param Order $Order
     * @param int $actionStatus （デフォルト：4 与信完了）
     * @return YamatoOrderPayment
     */
    protected function createOrderPaymentDataCvs(Order $Order, $actionStatus = 4)
    {
        // 受注決済データ作成
        $YamatoOrderPayment = new YamatoOrderPayment();
        $YamatoOrderPayment
            ->setId($Order->getId())
            ->setMemo02(array(
                'title' => array('name' => 'コンビニ決済', 'value' => '1'),
                'OrderId' => array('name' => 'ご注文番号', 'value' => $Order->getId()),
            ))
            ->setMemo03($this->const['YAMATO_PAYID_CVS'])    // 支払方法：コンビニ支払い
            ->setMemo04($actionStatus)                       // 決済ステータス
            ->setMemo05(array(
                'function_div' => 'B01',                     // 機能区分：セブンイレブン
                'settle_price' => $Order->getPaymentTotal(), // お支払い額
            ));
        $this->app['orm.em']->persist($YamatoOrderPayment);

        // 受注データ更新
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CVS']));
        $Payment = $this->app['eccube.repository.payment']->find($YamatoPaymentMethod->getId());
        $Order->setPayment($Payment);

        $this->app['orm.em']->flush();

        return $YamatoOrderPayment;
    }

    /**
     * @param Order $Order
     * @param int $actionStatus （デフォルト：1 承認済み）
     * @param int $result（デフォルト：0 ご利用可）
     * @return YamatoOrderPayment
     */
    protected function createOrderPaymentDataDeferred(Order $Order, $actionStatus = 1, $result = 0)
    {
        // 受注決済データ作成
        $YamatoOrderPayment = new YamatoOrderPayment();
        $YamatoOrderPayment
            ->setId($Order->getId())
            ->setMemo03($this->const['YAMATO_PAYID_DEFERRED'])     // 支払方法：クロネコ代金後払い
            ->setMemo04($actionStatus)                             // 決済ステータス
            ->setMemo05(array(
                'function_div' => 'KAAAU0010APIAction',            // 機能区分：後払い与信依頼
                'totalAmount' => $Order->getPaymentTotal()
            ))
            ->setMemo06($result);                                   // 審査結果

        $this->app['orm.em']->persist($YamatoOrderPayment);

        // 受注データ更新
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_DEFERRED']));
        $Payment = $this->app['eccube.repository.payment']->find($YamatoPaymentMethod->getId());
        $Order->setPayment($Payment);

        $this->app['orm.em']->flush();

        return $YamatoOrderPayment;
    }

    /**
     * @param Order $Order
     * @return YamatoOrderScheduledShippingDate
     */
    protected function createYamatoOrderScheduledShippingDateData(Order $Order)
    {
        $order_id = $Order->getId();

        // 受注出荷予定日をランダム作成
        date_default_timezone_set('UTC');
        $start = strtotime('2020-01-01 00:00:00'); // 0
        $end = strtotime('2038-01-19 03:14:07'); // 2147483647

        /** @var \datetime $orderScheduledShippingDate */
        $orderScheduledShippingDate = date('Ymd', mt_rand($start, $end));

        // 受注出荷予定日データ作成
        $YamatoOrderScheduledShippingDate = new YamatoOrderScheduledShippingDate();
        $YamatoOrderScheduledShippingDate
            ->setId($order_id)
            ->setScheduledshippingDate($orderScheduledShippingDate);

        $this->app['orm.em']->persist($YamatoOrderScheduledShippingDate);
        $this->app['orm.em']->flush();

        return $YamatoOrderScheduledShippingDate;
    }

    /**
     * @param Order $Order
     * @return array
     */
    protected function createYamatoShippingDelivSlip(Order $Order)
    {
        $ret = array();

        /** @var \Faker\Generator $faker */
        $faker = $this->getFaker();

        $order_id = $Order->getId();
        $Shippings = $Order->getShippings();

        foreach ($Shippings as $Shipping) {
            /** @var Shipping $Shipping */
            // 配送伝票番号データ作成
            $YamatoShippingDelivSlip = new YamatoShippingDelivSlip();
            $YamatoShippingDelivSlip
                ->setId($Shipping->getId())
                ->setOrderId($order_id)
                ->setDelivSlipNumber(Str::random(12))
                ->setLastDelivSlipNumber(Str::random(12))
                ->setDelivSlipUrl($faker->url());
            $this->app['orm.em']->persist($YamatoShippingDelivSlip);
            $this->app['orm.em']->flush($YamatoShippingDelivSlip);
            $ret[] = $YamatoShippingDelivSlip;
        }

        return $ret;
    }

    /**
     * @param Order $Order
     * @return Shipping $Shipping
     */
    protected function createShipping($Order)
    {
        /** @var \Faker\Generator $faker */
        $faker = $this->getFaker();
        $phoneNumber = $faker->phoneNumber;
        $phoneNumbers = explode('-', $phoneNumber);
        $Delivery = $this->app['eccube.repository.delivery']->find(1);

        $Shipping = new Shipping();
        $Shipping->setName01($faker->lastName);
        $Shipping->setName02($faker->firstName);
        $Shipping->setKana01($faker->lastKanaName);
        $Shipping->setKana02($faker->firstKanaName);
        $Shipping->setTel01($phoneNumbers[0]);
        $Shipping->setTel02($phoneNumbers[1]);
        $Shipping->setTel03($phoneNumbers[2]);
        $Shipping->setZip01(889);
        $Shipping->setZip02(0612);
        $Shipping->setPref();
        $Shipping->setAddr01($faker->address);
        $Shipping->setAddr02($faker->address);
        $Shipping->setDelivery($Delivery);
        $Shipping->setOrder($Order);
        $Shipping->setDelFlg(0);

        $this->app['orm.em']->persist($Shipping);
        $this->app['orm.em']->flush();

        return $Shipping;
    }

    /**
     * 複数配送設定をセットする
     *
     * @param int $value 0:無効 1:有効
     * @void
     */
    protected function setMultipleShipping($value)
    {
        /** @var BaseInfo $BaseInfo */
        $BaseInfo = $this->app['eccube.repository.base_info']->get();
        $BaseInfo->setOptionMultipleShipping($value);
        $this->app['orm.em']->flush();
    }

}
