<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Model;

class LoginType extends \Magento\Framework\Model\AbstractModel implements
    \Magento\Framework\DataObject\IdentityInterface,
    \Barwenock\SocialAuth\Api\Data\LoginTypeInterface
{
    public const CACHE_TAG = 'socialauth_login_type';

    /**
     * @var string
     */
    protected $_cacheTag = 'socialauth_login_type';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'socialauth_login_type';

    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init(\Barwenock\SocialAuth\Model\ResourceModel\LoginType::class);
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
        return $this->getData(self::ENTITY_ID);
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
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * Set LoginType
     *
     * @param string $loginType
     * @return $this
     */
    public function setLoginType($loginType)
    {
        return $this->setData(self::LOGIN_TYPE, $loginType);
    }

    /**
     * Get LoginType
     *
     * @return string
     */
    public function getLoginType()
    {
        return $this->getData(self::LOGIN_TYPE);
    }
}
