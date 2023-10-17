<?php

namespace Barwenock\SocialAuth\Model\ResourceModel;

class LoginType extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('socialauth_login_type', 'entity_id');
    }
}
