<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Eccube\Application;

class CartEvent extends AbstractEvent
{
    /**
     * カート画面：CartRequestベント
     *
     */
    public function onRouteCartRequest()
    {
        $app = $this->app;
        $request = $this->app['request'];
        $pre_page = $request->getSession()->get('yamato_payment.pre_page');

        // カートインの場合のみ以下の処理を通る
        if (strpos($pre_page, 'shopping') === false ) {

            // カートの商品種別チェック
            $app['yamato_payment.util.payment']->checkCartProductType(true);
        }
    }
}
