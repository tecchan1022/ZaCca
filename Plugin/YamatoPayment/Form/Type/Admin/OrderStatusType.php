<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type\Admin;

use Eccube\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class OrderStatusType extends AbstractType
{
    /** @var Application */
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
        // 無名関数対策
        $app = $this->app;

        // 対応状況から｢購入処理中｣｢決済処理中｣を除く一覧を取得
        $listOrderStatus = array();
        $orderStatuses = $this->app['eccube.repository.order_status']->findAllArray();
        foreach ($orderStatuses as $key => $value) {
            if ($key != $this->app['config']['order_pending'] && $key != $this->app['config']['order_processing']) {
                $listOrderStatus[$key] = $value;
            }
        }

        $builder
            ->add('order_date_start', 'date', array(
                'label' => '注文日(FROM)',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'empty_value' => array('year' => '----', 'month' => '--', 'day' => '--'),
            ))
            ->add('order_date_end', 'date', array(
                'label' => '注文日(TO)',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'empty_value' => array('year' => '----', 'month' => '--', 'day' => '--'),
            ))
            ->add('current_payment_status', 'hidden')
            ->add('current_payment_type', 'hidden')
            ->add('status', 'entity', array(
                'class' => 'Eccube\Entity\Master\OrderStatus',
                'property' => 'name',
                'empty_value' => '-',
                'empty_data' => null,
                'query_builder' => function($er) use($app) {
                    return $er->createQueryBuilder('o')
                        ->andWhere('o.id <> :id')
                        ->setParameter('id', $app['config']['order_pending'])
                        ->andWhere('o.id <> :id2')
                        ->setParameter('id2', $app['config']['order_processing']);
                },
            ))
            ->add('payment_status', 'choice', array(
                'choices' => array(
                    'commit' => '出荷情報登録',
                    'cancel' => '決済取消',
                    'reauth' => '再与信',
                ),
                'placeholder' => '-',
                'required' => false,
                'expanded' => false,
                'multiple' => false
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'yamato_order_status';
    }
}
