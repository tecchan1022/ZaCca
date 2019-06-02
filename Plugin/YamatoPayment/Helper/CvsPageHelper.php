<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */

namespace Plugin\YamatoPayment\Helper;

use Eccube\Application;
use Eccube\Entity\Order;
use Plugin\YamatoPayment\Entity\PaymentExtension;

/**
 * 決済モジュール 決済画面ヘルパー：コンビニ決済
 */
class CvsPageHelper
{
    /** @var Application */
    protected $app;
    public $isComplete = false;
    public $error;

    /**
     * コンストラクタ
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * 画面モード毎のアクションを行う
     *
     * @param array $listParam フォームデータ
     * @param Order $Order 受注情報
     * @param PaymentExtension $paymentInfo 支払方法の設定情報
     * @param $objPage
     */
    function modeAction($listParam, $Order, $paymentInfo, $objPage)
    {
        $this->isComplete = false;
        $app = $this->app;
        $const = $app['config']['YamatoPayment']['const'];

        $objClient = $app['yamato_payment.service.client.cvs'];

        $result = $objClient->doPaymentRequest($Order, $listParam, $paymentInfo);

        if ($result) {
            //注文状況を「入金待ち」へ
            $order_status = $app['config']['order_pay_wait'];
            $Order->setOrderStatus($app['eccube.repository.order_status']->find($order_status));
            // 決済ステータスを「決済依頼済み」に変更する
            $payment_status = $const['YAMATO_ACTION_STATUS_SEND_REQUEST'];
            $YamatoOrderPayment = $app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());
            $YamatoOrderPayment->setMemo04($payment_status);

            $app['orm.em']->persist($YamatoOrderPayment);
            $app['orm.em']->persist($Order);
            $app['orm.em']->flush();
            $this->isComplete = true;
        } else {
            $error = $objClient->getError();
            $objPage->error['payment'] = '※ 決済でエラーが発生しました。<br />' . implode('<br />', $error);
            //決済ステータスを「決済中断」に変更する
            $payment_status = $const['YAMATO_ACTION_STATUS_NG_TRANSACTION'];
            $YamatoOrderPayment = $app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());
            $YamatoOrderPayment->setMemo04($payment_status);

            $app['orm.em']->persist($YamatoOrderPayment);
            $app['orm.em']->flush();
        }
    }
}
