<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Extension\Admin;

use Eccube\Entity\OrderDetail;
use Plugin\YamatoPayment\Form\Extension\AbstractTypeExtensionTestCase;
use Symfony\Component\HttpFoundation\Request;

class OrderTypeExtensionTest extends AbstractTypeExtensionTestCase
{
    /** @var \Eccube\Application */
    protected $app;

    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var array デフォルト値（正常系）を設定 */
    protected $formData = array(
        'name' => array(
            'name01' => 'たかはし',
            'name02' => 'しんいち',
        ),
        'kana'=> array(
            'kana01' => 'タカハシ',
            'kana02' => 'シンイチ',
        ),
        'company_name' => 'ロックオン',
        'zip' => array(
            'zip01' => '530',
            'zip02' => '0001',
        ),
        'address' => array(
            'pref' => '5',
            'addr01' => '北区',
            'addr02' => '梅田',
        ),
        'tel' => array(
            'tel01' => '012',
            'tel02' => '345',
            'tel03' => '6789',
        ),
        'fax' => array(
            'fax01' => '112',
            'fax02' => '345',
            'fax03' => '6789',
        ),
        'email' => 'default@example.com',
        'discount' => '1',
        'delivery_fee_total' => '1',
        'charge' => '1',
        'OrderStatus' => '1',
        'Payment' => '1', // dtb_payment?
        'OrderDetails' => array(),
        'Shippings' => array(),
        'YamatoShippings' => array(),
        'scheduled_shipping_date' => '',
        'Customer' => array(),

    );

    public function setUp()
    {
        parent::setUp();

        // CSRF tokenを無効にしてFormを作成
        // 送り状番号登録・編集
        $this->form = $this->app['form.factory']
            ->createBuilder('order', null, array(
                'csrf_protection' => false,
            ))
            ->getForm();


        $Order = $this->createOrder($this->createCustomer());

        // OrderDetails の設定
        $OrderDetails = array();
        foreach($Order->getOrderDetails() as $orderDetail) {
            /** @var OrderDetail $orderDetail */
            $price = $orderDetail->getPrice();
            $quantity = $orderDetail->getQuantity();
            $tax_rate = $orderDetail->getTaxRate();

            $OrderDetails = array(
                'price' => $price,
                'quantity' => $quantity,
                'tax_rate' => $tax_rate,
            );
        }

        $this->formData['OrderDetails'] = array($OrderDetails);
    }

    public function testValidData()
    {
        $this->app['request'] = new Request();
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }
}
