<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Plugin\AdminLoginAlert;

use Eccube\Application;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class SecurityEventListener
{
    public $app;

    public function __construct(\Silex\Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $token = $event->getAuthenticationToken();
        $user = $token->getUser();
        if ($user instanceof \Eccube\Entity\Member) {
            $this->app = Application::getInstance();
            $adminLoginAlertConfig = $this->app['plugin.admin_login_alert.repository.admin_login_alert_config']->find(1);
            if ($adminLoginAlertConfig && $adminLoginAlertConfig->getEmail()) {
                $now = new \DateTime();
                //ログイン者と管理者へメール送信
                $body = '[日時]' . $now->format('Y/m/d H:i:s') . "\n";
                $body .= '[ID]' . $user->getLoginId() . "\n";
                $body .= '[IP]' . $this->app['request']->getClientIp() . "\n";
                $body .= '[UA]' . $_SERVER['HTTP_USER_AGENT'] . "\n";
                $this->app['plugin.admin_login_alert.service.mail']->sendAdminLoginAlertMail($body, $adminLoginAlertConfig->getEmail());
            }
        }
    }
}
