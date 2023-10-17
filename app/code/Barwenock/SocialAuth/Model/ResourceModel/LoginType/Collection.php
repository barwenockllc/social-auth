<?php

namespace Barwenock\SocialAuth\Model\ResourceModel\LoginType;

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
            \Barwenock\SocialAuth\Model\LoginType::class,
            \Barwenock\SocialAuth\Model\ResourceModel\LoginType::class
        );
    }
}
