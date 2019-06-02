<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Eccube\Application;
use Eccube\Entity\Order;
use Eccube\Event\EventArgs;
use Plugin\YamatoPayment\Entity\YamatoOrderPayment;

class MailEvent extends AbstractEvent
{
    /**
     * 受注メール送信：Renderイベント
     *
     * @param EventArgs $event
     */
    public function onMailAdminOrderRender(EventArgs $event)
    {
        // パラメータを取得
        $formData = $event->getArgument('formData');
        $Order = $event->getArgument('Order');
        $message = $event->getArgument('message');
        $body = $message->getBody();

        // メール本文差し込み処理
        if (!isset($formData['template'])) {
            $formData['template'] = 0;
        }
        $body = $this->insertOrderMailBody($body, $Order, $formData['template']);
        $message->setBody($body);

        // 請求書再発行通知メール 送信先
        if (isset($formData['mail_address'])) {
            $message->setBcc($formData['mail_address']);
        }
    }

    /**
     * 注文確認メール送信：Renderイベント
     *
     * @param EventArgs $event
     */
    public function onMailOrderRender(EventArgs $event)
    {
        // 送信メール情報を取得
        $Order = $event->getArgument('Order');
        $message = $event->getArgument('message');
        $body = $message->getBody();

        // メール本文差し込み処理
        $body = $this->insertOrderMailBody($body, $Order);

        $message->setBody($body);
    }

    /**
     * メール本文差し込み処理
     *
     * @param string $mailBody
     * @param Order $Order
     * @param integer $templateId
     * @return string
     */
    public function insertOrderMailBody($mailBody, $Order, $templateId = 1)
    {
        $const = $this->app['config']['YamatoPayment']['const'];

        // 決済情報を取得
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        if (is_null($YamatoOrderPayment) == false) {
            // メール本文へ決済情報差し込み
            $mailBody = $this->insertPaymentDataToOrderMail($mailBody, $YamatoOrderPayment);
        }

        // 発送完了メールの場合
        if ($templateId == $const['DELIV_COMPLETE_MAIL_ID']) {
            // 発送完了メールへ荷物お問い合わせ情報差し込み
            $mailBody = $this->insertDelivSlipToDelivCompleteMail($mailBody, $Order, $YamatoOrderPayment);
        }

        return $mailBody;
    }

    /**
     * メール本文へ決済情報差し込み
     *
     * @param string $body メール本文
     * @param YamatoOrderPayment $YamatoOrderPayment 決済情報
     * @return string メール本文
     */
    protected function insertPaymentDataToOrderMail($body, $YamatoOrderPayment)
    {
        // メール通知用決済情報取得
        $paymentData = $YamatoOrderPayment->getMemo02();
        if (!empty($paymentData)) {
            // 差し込み用テンプレート取得
            $twig = $this->app['twig']->render(
                'YamatoPayment/Resource/template/admin/mail/order_mail.twig', array(
                'paymentData' => $paymentData,
            ));
            $search = <<< EOF
************************************************
　ご注文商品明細
EOF;
            // テンプレート差し込み
            $replace = $twig . $search;
            $body = str_replace($search, $replace, $body);
        }

        return $body;
    }

    /**
     * 発送完了メールへ荷物お問い合わせ情報差し込み
     *
     * @param string $body メール本文
     * @param Order $Order 受注情報
     * @param YamatoOrderPayment $YamatoOrderPayment 決済情報
     * @return string メール本文
     */
    protected function insertDelivSlipToDelivCompleteMail($body, $Order, $YamatoOrderPayment)
    {
        $const = $this->app['config']['YamatoPayment']['const'];
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();

        // 配送伝票番号情報取得
        $ShippingDelivSlips = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']
            ->getDelivSlipByShippings($Order->getShippings());

        $twig = '';

        // 差し込み用テンプレート取得
        $twig = $this->app['twig']->render(
                'YamatoPayment/Resource/template/admin/mail/deliv_complete_mail.twig', array(
                        'ShippingDelivSlips' => $ShippingDelivSlips,
                        'const' => $const,
                        'userSettings' => $userSettings,
                )
        );


        // 差し込み対象テンプレートがセットされている場合
        if (!empty($twig)) {
            $search = <<< EOF
************************************************
　ご請求金額
EOF;
            // テンプレート差し込み
            $replace = $twig . $search;
            $body = str_replace($search, $replace, $body);

        }

        return $body;
    }

}
