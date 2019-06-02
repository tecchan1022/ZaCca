<?php
/*
 * Copyright(c) 2015 SystemFriend Inc. All rights reserved.
 * http://ec-cube.systemfriend.co.jp/
 */

namespace Plugin\GoogleAnalyticsSimpleSetup\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ConfigType extends AbstractType
{
    private $config;
    private $arrForm;

    public function __construct($config)
    {
        $this->config  = $config;
    }

    /**
     * Build config type form
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return type
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $config = $this->config;

        $builder
            ->add('transaction_id', 'text', array(
                'label' => 'トラッキング ID',
                'required' => true,
                'attr' => array(
                    'maxlength' => $config['stext_len'],
                ),
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array('max' => $config['stext_len'])),
                    new Assert\Regex(array('pattern' => '/^[[:graph:][:space:]]+$/i')),
                ),
            ))
            ->addEventSubscriber(new \Eccube\Event\FormEventSubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'plugin_GoogleAnalyticsSimpleSetup_config';
    }
}
