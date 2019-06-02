<?php

namespace Plugin\SimpleSiteMaintenance\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SsmConfig
 */
class SsmConfig extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $mente_mode;

    /**
     * @var integer
     */
    private $admin_close_flg;

    /**
     * @var string
     */
    private $page_html;


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
     * Set mente_mode
     *
     * @param integer $menteMode
     * @return SsmConfig
     */
    public function setMenteMode($menteMode)
    {
        $this->mente_mode = $menteMode;

        return $this;
    }

    /**
     * Get mente_mode
     *
     * @return integer
     */
    public function getMenteMode()
    {
        return $this->mente_mode;
    }

    public function isMenteMode() {
        return ($this->getMenteMode() == 1);
    }

    /**
     * Set admin_close_flg
     *
     * @param integer $adminCloseFlg
     * @return SsmConfig
     */
    public function setAdminCloseFlg($adminCloseFlg)
    {
        $this->admin_close_flg = $adminCloseFlg;

        return $this;
    }

    /**
     * Get admin_close_flg
     *
     * @return integer
     */
    public function getAdminCloseFlg()
    {
        return $this->admin_close_flg;
    }

    public function isAdminCloseFlg()
    {
        return ($this->getAdminCloseFlg() == 1);
    }

    /**
     * Set page_html
     *
     * @param string $pageHtml
     * @return SsmConfig
     */
    public function setPageHtml($pageHtml)
    {
        $this->page_html = $pageHtml;

        return $this;
    }

    /**
     * Get page_html
     *
     * @return string
     */
    public function getPageHtml()
    {
        return $this->page_html;
    }
}
