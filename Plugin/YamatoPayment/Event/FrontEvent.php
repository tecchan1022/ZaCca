<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Eccube\Application;

class FrontEvent extends AbstractEvent
{
    /**
     * フロント画面共通：FrontRequestイベント
     */
    public function onFrontRequest()
    {
        // ページのパスをセッションに保持する
        $this->app['yamato_payment.util.plugin']->savePagePath($this->app['request']->getPathInfo());
    }

}
