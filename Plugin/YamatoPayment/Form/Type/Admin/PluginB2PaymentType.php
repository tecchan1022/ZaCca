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

class PluginB2PaymentType extends AbstractType
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
        // 送り状種別の一覧を取得する
        $listDelivSlipType = $this->app['yamato_payment.util.payment']->getDelivSlipType();

        $builder
            ->add('payment_id', 'hidden')
            ->add('payment_method', 'hidden')
            ->add('deliv_slip_type', 'choice', array(
                'choices' => $listDelivSlipType,
                'expanded' => false,
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'yamato_b2_payment';
    }
}
