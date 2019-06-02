<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Extension\Admin;

use Eccube\Application;
use Eccube\Entity\Master\ProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * 受注検索タイプエクステンション
 */
class SearchOrderTypeExtension extends AbstractTypeExtension
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
        $app = $this->app;

        $builder
            ->add('product_type', 'product_type', array(
                'label' => '商品種別',
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
            ))
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($app) {
                $form = $event->getForm();

                // セッションから検索条件を復元
                $ProductTypes = $app['session']->get('yamato_payment.admin.order.search.product_type');
                if (count($ProductTypes) > 0) {
                    $type_ids = array();
                    foreach ($ProductTypes as $ProductType) {
                        /** @var ProductType $ProductType */
                        $type_ids[] = $ProductType->getId();
                    }
                    $ProductTypes = $app['eccube.repository.master.product_type']->findBy(array('id' => $type_ids));
                    // フォームに設定
                    $form->get('product_type')->setData($ProductTypes);
                }
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($app) {
                $form = $event->getForm();

                // 検索条件をセッションに保持
                $ProductTypes = $form->get('product_type')->getData();
                $app['session']->set('yamato_payment.admin.order.search.product_type', $ProductTypes);
            });
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
    }

    public function getExtendedType()
    {
        return 'admin_search_order';
    }

}
