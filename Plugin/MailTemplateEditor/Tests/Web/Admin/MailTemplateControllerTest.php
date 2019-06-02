<?php
/*
  * This file is part of the MailTemplateEditor plugin
  *
  * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
  *
  * For the full copyright and license information, please view the LICENSE
  * file that was distributed with this source code.
  */

namespace Plugin\MailTemplateEditor\Tests\Web\Admin;

use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;

class MailTemplateControllerTest extends AbstractAdminWebTestCase
{
    public function testRoutingAdminContentMail()
    {
        $client = $this->client;
        $client->request('GET',
            $this->app->url('plugin_MailTemplateEditor_mail')
        );
        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testRoutingAdminContentMailGet()
    {
        $client = $this->client;

        $client->request('GET',
            $this->app->url('plugin_MailTemplateEditor_mail_edit', array('name' => 'order.twig'))
        );
        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testRoutingAdminContentMailEdit()
    {
        $client = $this->client;

        $client->request(
            'POST',
            $this->app->url('plugin_MailTemplateEditor_mail_edit', array('name' => 'order.twig')),
            array(
                'admin_mail_template' => array(
                    'tpl_data' => 'testtest',
                    '_token' => 'dummy',
                ),
                'name' => 'order.twig',
            )
        );

        $this->assertTrue($client->getResponse()->isRedirect($this->app->url('plugin_MailTemplateEditor_mail_edit', array('name' => 'order.twig'))));

        $this->expected = 'testtest';
        $this->actual = file_get_contents($this->app['config']['template_realdir'].'/Mail/order.twig');
        $this->verify();
    }

    public function testRoutingAdminContentMailReEdit()
    {
        $client = $this->client;

        $crawler = $client->request(
            'PUT',
            $this->app->url('plugin_MailTemplateEditor_mail_reedit', array('name' => 'order.twig'))
        );

        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->expected = file_get_contents($this->app['config']['template_default_realdir'].'/Mail/order.twig');
        $this->actual = file_get_contents($this->app['config']['template_realdir'].'/Mail/order.twig');
        $this->verify();
    }
}
