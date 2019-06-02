<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\ServiceProvider;

use Eccube\Application;
use Eccube\Common\Constant;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

class PaymentServiceProvider implements ServiceProviderInterface
{
    public function register(BaseApplication $app)
    {
        /*** 管理画面のルート追加 ***/
        $admin = $app['controllers_factory'];

        // 強制SSL
        if ($app['config']['force_ssl'] == Constant::ENABLED) {
            $admin->requireHttps();
        }

        $admin->match('/plugin/yamato_payment/config', '\\Plugin\\YamatoPayment\\Controller\\Admin\\PluginConfigController::edit')->bind('plugin_YamatoPayment_config');
        $admin->match('/plugin/yamato_payment/b2_config', '\\Plugin\\YamatoPayment\\Controller\\Admin\\PluginB2ConfigController::edit')->bind('plugin_YamatoPayment_b2_config');
        $admin->match('/plugin/yamato_payment/report', '\\Plugin\\YamatoPayment\\Controller\\Admin\\PluginReportController::edit')->bind('plugin_YamatoPayment_report');
        $admin->match('/plugin/yamato_payment/report/export', '\\Plugin\\YamatoPayment\\Controller\\Admin\\PluginReportController::exportCSV')->bind('plugin_YamatoPayment_report_export');
        $admin->match('/order/yamato_order_b2_csv_upload', '\\Plugin\\YamatoPayment\\Controller\\Admin\\OrderB2Controller::csvB2')->bind('yamato_order_b2_csv_upload');
        $admin->match('/order/yamato_order_status/page/{page_no}', '\\Plugin\\YamatoPayment\\Controller\\Admin\\OrderStatusController::index')
            ->assert('page_no', '\d+')->bind('yamato_order_status_page');
        $admin->match('/order/yamato_order_status', '\\Plugin\\YamatoPayment\\Controller\\Admin\\OrderStatusController::index')
            ->bind('yamato_order_status');
        $admin->match('/order/yamato_order_status/delete', '\\Plugin\\YamatoPayment\\Controller\\Admin\\OrderStatusController::delete')
            ->bind('yamato_order_status_delete');
        $admin->match('/order/yamato_order_status/change_status', '\\Plugin\\YamatoPayment\\Controller\\Admin\\OrderStatusController::changeStatus')
            ->bind('yamato_order_status_change_status');
        $admin->match('/order/yamato_order_status/change_payment_status', '\\Plugin\\YamatoPayment\\Controller\\Admin\\OrderStatusController::changePaymentStatus')
            ->bind('yamato_order_status_change_payment_status');
        $admin->match('/order/yamato_order_b2_csv_template/{type}', '\\Plugin\\YamatoPayment\\Controller\\Admin\\OrderB2Controller::csvTemplate')->bind('yamato_order_b2_csv_template');
        $admin->match('/order/export/{id}/buyer', '\\Plugin\\YamatoPayment\\Controller\\Admin\\OrderEditControllerExtension::exportBuyer')->assert('id', '\d+')->bind('admin_order_export_buyer');
        $admin->match('/order/export/web_collect', '\\Plugin\\YamatoPayment\\Controller\\Admin\\OrderControllerExtension::exportWebCollect')->bind('admin_order_export_web_collect');
        $admin->match('/order/export/b2', '\\Plugin\\YamatoPayment\\Controller\\Admin\\OrderControllerExtension::exportB2')->bind('admin_order_export_b2');
        $admin->match('/product/yamato_reserve_product_csv_upload', '\\Plugin\\YamatoPayment\\Controller\\Admin\\ProductCsvImportController::csvReserveProduct')->bind('yamato_reserve_product_csv_upload');
        $admin->match('/product/yamato_reserve_product_csv_template', '\\Plugin\\YamatoPayment\\Controller\\Admin\\ProductCsvImportController::csvTemplate')->bind('yamato_reserve_product_csv_template');
        
        // 商品CSV出力処理を上書き
        $admin->match('/product/export', '\\Plugin\\YamatoPayment\\Controller\\Admin\\ProductControllerExtension::export')->bind('admin_product_export');
        // 受注CSV出力処理を上書き
        $admin->match('/order/export', '\\Plugin\\YamatoPayment\\Controller\\Admin\\OrderControllerExtension::exportOrder')->bind('admin_order_export_order');

        // mount controllers
        $app->mount('/'.trim($app['config']['admin_route'], '/').'/', $admin);

        /*** フロントのルート追加 ***/
        $front = $app['controllers_factory'];

        // 強制SSL
        if ($app['config']['force_ssl'] == Constant::ENABLED) {
            $front->requireHttps();
        }

        $front->match('/shopping/yamato_payment', '\\Plugin\\YamatoPayment\\Controller\\PaymentController::index')->bind('yamato_shopping_payment');
        $front->match('/shopping/yamato_payment/back', '\\Plugin\\YamatoPayment\\Controller\\PaymentController::goBack')->bind('yamato_shopping_payment_back');
        $front->match('/shopping/yamato_payment_recv', '\\Plugin\\YamatoPayment\\Controller\\PaymentRecvController::index')->bind('yamato_shopping_payment_recv');
        $front->match('/mypage/change_card', '\\Plugin\\YamatoPayment\\Controller\\Mypage\\MypageCardEditController::index')->value('mypageno', 'card')->bind('yamato_mypage_change_card');
        $front->post('/mypage/change_card/del', '\\Plugin\\YamatoPayment\\Controller\\Mypage\\MypageCardEditController::delRegisCard')->bind('yamato_delete_card');

        // mount controllers
        $app->mount('/', $front);


        // リポジトリの追加
        $app['yamato_payment.repository.yamato_plugin'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\YamatoPayment\Entity\YamatoPlugin');
        });
        $app['yamato_payment.repository.yamato_payment_method'] = $app->share(function () use ($app) {
            $yamatoPaymentMethodRepo = $app['orm.em']->getRepository('\Plugin\YamatoPayment\Entity\YamatoPaymentMethod');
            $yamatoPaymentMethodRepo->setApplication($app);
            return $yamatoPaymentMethodRepo;
        });
        $app['yamato_payment.repository.yamato_order_payment'] = $app->share(function () use ($app) {
            $yamatoOrderPaymentRepo = $app['orm.em']->getRepository('\Plugin\YamatoPayment\Entity\YamatoOrderPayment');
            $yamatoOrderPaymentRepo->setApplication($app);

            return $yamatoOrderPaymentRepo;

        });
        $app['yamato_payment.repository.yamato_product'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('\Plugin\YamatoPayment\Entity\YamatoProduct');
        });
        $app['yamato_payment.repository.yamato_shipping_deliv_slip'] = $app->share(function () use ($app) {
            $YamatoShippingDelivSlipRepo = $app['orm.em']->getRepository('\Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip');
            $YamatoShippingDelivSlipRepo->setApplication($app);

            return $YamatoShippingDelivSlipRepo;
        });
        $app['yamato_payment.repository.yamato_order_scheduled_shipping_date'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('\Plugin\YamatoPayment\Entity\YamatoOrderScheduledShippingDate');
        });

        // サービスの追加
        $app['yamato_payment.service.client.credit'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Service\Client\CreditClientService($app);
        });
        $app['yamato_payment.service.client.cvs'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Service\Client\CvsClientService($app);
        });
        $app['yamato_payment.service.client.deferred'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Service\Client\DeferredClientService($app);
        });
        $app['yamato_payment.service.client.deferred_util'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Service\Client\DeferredUtilClientService($app);
        });
        $app['yamato_payment.service.client.member'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Service\Client\MemberClientService($app);
        });
        $app['yamato_payment.service.client.util'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Service\Client\UtilClientService($app);
        });
        $app['yamato_payment.service.csv.export'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Service\CsvExportService($app);
        });

        // Utility の追加
        $app['yamato_payment.util.plugin'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Util\PluginUtil($app);
        });
        $app['yamato_payment.util.payment'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Util\PaymentUtil($app);
        });

        // イベントの追加
        $app['yamato_payment.event.admin.order.edit'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Event\AdminOrderEditEvent($app);
        });
        $app['yamato_payment.event.admin.order.index'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Event\AdminOrderIndexEvent($app);
        });
        $app['yamato_payment.event.admin.order.mail'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Event\AdminOrderMailEvent($app);
        });
        $app['yamato_payment.event.admin.product.edit'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Event\AdminProductEditEvent($app);
        });
        $app['yamato_payment.event.admin.setting.shop.payment.edit'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Event\AdminSettingShopPaymentEditEvent($app);
        });
        $app['yamato_payment.event.mail'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Event\MailEvent($app);
        });
        $app['yamato_payment.event.front'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Event\FrontEvent($app);
        });
        $app['yamato_payment.event.mypage'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Event\MypageEvent($app);
        });
        $app['yamato_payment.event.shopping'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Event\ShoppingEvent($app);
        });
        $app['yamato_payment.event.cart'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Event\CartEvent($app);
        });

        // ヘルパーの追加
        $app['yamato_payment.helper.credit_page'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Helper\CreditPageHelper($app);
        });
        $app['yamato_payment.helper.cvs_page'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Helper\CvsPageHelper($app);
        });
        $app['yamato_payment.helper.deferred_page'] = $app->share(function () use ($app) {
            return new \Plugin\YamatoPayment\Helper\DeferredPageHelper($app);
        });

        // フォームタイプの追加
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new \Plugin\YamatoPayment\Form\Type\Admin\PluginConfigType($app);
            $types[] = new \Plugin\YamatoPayment\Form\Type\Admin\PluginB2ConfigType($app);
            $types[] = new \Plugin\YamatoPayment\Form\Type\Admin\OrderShippingType($app);
            $types[] = new \Plugin\YamatoPayment\Form\Type\Admin\OrderStatusType($app);
            $types[] = new \Plugin\YamatoPayment\Form\Type\Admin\PluginB2PaymentType($app);
            $types[] = new \Plugin\YamatoPayment\Form\Type\Admin\PluginB2DeliveryType($app);
            $types[] = new \Plugin\YamatoPayment\Form\Type\Admin\PluginB2DeliveryTimeType($app);
            $types[] = new \Plugin\YamatoPayment\Form\Type\RegistCreditType($app);
            $types[] = new \Plugin\YamatoPayment\Form\Type\ThreeDTranType($app);
            $types[] = new \Plugin\YamatoPayment\Form\Type\CvsType($app);
            return $types;
        }));

        // フォームエクステンションの追加
        $app['form.type.extensions'] = $app->share($app->extend('form.type.extensions', function ($extensions) use ($app) {
            $extensions[] = new \Plugin\YamatoPayment\Form\Extension\Admin\OrderTypeExtension($app);
            $extensions[] = new \Plugin\YamatoPayment\Form\Extension\Admin\SearchOrderTypeExtension($app);
            $extensions[] = new \Plugin\YamatoPayment\Form\Extension\Admin\MailTypeExtension($app);
            $extensions[] = new \Plugin\YamatoPayment\Form\Extension\Admin\ProductTypeExtension($app);
            $extensions[] = new \Plugin\YamatoPayment\Form\Extension\Admin\PaymentRegisterTypeExtension($app);
            return $extensions;
        }));

        // 管理画面メニューの追加
        $app['config'] = $app->share($app->extend('config', function ($config) {
            $addNavi2['id'] = "yamato_order_status";
            $addNavi2['name'] = "決済状況管理";
            $addNavi2['url'] = "yamato_order_status";

            $addNavi['id'] = "yamato_order_b2_csv_upload";
            $addNavi['name'] = "送り状番号登録";
            $addNavi['url'] = "yamato_order_b2_csv_upload";

            $addNavi3['id'] = "yamato_reserve_product_csv_upload";
            $addNavi3['name'] = "予約商品CSV登録";
            $addNavi3['url'] = "yamato_reserve_product_csv_upload";

            $nav = $config['nav'];
            foreach ($nav as $key => $val) {
                if ("order" == $val["id"]) {
                    $nav[$key]['child'][] = $addNavi2;
                    $nav[$key]['child'][] = $addNavi;
                }
                if ("product" == $val["id"]) {
                    $nav[$key]['child'][] = $addNavi3;
                }
            }

            $config['nav'] = $nav;
            return $config;
        }));

        // メッセージ登録
        $app['yamato_payment.error_message'] = $app->share(function () use ($app) {
            $message_file = __DIR__ . '/../Resource/config/error_message' . '.yml';
            $errorMessage = $app['eccube.service.plugin']->readYml($message_file);

            return $errorMessage;
        });

        // ログ出力
        $app['yamato_payment.log'] = $app->share(function () use ($app) {

            // パス
            $pluginCode = $app['yamato_payment.util.plugin']->getPluginCode();
            $path = $app['config']['root_dir'] . "/app/log/" . $pluginCode . '.log';
            // フォーマット
            $output = "[%datetime%] %message% %context% %extra%\n";
            // 出力フォーマット指定
            $formatter = new LineFormatter($output, null, true);

            // ファイルにログを出力
            $rotatingFileHandler = new RotatingFileHandler($path, $app['config']['log']['max_files'], Logger::DEBUG);
            $rotatingFileHandler->setFormatter($formatter);

            $log = new Logger('YamatoPayment');
            $log->pushHandler($rotatingFileHandler);

            return $log;
        });

        // ECCUBEバージョンの差異吸収
        $app['config'] = $app->share($app->extend('config',function ($config) {
            if (version_compare(Constant::VERSION, '3.0.9', '<=')) {
                $config['plugin_urlpath'] = $config['root_urlpath'] . '/plugin';
            }
            return $config;
        }));
    }

    public function boot(BaseApplication $app)
    {
    }
}
