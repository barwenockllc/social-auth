<?php

namespace Barwenock\SocialAuth\Model;

class FacebookCustomer extends \Magento\Framework\Model\AbstractModel implements
    \Magento\Framework\DataObject\IdentityInterface,
    \Barwenock\SocialAuth\Api\Data\FacebookCustomerInterface
{
    public const CACHE_TAG = 'socialauth_facebook_customer';

    /**
     * @var string
     */
    protected $_cacheTag = 'socialauth_facebook_customer';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'socialauth_facebook_customer';

    /**
     * Set resource model
     */
    public function _construct()
    {
        $this->_init(\Barwenock\SocialAuth\Model\ResourceModel\FacebookCustomer::class);
    }

    /**
     * Get identities.
     *
     * @return []
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Set EntityId
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get EntityId
     *
     * @return int
     */
    public function getEntityId()
    {
        return parent::getData(self::ENTITY_ID);
    }

    /**
     * Set CustomerId
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get CustomerId
     *
     * @return int
     */
    public function getCustomerId()
    {
        return parent::getData(self::CUSTOMER_ID);
    }

    /**
     * Set FacebookId
     *
     * @param string $facebookId
     * @return $this
     */
    public function setFacebookId($facebookId)
    {
        return $this->setData(self::FACEBOOK_ID, $facebookId);
    }

    /**
     * Get FacebookId
     *
     * @return string
     */
    public function getFacebookId()
    {
        return parent::getData(self::FACEBOOK_ID);
    }
}
