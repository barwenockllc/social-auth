<?php

namespace Barwenock\SocialAuth\Model\ResourceModel;

class FacebookCustomer extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('socialauth_facebook_customer', 'entity_id');
    }
}
