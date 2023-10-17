<?php

namespace Barwenock\SocialAuth\Api;

interface FacebookCustomerRepositoryInterface
{
    /**
     * Get customer collection by customer id
     *
     * @param  integer $customerId customer id
     * @return object
     */
    public function getByCustomerId($customerId);

    /**
     * Get customer collection by facebook id
     *
     * @param  integer $facebookId facebook id of customer
     * @return object
     */
    public function getByFacebookId($facebookId);

    /**
     * Get by id
     *
     * @param int $id
     * @return \Barwenock\SocialAuth\Model\FacebookCustomer
     */
    public function getById($id);

    /**
     * Save
     *
     * @param \Barwenock\SocialAuth\Model\FacebookCustomer $subject
     * @return void
     */
    public function save(\Barwenock\SocialAuth\Model\FacebookCustomer $subject);

    /**
     * Get list
     *
     * @param Magento\Framework\Api\SearchCriteriaInterface $creteria
     * @return Magento\Framework\Api\SearchResults
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $creteria);

    /**
     * Delete
     *
     * @param \Barwenock\SocialAuth\Model\FacebookCustomer $subject
     * @return boolean
     */
    public function delete(\Barwenock\SocialAuth\Model\FacebookCustomer $subject);

    /**
     * Delete by id
     *
     * @param int $id
     * @return boolean
     */
    public function deleteById($id);
}
