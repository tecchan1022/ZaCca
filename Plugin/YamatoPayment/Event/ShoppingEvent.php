<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Eccube\Application;
use Eccube\Entity\Order;
use Eccube\Entity\Payment;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Eccube\Event\EccubeEvents;

class ShoppingEvent extends AbstractEvent
{
    /**
     * 注文内容確認画面；IndexInitializeイベント
     *
     * @param EventArgs $event
     */
    public function onFrontShoppingIndexInitialize(EventArgs $event)
    {
        $const = $this->app['config']['YamatoPayment']['const'];

        // 受注情報を取得
        /** @var Order $Order */
        $Order = $event->getArgument('Order');
        // フォーム情報を取得
        /** @var FormBuilder $builder */
        $builder = $event->getArgument('builder');

        // 受注商品に後払い不可商品有無の判定
        $not_deferred_flg = $this->app['yamato_payment.repository.yamato_product']->isNotDeferredFlg($Order);

        // 後払い不可商品が存在する場合
        if ($not_deferred_flg) {

            // 後払い決済情報取得
            /** @var YamatoPaymentMethod $YamatoPaymentMethod */
            $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']
                ->findOneBy(array('memo03' => $const['YAMATO_PAYID_DEFERRED']));

            // 画面の支払方法一覧を取得
            $Payments = $builder->get('payment')->getOption('choices');
            foreach ($Payments as $key => $Payment) {
                /** @var Payment $Payment */
                // 支払方法一覧にクロネコ代金後払いが存在する場合
                if ($Payment->getId() == $YamatoPaymentMethod->getId()) {

                    //支払方法一覧からクロネコ代金後払いを削除
                    unset($Payments[$key]);

                    // 受注情報の支払方法がクロネコ代金後払いの場合
                    if ($Order->getPayment()->getId() == $YamatoPaymentMethod->getId()) {
                        $Payment = current($Payments);
                        // 受注情報の支払方法を再セット
                        $Order->setPayment($Payment);
                        $Order->setPaymentMethod($Payment->getMethod());
                        $Order->setCharge($Payment->getCharge());

                        $total = (int)$Order->getSubTotal() + (int)$Order->getCharge() + (int)$Order->getDeliveryFeeTotal();
                        $Order->setTotal($total);
                        $Order->setPaymentTotal($total);

                        // 受注情報を最新状態に更新
                        $this->app['orm.em']->flush();
                    }

                    // フォームを仮作成
                    $builderTemp = $this->app['form.factory']->createBuilder('shopping', null, array(
                        'payments' => $Payments,
                        'payment' => $Order->getPayment(),
                    ));

                    // フォームの支払方法を上書き
                    $builder->add($builderTemp->get('payment'));

                    break;
                }
            }
        }

        // ヤマト決済かどうかのフラグをセッションに保持する
        $this->app['session']->set('yamato_payment.yamato_payment_flg', $this->isYamatoPayment($Order));
    }

    /**
     * 注文内容確認画面：IndexRenderイベント
     *
     * @param TemplateEvent $event
     */
    public function onShoppingIndexRender(TemplateEvent $event)
    {
        $Parameters = $event->getParameters();
        /** @var Order $Order */
        $Order = $Parameters['Order'];

        // 支払方法を取得
        $Payment = $Order->getPayment();
        if (is_null($Payment)) {
            return;
        }

        // ヤマト決済データ取得
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']
            ->find($Payment->getId());

        // ヤマト決済の場合
        if ($YamatoPaymentMethod) {
            // ボタンに表示する支払方法名を取得
            $buttonText = $Payment->getMethod() . 'へ';
            $search = '注文する';
            // 支払方法に合わせたボタン名へ変更する
            $source = str_replace($search, $buttonText, $event->getSource());
            $event->setSource($source);
        }
    }

