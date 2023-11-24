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
     * @param int $id
     * @return \Barwenock\SocialAuth\Model\LoginType
     */
    public function getById($id);

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
     * @param \Barwenock\SocialAuth\Model\LoginType $subject
     * @return void
     */
    public function save(\Barwenock\SocialAuth\Model\LoginType $subject);

    /**
     * Get list
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $creteria
     * @return \Magento\Framework\Api\SearchResults
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $creteria);

    /**
     * Delete
     *
     * @param \Barwenock\SocialAuth\Model\LoginType $subject
     * @return boolean
     */
    public function delete(\Barwenock\SocialAuth\Model\LoginType $subject);

    /**
     * Delete by id
     *
     * @param int $id
     * @return boolean
     */
    public function deleteById($id);
}
