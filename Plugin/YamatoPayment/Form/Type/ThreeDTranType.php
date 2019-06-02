<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type;

use Eccube\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ThreeDTranType extends AbstractType
{
    /**
     * カード情報画面 共通箇所の構築
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('COMP_CD', 'hidden')
            ->add('CARD_NO', 'hidden')
            ->add('CARD_EXP', 'hidden')
            ->add('ITEM_PRICE', 'hidden')
            ->add('ITEM_TAX', 'hidden')
            ->add('CUST_CD', 'hidden')
            ->add('SHOP_ID', 'hidden')
            ->add('TERM_CD', 'hidden')
            ->add('CRD_RES_CD', 'hidden')
            ->add('RES_VE', 'hidden')
            ->add('RES_PA', 'hidden')
            ->add('RES_CODE', 'hidden')
            ->add('3D_INF', 'hidden')
            ->add('3D_TRAN_ID', 'hidden')
            ->add('SEND_DT', 'hidden')
            ->add('HASH_VALUE', 'hidden')
            ->add('TOKEN', 'hidden');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
    }
}
