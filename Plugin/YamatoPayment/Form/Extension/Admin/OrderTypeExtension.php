<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Extension\Admin;

use Eccube\Application;
use Plugin\YamatoPayment\Form\Type\Admin\OrderShippingType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * 受注タイプエクステンション
 */
class OrderTypeExtension extends AbstractTypeExtension
{
    private $app;

    /**
     * コンストラクタ
     *
     * @param Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 設定画面の構築
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('YamatoShippings', 'collection', array(
                'type' => new OrderShippingType($this->app),
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'mapped' => false,
            ))
            ->add('scheduled_shipping_date', 'text', array(
                'required' => false,
                'attr' => array(
                    'class' => 'width222',
                    'maxlength' => '8',
                ),
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 8,
                        'maxMessage' => '半角数字8文字（例）20140401'
                    )),
                ),
                'mapped' => false,
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
    }

    public function getExtendedType()
    {
        return 'order';
    }

}
