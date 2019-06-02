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
use Eccube\Common\Constant;
use Eccube\Entity\Payment;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;

class YamatoPaymentMethodRepositoryTest extends AbstractRepositoryTestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function test_disableYamatoPaymentAll__ヤマト決済に対応する支払方法のdel_flgが1となること()
    {
        // ヤマト決済に対応する支払方法のdel_flgを0に設定
        $YamatoPaymentMethods = $this->app['yamato_payment.repository.yamato_payment_method']->findAll();
        foreach ($YamatoPaymentMethods as $YamatoPaymentMethod) {
            /** @var YamatoPaymentMethod $YamatoPaymentMethod */
            /** @var Payment $Payment */
            $Payment = $this->app['eccube.repository.payment']->find($YamatoPaymentMethod->getId());
            if (is_null($Payment)) {
                continue;
            }
            $Payment->setDelFlg(Constant::DISABLED);
        }
        $this->app['orm.em']->flush();

        // 全ての決済方法を無効にする
        $this->app['yamato_payment.repository.yamato_payment_method']->disableYamatoPaymentAll();

        // ヤマト決済に対応する支払方法を取得
        $YamatoPaymentMethods = $this->app['yamato_payment.repository.yamato_payment_method']->findAll();
        foreach ($YamatoPaymentMethods as $YamatoPaymentMethod) {
            /** @var YamatoPaymentMethod $YamatoPaymentMethod */
            /** @var Payment $Payment */
            $Payment = $this->app['eccube.repository.payment']->find($YamatoPaymentMethod->getId());

            // del_flgが1なことを確認
            $this->assertEquals(1, $Payment->getDelFlg());
        }
    }

    public function test_enablePaymentByPaymentType_決済方法情報がnullの場合何も返らない()
    {
        // 処理を実行する
        // 何も返らないこと
        $this->assertNull($this->app['yamato_payment.repository.yamato_payment_method']->enablePaymentByPaymentType(null));
    }

    public function test_enablePaymentByPaymentType__クレジットカード決済__クレジットカード決済が有効になること()
    {
        // 一旦、全ての決済方法を無効にする
        $this->app['yamato_payment.repository.yamato_payment_method']->disableYamatoPaymentAll();

        $payment_type = $this->const['YAMATO_PAYID_CREDIT'];

        // クレジットカード決済決済を有効にする
        $this->app['yamato_payment.repository.yamato_payment_method']->enablePaymentByPaymentType((int)$payment_type);

        // クレジットカード決済が有効なことを確認
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array('memo03' => $payment_type));
        /** @var Payment $Payment */
        $Payment = $this->app['eccube.repository.payment']->find($YamatoPaymentMethod->getId());
        $this->assertEquals(0, $Payment->getDelFlg());

    }

    public function test_enablePaymentByPaymentType__クロネコ代金後払い決済__手数料未設定の場合__クロネコ代金後払い決済が有効になること__手数料が設定されていること()
    {
        $payment_type = $this->const['YAMATO_PAYID_DEFERRED'];

        // クロネコ代金後払い決済の手数料を削除
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array('memo03' => $payment_type));
        /** @var Payment $Payment */
        $Payment = $this->app['eccube.repository.payment']->find($YamatoPaymentMethod->getId());
        $Payment->setCharge(null);
        $this->app['orm.em']->flush();

        // 一旦、全ての決済方法を無効にする
        $this->app['yamato_payment.repository.yamato_payment_method']->disableYamatoPaymentAll();

        // クロネコ代金後払い決済決済を有効にする
        $this->app['yamato_payment.repository.yamato_payment_method']->enablePaymentByPaymentType((int)$payment_type);

        // クロネコ代金後払い決済が有効なことを確認
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array('memo03' => $payment_type));
        /** @var Payment $Payment */
        $Payment = $this->app['eccube.repository.payment']->find($YamatoPaymentMethod->getId());
        $this->assertEquals(0, $Payment->getDelFlg());
        $this->assertNotNull($Payment->getCharge());
    }
}
