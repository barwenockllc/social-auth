<?php

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
     * Save
     *
     * @param \Barwenock\SocialAuth\Model\LoginType $subject
     * @return void
     */
    public function save(\Barwenock\SocialAuth\Model\LoginType $subject);

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
