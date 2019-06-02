<?php

namespace Plugin\AdminLoginAlert\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AdminLoginAlertConfig
 */
class AdminLoginAlertConfig extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $email;


    /**
     * Set id
     *
     * @param integer $id
     * @return AdminLoginAlertConfig
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return AdminLoginAlertConfig
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }
}
