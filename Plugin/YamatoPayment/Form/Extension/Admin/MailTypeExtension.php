<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Extension\Admin;

use Eccube\Application;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * メールタイプエクステンション
 */
class MailTypeExtension extends AbstractTypeExtension
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
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();

                /*
                 * メール送信イベントでテンプレートIDを判定するための項目を追加。
                 * form['template']がメール送信イベントで取れないためこうする...
                 * 画面では使用しない。
                 */
                $form->add('mail_template_id', 'text', array('property_path' => '[template]'));

                $data['mail_template_id'] = $data['template'];
                $event->setData($data);
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
        return 'mail';
    }

}
