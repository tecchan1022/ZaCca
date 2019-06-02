<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Web\Mypage;

use Plugin\YamatoPayment\Web\AbstractWebTestCase;

abstract class AbstractMypageWebTestCase extends AbstractWebTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->logIn();
    }
}