    /**
     * 注文内容確認画面：ConfirmRequestイベント
     *
     * @param GetResponseEvent $event
     */
    public function onRouteFrontShoppingConfirmRequest(GetResponseEvent $event)
    {
        $app = $this->app;
        $request = $this->app['request'];

        if ('POST' !== $request->getMethod()) {
            $response = $app->redirect($app->url('cart'));
            $event->setResponse($response);
            return;
        }

        // カートの商品種別チェック
        if (!$app['yamato_payment.util.payment']->checkCartProductType()) {
            // カート画面へ戻る
            $event->setResponse($this->app->redirect($this->app->url('cart')));
            return;
        }

        // 支払い方法がヤマト決済でない場合
        if (!$app['session']->get('yamato_payment.yamato_payment_flg')) {
            return;
        }

        /** @var Order $Order */
        $Order = $app['eccube.service.shopping']->getOrder($app['config']['order_processing']);

        // form作成
        $builder = $app['eccube.service.shopping']->getShippingFormBuilder($Order);

// ここから
        $eventBase = new EventArgs(
                array(
                        'builder' => $builder,
                        'Order' => $Order,
                ),
                $request
        );
        $app['eccube.event.dispatcher']->dispatch(EccubeEvents::FRONT_SHOPPING_CONFIRM_INITIALIZE, $eventBase);
// ここまで

        $form = $builder->getForm();
        $form->handleRequest($app['request']);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            // 受注情報、配送情報を更新
            $app['eccube.service.shopping']->setOrderUpdate($Order, $formData);
            // ステータスを購入処理中に戻す
            $OrderStatus = $app['eccube.repository.master.order_status']->find($app['config']['order_processing']);
            $Order->setOrderStatus($OrderStatus);
            // 受注日をnullに戻す
            $Order->setOrderDate(null);
            $app['orm.em']->persist($Order);
            $app['orm.em']->flush();

            // ページ遷移の正当性を記録
            $app['yamato_payment.util.plugin']->setRegistSuccess();

            // 決済ページへリダイレクト
            $response = $this->app->redirect($this->app->url('yamato_shopping_payment'));
            $event->setResponse($response);
            return;
        }
    }

    /**
     * 注文完了画面：CompleteRenderイベント
     *
     * @param TemplateEvent $event
     */
    public function onShoppingCompleteRender(TemplateEvent $event)
    {
        $Parameters = $event->getParameters();
        $order_id = $Parameters['orderId'];
        if (is_null($order_id)) {
            return;
        }

        // 受注決済データ取得
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']
            ->find($order_id);
        if (is_null($YamatoOrderPayment)) {
            return;
        }

        // 受注完了画面差し込み用データ取得
        $memo02 = $YamatoOrderPayment->getMemo02();
        if (!empty($memo02)) {
            // 差し込むテンプレートの取得
            $insert = $this->app->renderView(
                'YamatoPayment/Resource/template/default/complete.twig', array(
                'listOther' => $memo02,
            ));
            $search = '<div id="deliveradd_input_box__top_button" class="row no-padding">';
            $replace = $insert . $search;
            $source = str_replace($search, $replace, $event->getSource());
            $event->setSource($source);
        }
    }

    /**
     * 注文内容確認画面：ShoppingRequestベント
     *
     * @param GetResponseEvent $event
     */
    public function onRouteShoppingRequest(GetResponseEvent $event)
    {
        // カートの商品種別チェック
        if (!$this->app['yamato_payment.util.payment']->checkCartProductType()) {
            // カート画面へ戻る
            $event->setResponse($this->app->redirect($this->app->url('cart')));
            return;
        }
    }

    /**
     * ヤマト決済かどうかの判定
     *
     * @param Order $Order
     * @return bool
     */
    public function isYamatoPayment($Order)
    {
        // 支払方法を取得
        /** @var Payment $Payment */
        $Payment = $Order->getPayment();

        if (is_null($Payment)) {
            return false;
        }

        // ヤマト決済かどうかの判定
        if (!is_null($this->app['yamato_payment.repository.yamato_payment_method']->find($Payment->getId()))) {
            return true;
        }
        return false;
    }
}
