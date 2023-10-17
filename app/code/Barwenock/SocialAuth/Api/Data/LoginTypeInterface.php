<?php

namespace Barwenock\SocialAuth\Api\Data;

interface LoginTypeInterface
{
    public const ENTITY_ID = 'entity_id';

    public const CUSTOMER_ID = 'customer_id';

    public const LOGIN_TYPE = 'login_type';

    /**
     * Set EntityId
     *
     * @param int $entityId
     * @return \Barwenock\SocialAuth\Api\Data\LoginTypeInterface
     */
    public function setEntityId($entityId);

    /**
     * Get EntityId
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Set CustomerId
     *
     * @param int $customerId
     * @return \Barwenock\SocialAuth\Api\Data\LoginTypeInterface
     */
    public function setCustomerId($customerId);

    /**
     * Get CustomerId
     *
     * @return int
     */
    public function getCustomerId();

    /**
     * Set LoginType
     *
     * @param string $loginType
     * @return \Barwenock\SocialAuth\Api\Data\LoginTypeInterface
     */
    public function setLoginType($loginType);

    /**
     * Get LoginType
     *
     * @return string
     */
    public function getLoginType();
}
