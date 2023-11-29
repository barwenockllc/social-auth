<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Model;

class LoginTypeRepository implements \Barwenock\SocialAuth\Api\LoginTypeRepositoryInterface
{
    /**
     * @var \Barwenock\SocialAuth\Model\ResourceModel\LoginType\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Barwenock\SocialAuth\Model\ResourceModel\LoginType
     */
    protected $loginTypeResource;

    /**
     * @var \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var \Magento\Framework\Api\SearchResultsFactory
     */
    protected $searchResultsFactory;

    /**
     * @var \Barwenock\SocialAuth\Model\LoginTypeFactory
     */
    protected $loginTypeFactory;

    /**
     * @param \Barwenock\SocialAuth\Model\ResourceModel\LoginType\CollectionFactory $collectionFactory
     * @param \Barwenock\SocialAuth\Model\ResourceModel\LoginType $loginTypeResource
     * @param \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface $collectionProcessor
     * @param \Magento\Framework\Api\SearchResultsFactory $searchResultsFactory
     * @param \Barwenock\SocialAuth\Model\LoginTypeFactory $loginTypeFactory
     */
    public function __construct(
        \Barwenock\SocialAuth\Model\ResourceModel\LoginType\CollectionFactory $collectionFactory,
        \Barwenock\SocialAuth\Model\ResourceModel\LoginType $loginTypeResource,
        \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface $collectionProcessor,
        \Magento\Framework\Api\SearchResultsFactory $searchResultsFactory,
        \Barwenock\SocialAuth\Model\LoginTypeFactory $loginTypeFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->loginTypeResource = $loginTypeResource;
        $this->collectionProcessor = $collectionProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->loginTypeFactory = $loginTypeFactory;
    }

    /**
     * Get entity by id
     *
     * @param int $entityId
     * @return \Barwenock\SocialAuth\Api\Data\LoginTypeInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($entityId)
    {
        $loginType = $this->loginTypeFactory->create();
        $this->loginTypeResource->load($loginType, $entityId);
        if ($loginType->getEntityId() === 0) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __("Requested login type doesn't exist")
            );
        }

        return $loginType;
    }

    /**
     * Get by customer id
     *
     * @param int $customerId
     * @return LoginType
     */
    public function getByCustomerId($customerId)
    {
        $loginType = $this->loginTypeFactory->create();
        $this->loginTypeResource->load($loginType, $customerId, 'customer_id');

        return $loginType;
    }

    /**
     * @inheritDoc
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Barwenock\SocialAuth\Api\Data\LoginTypeInterface $loginType)
    {
        try {
            $this->loginTypeResource->save($loginType);
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(__($exception->getMessage()));
        }

        return $loginType;
    }

    /**
     * Get list of items by search criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResults
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritDoc
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\Barwenock\SocialAuth\Api\Data\LoginTypeInterface $loginType): bool
    {
        try {
            $this->loginTypeResource->delete($loginType);
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * Delete by entity id
     *
     * @param int $entityId
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById($entityId)
    {
        return $this->delete($this->getById($entityId));
    }
}
