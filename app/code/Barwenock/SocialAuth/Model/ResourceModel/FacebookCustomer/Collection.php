<?php

namespace Barwenock\SocialAuth\Model\ResourceModel\FacebookCustomer;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            \Barwenock\SocialAuth\Model\FacebookCustomer::class,
            \Barwenock\SocialAuth\Model\ResourceModel\FacebookCustomer::class
        );
    }
}
