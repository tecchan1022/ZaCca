<?php
/*
 * Copyright(c) 2016 SYSTEM_KD
 */

namespace Plugin\SimpleSiteMaintenance\Form\Type;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\Extension\Core\Type;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PluginConfigType extends AbstractType
{

    public $app;

    public function __construct(\Silex\Application $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('mente_mode', 'choice', array(
                'label' => 'メンテナンス',
                'choices' => array(0 => 'OFF', 1 => 'ON'),
                'expanded' => true,
                'required' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('admin_close_flg', 'choice', array(
                'label' => '管理者のアクセス',
                'choices' => array(0 => '許可する', 1 => '許可しない'),
                'expanded' => true,
                'required' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('page_html', 'textarea', array(
                'label' => 'テキストエリア',
                'required' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array(
                        'max' => $this->app['config']['lltext_len'],
                    )),
                ),
            ))
            ->addEventSubscriber(new \Eccube\Event\FormEventSubscriber())
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => '\Plugin\SimpleSiteMaintenance\Entity\SsmConfig',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'plg_ssm_config';
    }
}
