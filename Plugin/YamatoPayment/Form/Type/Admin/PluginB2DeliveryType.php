<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type\Admin;

use Eccube\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PluginB2DeliveryType extends AbstractType
{
    /**
     * @var Application
     */
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
     * 設定画面の構築
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // クール便区分の一覧を取得する
        $listCoolKbType = $this->app['yamato_payment.util.payment']->getCool();
        // 配送コードの一覧を取得する
        $listDeliveryCode = $this->app['yamato_payment.util.payment']->getDeliveryCode();

        $builder
            ->add('delivery_id', 'hidden')
            ->add('delivery_name', 'hidden')
            ->add('cool_kb', 'choice', array(
                'choices' => $listCoolKbType,
                'expanded' => false,
            ))
            ->add('delivery_service_code', 'choice', array(
                'choices' => $listDeliveryCode,
                'expanded' => false,
            ))
            ->add('b2_delivtime_code', 'collection', array(
                'type' => new PluginB2DeliveryTimeType($this->app),
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'yamato_b2_delivery';
    }
}
