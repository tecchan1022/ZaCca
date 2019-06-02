<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment;

use Eccube\Application;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class YamatoPaymentEvent
{
    /** @var Application */
    private $app;

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
     * 受注編集画面：IndexInitializeイベント
     *
     * @param EventArgs $event
     */
    public function onAdminOrderEditIndexInitialize(EventArgs $event)
    {
        $this->app['yamato_payment.event.admin.order.edit']
            ->onAdminOrderEditIndexInitialize($event);
    }

    /**
     * 受注編集画面：IndexCompleteイベント
     *
     * @param EventArgs $event
     */
    public function onAdminOrderEditIndexComplete(EventArgs $event)
    {
        $this->app['yamato_payment.event.admin.order.edit']
            ->onAdminOrderEditIndexComplete($event);
    }

    /**
     * 受注編集画面：Renderイベント
     *
     * @param TemplateEvent $event
     */
    public function onAdminOrderEditRender(TemplateEvent $event)
    {
        $this->app['yamato_payment.event.admin.order.edit']
            ->onAdminOrderEditRender($event);
    }

    /**
     * 受注編集画面：Beforeイベント
     *
     * @param GetResponseEvent $event
     */
    public function onRouteAdminOrderEditRequest(GetResponseEvent $event)
    {
        $this->app['yamato_payment.event.admin.order.edit']
            ->onRouteAdminOrderEditRequest($event);
    }

    /**
     * 受注管理画面：Searchイベント
     *
     * @param EventArgs $event
     */
    public function onAdminOrderIndexSearch(EventArgs $event)
    {
        $this->app['yamato_payment.event.admin.order.index']
            ->onAdminOrderIndexSearch($event);
    }

    /**
     * 受注管理画面：Renderイベント
     *
     * @param TemplateEvent $event
     */
    public function onAdminOrderIndexRender(TemplateEvent $event)
    {
        $this->app['yamato_payment.event.admin.order.index']
            ->onAdminOrderIndexRender($event);
    }

    /**
     * 受注メール送信画面：ConfirmRenderイベント
     *
     * @param TemplateEvent $event
     */
    public function onAdminOrderMailConfirmRender(TemplateEvent $event)
    {
        $this->app['yamato_payment.event.admin.order.mail']
            ->onAdminOrderMailConfirmRender($event);
    }

    /**
     * 受注メール送信画面：IndexCompleteイベント
     *
     * @param EventArgs $event
     */
    public function onAdminOrderMailIndexComplete(EventArgs $event)
    {
        $this->app['yamato_payment.event.admin.order.mail']
            ->onAdminOrderMailIndexComplete($event);
    }

    /**
     * 受注メール送信画面：MailAllCompleteイベント
     *
     * @param EventArgs $event
     */
    public function onAdminOrderMailMailAllComplete(EventArgs $event)
    {
        $this->app['yamato_payment.event.admin.order.mail']
            ->onAdminOrderMailMailAllComplete($event);
    }

    /**
     * 受注メール送信画面：MailAllConfirmRenderイベント
     *
     * @param TemplateEvent $event
     */
    public function onAdminOrderMailAllConfirmRender(TemplateEvent $event)
    {
        $this->app['yamato_payment.event.admin.order.mail']
            ->onAdminOrderMailAllConfirmRender($event);
    }

    /**
     * 商品登録画面：EditCompleteイベント
     *
     * @param EventArgs $event
     */
    public function onAdminProductEditComplete(EventArgs $event)
    {
        $this->app['yamato_payment.event.admin.product.edit']
            ->onAdminProductEditComplete($event);
    }

    /**
     * 商品複製処理：CopyCompleteイベント
     *
     * @param EventArgs $event
     */
    public function onAdminProductCopyComplete(EventArgs $event)
    {
        $this->app['yamato_payment.event.admin.product.edit']
            ->onAdminProductCopyComplete($event);
    }

    /**
     * 商品登録画面：ProductRenderイベント
     *
     * @param TemplateEvent $event
     */
    public function onAdminProductProductRender(TemplateEvent $event)
    {
        $this->app['yamato_payment.event.admin.product.edit']
            ->onAdminProductProductRender($event);
    }

    /**
     * 支払方法設定画面：EditInitializeイベント
     *
     * @param EventArgs $event
     */
    public function onAdminSettingShopPaymentEditInitialize(EventArgs $event)
    {
        $this->app['yamato_payment.event.admin.setting.shop.payment.edit']
            ->onAdminSettingShopPaymentEditInitialize($event);
    }

    /**
     * 支払方法設定画面：EditCompleteイベント
     *
     * @param EventArgs $event
     */
    public function onAdminSettingShopPaymentEditComplete(EventArgs $event)
    {
        $this->app['yamato_payment.event.admin.setting.shop.payment.edit']
            ->onAdminSettingShopPaymentEditComplete($event);
    }

    /**
     * 支払方法設定画面：EditRenderイベント
     *
     * @param TemplateEvent $event
     */
    public function onAdminSettingShopPaymentEditRender(TemplateEvent $event)
    {
        $this->app['yamato_payment.event.admin.setting.shop.payment.edit']
            ->onAdminSettingShopPaymentEditRender($event);
    }

    /**
     * 注文確認メール送信：Renderイベント
     *
     * @param EventArgs $event
     */
    public function onMailOrderRender(EventArgs $event)
    {
        $this->app['yamato_payment.event.mail']
            ->onMailOrderRender($event);
    }

    /**
     * 受注メール送信：Renderイベント
     *
     * @param EventArgs $event
     */
    public function onMailAdminOrderRender(EventArgs $event)
    {
        $this->app['yamato_payment.event.mail']
            ->onMailAdminOrderRender($event);
    }

    /**
     * フロント画面共通：FrontRequestイベント
     */
    public function onFrontRequest()
    {
        $this->app['yamato_payment.event.front']
            ->onFrontRequest();
    }

    /**
     * マイページ：Beforeイベント
     *
     * @param FilterResponseEvent $event
     */
    public function onRenderMypageBefore(FilterResponseEvent $event)
    {
        $this->app['yamato_payment.event.mypage']
            ->onRenderMypageBefore($event);
    }

    /**
     * 注文内容確認画面；IndexInitializeイベント
     *
     * @param EventArgs $event
     */
    public function onFrontShoppingIndexInitialize(EventArgs $event)
    {
        $this->app['yamato_payment.event.shopping']
            ->onFrontShoppingIndexInitialize($event);
    }

    /**
     * 注文内容確認画面：IndexRenderイベント
     *
     * @param TemplateEvent $event
     */
    public function onShoppingIndexRender(TemplateEvent $event)
    {
        $this->app['yamato_payment.event.shopping']
            ->onShoppingIndexRender($event);
    }

    /**
     * 注文内容確認画面：ConfirmInitializeイベント
     *
     * @param GetResponseEvent $event
     */
    public function onRouteFrontShoppingConfirmRequest(GetResponseEvent $event)
    {
        $this->app['yamato_payment.event.shopping']
            ->onRouteFrontShoppingConfirmRequest($event);
    }

    /**
     * 注文完了画面：CompleteRenderイベント
     *
     * @param TemplateEvent $event
     */
    public function onShoppingCompleteRender(TemplateEvent $event)
    {
        $this->app['yamato_payment.event.shopping']
            ->onShoppingCompleteRender($event);
    }

    /**
     * カート画面：CartRequestベント
     *
     */
    public function onRouteCartRequest()
    {
        $this->app['yamato_payment.event.cart']
            ->onRouteCartRequest();
    }

    /**
     * 注文内容確認画面：ShoppingRequestベント
     *
     * @param GetResponseEvent $event
     */
    public function onRouteShoppingRequest(GetResponseEvent $event)
    {
        $this->app['yamato_payment.event.shopping']
            ->onRouteShoppingRequest($event);
    }

}
