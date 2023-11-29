<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Api;

interface LoginTypeRepositoryInterface
{
    /**
     * Get by id
     *
     * @param int $entityId
     * @return \Barwenock\SocialAuth\Model\LoginType
     */
    public function getById($entityId);

    /**
     * Get by customer id
     *
     * @param mixed $customerId
     * @return \Barwenock\SocialAuth\Model\LoginType
     */
    public function getByCustomerId($customerId);

    /**
     * Save
     *
     * @param \Barwenock\SocialAuth\Model\LoginType $loginType
     * @return void
     */
    public function save(\Barwenock\SocialAuth\Model\LoginType $loginType);

    /**
     * Get list
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResults
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete
     *
     * @param \Barwenock\SocialAuth\Model\LoginType $loginType
     * @return boolean
     */
    public function delete(\Barwenock\SocialAuth\Model\LoginType $loginType);

    /**
     * Delete by id
     *
     * @param int $entityId
     * @return boolean
     */
    public function deleteById($entityId);
}
