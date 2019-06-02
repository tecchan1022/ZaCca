<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;

class OrderShippingType extends AbstractType
{
    public function __construct($app) {
        $this->app = $app;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $deliveryServiceCode = $this->getDeliveryServiceCode($this->app['request']->get('id'));
        if($deliveryServiceCode == '99') {
            // 他社配送の場合
            $builder->add('deliv_slip_number', 'text', array(
                    'label' => '送り状番号',
                    'required' => false,
                    'attr' => array(
                            'maxlength' => '50',
                    ),
                    'constraints' => array(
                            new Assert\Length(array(
                                    'max' => 50,
                            )),
                            new Assert\Regex(array(
                                    'pattern' => "/^[0-9]*$/",
                                    'match' => true,
                                    'message' => '※ 送り状番号は数字で入力してください。'
                            )),
                    ),
            ));
        } else {
            // ヤマトの場合
            $builder->add('deliv_slip_number', 'text', array(
                'label' => '送り状番号',
                'required' => false,
                'attr' => array(
                    'maxlength' => '12',
                ),
                'constraints' => array(
                    new Assert\Length(array(
                        'min' => 12,
                        'max' => 12,
                        'exactMessage' => "※ 送り状番号は12桁で入力して下さい。",
                    )),
                    new Assert\Regex(array(
                        'pattern' => "/^[0-9]*$/",
                        'match' => true,
                        'message' => '※ 送り状番号は数字で入力してください。'
                    )),
                ),
            ));
        }
    }

    /**
     * 配送業者IDをから送り状番号の種別を取得する
     * @return NULL|00|99
     */
    public function getDeliveryServiceCode($orderId) {
//         $pluginUtil = $this->app['yamato_payment.util.plugin'];
//         $deliveryServiceCode = $pluginUtil->getDeliveryServiceCode($orderId);
//         return $deliveryServiceCode;
        $pluginUtil = $this->app['yamato_payment.util.plugin'];
        $deliveryServiceCode = $pluginUtil->getSubData();
        return $deliveryServiceCode["user_settings"]["delivery_service_code"];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'yamato_shipping';
    }
}
