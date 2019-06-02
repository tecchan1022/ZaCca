<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Eccube\Application;
use Eccube\Entity\MailHistory;
use Eccube\Entity\Order;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;

class AdminOrderMailEvent extends AbstractEvent
{
    /**
     * 受注メール送信画面：IndexCompleteイベント
     *
     * @param EventArgs $event
     */
    public function onAdminOrderMailIndexComplete(EventArgs $event)
    {
        // パラメータ取得
        /** @var Form $form */
        $form = $event->getArgument('form');
        /** @var Order $Order */
        $Order = $event->getArgument('Order');
        /** @var MailHistory $MailHistory */
        $MailHistory = $event->getArgument('MailHistory');
        $mailBody = $MailHistory->getMailBody();

        // メールテンプレートID
        $templateId = $form->get('mail_template_id')->getData();

        // メール本文差し込み処理
        $mailBody = $this->app['yamato_payment.event.mail']->insertOrderMailBody(
            $mailBody,
            $Order,
            $templateId
        );

        // メール送信履歴更新
        $MailHistory->setMailBody($mailBody);

        $this->app['orm.em']->persist($MailHistory);
        $this->app['orm.em']->flush($MailHistory);
    }

    /**
     * 受注メール送信画面：ConfirmRenderイベント
     *
     * @param TemplateEvent $event
     */
    public function onAdminOrderMailConfirmRender(TemplateEvent $event)
    {
        // パラメータを取得
        $parameters = $event->getParameters();
        /** @var FormView $form */
        $form = $parameters['form'];
        /** @var Order $Order */
        $Order = $parameters['Order'];
        $body = $parameters['body'];

        // メールテンプレートID
        $templateId = $form->children['template']->vars['value'];

        // メール本文差し込み処理
        $body = $this->app['yamato_payment.event.mail']->insertOrderMailBody(
            $body,
            $Order,
            (int)$templateId
        );

        $parameters['body'] = $body;
        $event->setParameters($parameters);
    }

    /**
     * 受注メール送信画面：MailAllCompleteイベント
     *
     * @param EventArgs $event
     */
    public function onAdminOrderMailMailAllComplete(EventArgs $event)
    {
        // パラメータ取得
        $request = $event->getRequest();
        /** @var Form $form */
        $form = $event->getArgument('form');

        // メールテンプレートID
        $templateId = $form->get('mail_template_id')->getData();

        $ids = $request->get('ids');
        $tmp = explode(',', $ids);

        foreach ($tmp as $orderId) {

            // 受注データ取得
            /** @var Order $Order */
            $Order = $this->app['eccube.repository.order']->find($orderId);

            // メール送信履歴から最新の1件を取得
            /** @var MailHistory $MailHistory */
            $MailHistory = $this->app['eccube.repository.mail_history']->findOneBy(
                array('Order' => $orderId),
                array('id' => 'DESC')
            );
            $mailBody = $MailHistory->getMailBody();

            // メール本文差し込み処理
            $mailBody = $this->app['yamato_payment.event.mail']->insertOrderMailBody(
                $mailBody,
                $Order,
                $templateId
            );

            // メール送信履歴更新
            $MailHistory->setMailBody($mailBody);
        }

        $this->app['orm.em']->flush();
    }

    /**
     * 受注メール送信画面：MailAllConfirmRenderイベント
     *
     * @param TemplateEvent $event
     */
    public function onAdminOrderMailAllConfirmRender(TemplateEvent $event)
    {
        // パラメータを取得
        $parameters = $event->getParameters();
        /** @var FormView $form */
        $form = $parameters['form'];
        $body = $parameters['body'];
        $ids = $parameters['ids'];

        // メールテンプレートID
        $templateId = $form->children['template']->vars['value'];

        // 受注データ取得
        $tmp = explode(',', $ids);
        /** @var Order $Order */
        $Order = $this->app['eccube.repository.order']->find($tmp[0]);

        // メール本文差し込み処理
        $body = $this->app['yamato_payment.event.mail']->insertOrderMailBody(
            $body,
            $Order,
            (int)$templateId
        );

        // パラメータ再設定
        $parameters['body'] = $body;
        $event->setParameters($parameters);
    }
}
