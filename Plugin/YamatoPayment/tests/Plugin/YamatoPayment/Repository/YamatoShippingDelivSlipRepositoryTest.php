<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Plugin\YamatoPayment\Repository;

use Eccube\Application;
use Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip;

class YamatoShippingDelivSlipRepositoryTest extends AbstractRepositoryTestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function test_getDelivSlipByShippings_配送情報に紐づく配送伝票情報が存在しない場合__出荷IDと受注IDのみの配送伝票番号を新規作成する()
    {
        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer());

        // 配送情報に紐づく配送伝票情報を取得
        $ShippingDelivSlips = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']->getDelivSlipByShippings($Order->getShippings());

        // 配送伝票情報が存在することを確認
        $this->assertNotNull($ShippingDelivSlips);

        foreach($ShippingDelivSlips as $ShippingDelivSlip) {
            /** @var YamatoShippingDelivSlip $ShippingDelivSlip */
            // 出荷IDがセットされていること
            $this->assertNotNull($ShippingDelivSlip->getId());

            // 受注IDがセットされていること
            $this->assertNotNull($ShippingDelivSlip->getOrderId());
        }
    }
}
