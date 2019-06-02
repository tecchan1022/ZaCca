<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type;

use Eccube\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CvsType extends AbstractType
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
     * コンビニ選択画面 共通箇所の構築
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // コンビニの名称一覧を取得する
        $allConveniStores = $this->app['yamato_payment.util.payment']->getConveni();
        $yamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->app['config']['YamatoPayment']['const']['YAMATO_PAYID_CVS']));
        $memo05 = $yamatoPaymentMethod->getMemo05();
        $enableConvineStores = array_values((array)$memo05['conveni']);

        $conveniStores = array();
        foreach ($allConveniStores as $key => $val){
            if (in_array($key, $enableConvineStores)) {
                $conveniStores[$key] = $val;
            }
        }

        $builder
            ->add('cvs', 'choice', array(
                'choices' => $conveniStores,
                'expanded' => true,
                'required' => false,
                'empty_value' => false,
                'constraints' => array(
                    new Assert\NotBlank(array('message' => '※ コンビニ選択が入力されていません。')),
                ),
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'regist_cvs';
    }
}
