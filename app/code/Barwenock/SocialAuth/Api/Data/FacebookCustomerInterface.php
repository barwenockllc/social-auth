<?php

namespace Barwenock\SocialAuth\Api\Data;

interface FacebookCustomerInterface
{
    public const ENTITY_ID = 'entity_id';

    public const CUSTOMER_ID = 'customer_id';

    public const FACEBOOK_ID = 'facebook_id';

    /**
     * Set EntityId
     *
     * @param int $entityId
     * @return \Barwenock\SocialAuth\Api\Data\FacebookCustomerInterface
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
     * @return \Barwenock\SocialAuth\Api\Data\FacebookCustomerInterface
     */
    public function setCustomerId($customerId);

    /**
     * Get CustomerId
     *
     * @return int
     */
    public function getCustomerId();

    /**
     * Set FacebookId
     *
     * @param string $facebookId
     * @return \Barwenock\SocialAuth\Api\Data\FacebookCustomerInterface
     */
    public function setFacebookId($facebookId);

    /**
     * Get FacebookId
     *
     * @return string
     */
    public function getFacebookId();
}
