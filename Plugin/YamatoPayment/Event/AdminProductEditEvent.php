<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Eccube\Application;
use Eccube\Entity\Product;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Plugin\YamatoPayment\Entity\YamatoProduct;
use Symfony\Component\Validator\Constraints as Assert;

class AdminProductEditEvent extends AbstractEvent
{
    /**
     * 商品登録画面：Completeイベント
     *
     * @param EventArgs $event
     */
    public function onAdminProductEditComplete($event)
    {
        // 登録情報の取得
        /** @var Product $Product */
        $Product = $event->getArgument('Product');
        $form = $event->getArgument('form');

        $YamatoProduct = $this->app['yamato_payment.repository.yamato_product']->find($Product->getId());

        if (is_null($YamatoProduct)) {
            $YamatoProduct = new YamatoProduct();
            $YamatoProduct->setId($Product->getId());
        }

        // 商品マスタ追加項目を登録
        $YamatoProduct->setReserveDate($form['reserve_date']->getData());
        $YamatoProduct->setNotDeferredFlg($form['not_deferred_flg']->getData());

        $this->app['orm.em']->persist($YamatoProduct);
        $this->app['orm.em']->flush($YamatoProduct);
    }

    /**
     * 商品複製処理：Completeイベント
     *
     * @param EventArgs $event
     */
    public function onAdminProductCopyComplete($event)
    {
        // 複製元の商品IDを取得
        $product_id = $event->getArgument('Product')->getId();
        // 商品マスタ追加項目情報の取得
        $YamatoProduct = $this->app['yamato_payment.repository.yamato_product']->find($product_id);
        if (!is_null($YamatoProduct)) {
            // 複製後の商品IDを取得
            $copy_product_id = $event->getArgument('CopyProduct')->getId();

            // 複製後の商品マスタ追加項目情報をセット
            $CopyYamatoProduct = new YamatoProduct();
            $CopyYamatoProduct->setId($copy_product_id);
            $CopyYamatoProduct->setReserveDate($YamatoProduct->getReserveDate());
            $CopyYamatoProduct->setNotDeferredFlg($YamatoProduct->getNotDeferredFlg());

            $this->app['orm.em']->persist($CopyYamatoProduct);
            $this->app['orm.em']->flush($CopyYamatoProduct);
        }
    }

    /**
     * 商品登録画面：Renderイベント
     *
     * @param TemplateEvent $event
     */
    public function onAdminProductProductRender(TemplateEvent $event)
    {
        /* CSS追加 */
        $snipet = $this->app->renderView(
            'YamatoPayment/Resource/template/admin/Product/product_head.twig'
        );
        // HTML の書き換え
        $search = '{% endblock stylesheet %}';
        $replace = $snipet . $search;
        $source = str_replace($search, $replace, $event->getSource());

        /* JavaScript追加 */
        $snipet = $this->app->renderView(
            'YamatoPayment/Resource/template/admin/Product/product_javascript.twig'
        );
        // HTML の書き換え
        $search = '{% endblock javascript %}';
        $replace = $snipet . $search;
        $source = str_replace($search, $replace, $source);

        /* 予約商品出荷予定日・後払い不可商品項目追加 */
        $snipet = file_get_contents(__DIR__ . '/../Resource/template/admin/Product/product_edit.twig');
        // HTML の書き換え
        $search = '<div class="extra-form">';
        $replace = $snipet . $search;
        $source = str_replace($search, $replace, $source);

        $event->setSource($source);

        /* パラメータの追加 */
        $parameters = $event->getParameters();

        $pluginUtil = $this->app['yamato_payment.util.plugin'];

        // 追加パラメータを取得
        $addParams = array(
            'use_option' => $pluginUtil->getUserSettings('use_option'),
            'advance_sale' => $pluginUtil->getUserSettings('advance_sale')
        );

        // パラメータ追加
        $parameters = array_merge($parameters, $addParams);
        $event->setParameters($parameters);

    }
}
