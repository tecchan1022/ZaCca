<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Eccube\Application;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Form;

class AdminSettingShopPaymentEditEvent extends AbstractEvent
{
    private $min;
    private $max;

    /**
     * コンストラクタ
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->min = $app['config']['YamatoPayment']['const']['CONVENI_ID_SEVENELEVEN'];
        $this->max = $app['config']['YamatoPayment']['const']['CONVENI_ID_CIRCLEK'];
    }

    /**
     * 支払方法設定画面：EditInitializeイベント
     *
     * @param EventArgs $event
     */
    public function onAdminSettingShopPaymentEditInitialize(EventArgs $event)
    {
        $builder = $event->getArgument('builder');
        $Payment = $event->getArgument('Payment');

        $paymentId = $Payment->getId();
        // 新規登録時は抜ける
        if (is_null($paymentId)) {
            return;
        }

        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->find($paymentId);
        if (is_null($YamatoPaymentMethod)) {
            return;
        }

        $memo05 = $YamatoPaymentMethod->getMemo05();
        switch ($YamatoPaymentMethod->getMemo03()) {

            // クレジットカード決済
            case $this->app['config']['YamatoPayment']['const']['YAMATO_PAYID_CREDIT']:
                // 初期値登録する場合
                if (!empty($memo05)) {
                    $builder->get('pay_way')->setData($memo05['pay_way']);
                    $builder->get('TdFlag')->setData($memo05['TdFlag']);
                    $builder->get('order_mail_title')->setData($memo05['order_mail_title']);
                    $builder->get('order_mail_body')->setData($memo05['order_mail_body']);
                    $builder->get('autoRegist')->setData($memo05['autoRegist']);
                }
                break;

            // コンビニ決済
            case $this->app['config']['YamatoPayment']['const']['YAMATO_PAYID_CVS']:
                // 初期値登録する場合
                if (!empty($memo05)) {
                    $builder->get('conveni')->setData($memo05['conveni']);

                    for ($no = $this->min; $no <= $this->max; $no++) {
                        $builder->get('order_mail_title_' . $no)->setData($memo05['order_mail_title_' . $no]);
                        $builder->get('order_mail_body_' . $no)->setData($memo05['order_mail_body_' . $no]);
                    }
                } else {
                    for ($no = $this->min; $no <= $this->max; $no++) {
                        $content = $this->app->renderView(
                            'YamatoPayment/Resource/template/admin/mail/cvs_' . $no . '.twig'
                        );
                        $builder->get('order_mail_body_' . $no)->setData($content);
                    }
                }
                break;

            // クロネコ代金後払い決済
            case $this->app['config']['YamatoPayment']['const']['YAMATO_PAYID_DEFERRED']:
                // 初期値登録する場合
                if (!empty($memo05)) {
                    $builder->get('order_mail_title')->setData($memo05['order_mail_title']);
                    $builder->get('order_mail_body')->setData($memo05['order_mail_body']);
                }
                break;

            default:
                break;
        }

    }

    /**
     * 支払方法設定画面：EditRenderイベント
     *
     * @param TemplateEvent $event
     */
    public function onAdminSettingShopPaymentEditRender(TemplateEvent $event)
    {
        $source = $event->getSource();
        $parameter = $event->getParameters();

        $paymentId = $parameter['payment_id'];
        // 新規登録時は抜ける
        if (is_null($paymentId)) {
            return;
        }

        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->find($paymentId);
        if (is_null($YamatoPaymentMethod)) {
            return;
        }

        $edit_snipet = null;
        $logo_snipet = null;

        switch ($YamatoPaymentMethod->getMemo03()) {
            // クレジットカード決済
            case $this->app['config']['YamatoPayment']['const']['YAMATO_PAYID_CREDIT']:
                $edit_snipet = file_get_contents(__DIR__ . '/../Resource/template/admin/Setting/Shop/payment_edit_credit.twig');
                $logo_snipet = file_get_contents(__DIR__ . '/../Resource/template/admin/Setting/Shop/payment_logo.twig');
                break;

            // コンビニ決済
            case $this->app['config']['YamatoPayment']['const']['YAMATO_PAYID_CVS']:
                $edit_snipet = file_get_contents(__DIR__ . '/../Resource/template/admin/Setting/Shop/payment_edit_conveni.twig');
                $logo_snipet = file_get_contents(__DIR__ . '/../Resource/template/admin/Setting/Shop/payment_logo.twig');
                break;

            // クロネコ代金後払い決済
            case $this->app['config']['YamatoPayment']['const']['YAMATO_PAYID_DEFERRED']:
                $edit_snipet = file_get_contents(__DIR__ . '/../Resource/template/admin/Setting/Shop/payment_edit_deferred.twig');
                $logo_snipet = file_get_contents(__DIR__ . '/../Resource/template/admin/Setting/Shop/payment_logo_deferred.twig');
                break;
            default:
                break;
        }

        // 各種決済の追加項目差し込み
        if (isset($edit_snipet)) {
            $search = '<div id="detail_list__back_button" class="row">';
            $replace = $edit_snipet . $search;
            $source = str_replace($search, $replace, $source);
        }

        // 各種ロゴDL用URL差し込み
        if (isset($logo_snipet)) {
            $search = '<div class="extra-form">';
            $replace = $logo_snipet . $search;
            $source = str_replace($search, $replace, $source);
        }

        $event->setSource($source);
    }

    /**
     * 支払方法設定画面：EditCompleteイベント
     *
     * @param EventArgs $event
     */
    public function onAdminSettingShopPaymentEditComplete($event)
    {
        $const = $this->app['config']['YamatoPayment']['const'];

        // パラメータ取得
        $Payment = $event->getArgument('Payment');
        /** @var Form $form */
        $form = $event->getArgument('form');

        $paymentId = $Payment->getId();
        // 新規登録時は抜ける
        if (is_null($paymentId)) {
            return;
        }

        // ヤマト決済情報の取得
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->find($paymentId);
        if (is_null($YamatoPaymentMethod)) {
            return;
        }

        $memo03 = $YamatoPaymentMethod->getMemo03();
        $data = array();
        switch ($memo03) {
            // コンビニ決済
            case $const['YAMATO_PAYID_CVS']:
                $data['conveni'] = $form['conveni']->getData();
                for ($no = $this->min; $no <= $this->max; $no++) {
                    $data['order_mail_title_' . $no] = $form['order_mail_title_' . $no]->getData();
                    $data['order_mail_body_' . $no] = $form['order_mail_body_' . $no]->getData();
                }
                break;

            // クレジットカード決済
            case $const['YAMATO_PAYID_CREDIT']:
                $data['pay_way'] = $form['pay_way']->getData();
                $data['TdFlag'] = $form['TdFlag']->getData();
                $data['order_mail_title'] = $form['order_mail_title']->getData();
                $data['order_mail_body'] = $form['order_mail_body']->getData();
                $data['autoRegist'] = $form['autoRegist']->getData();
                break;

            // クロネコ代金後払い決済
            case $const['YAMATO_PAYID_DEFERRED']:
                $data['order_mail_title'] = $form['order_mail_title']->getData();
                $data['order_mail_body'] = $form['order_mail_body']->getData();
                break;

            default:
                break;
        }

        $YamatoPaymentMethod->setMemo05($data);

        $this->app['orm.em']->persist($YamatoPaymentMethod);
        $this->app['orm.em']->flush();
    }
}
