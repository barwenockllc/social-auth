<?php

namespace Barwenock\SocialAuth\Model;

class FacebookCustomerRepository implements \Barwenock\SocialAuth\Api\FacebookCustomerRepositoryInterface
{
    /**
     * @var \Barwenock\SocialAuth\Model\ResourceModel\FacebookCustomer\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Barwenock\SocialAuth\Model\ResourceModel\FacebookCustomer
     */
    protected $facebookCustomerResource;

    /**
     * @var \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var \Magento\Framework\Api\SearchResultsFactory
     */
    protected $searchResultsFactory;

    /**
     * @var \Barwenock\SocialAuth\Model\FacebookCustomerFactory
     */
    protected $facebookCustomerFactory;

    /**
     * @param \Barwenock\SocialAuth\Model\ResourceModel\FacebookCustomer\CollectionFactory $collectionFactory
     * @param \Barwenock\SocialAuth\Model\ResourceModel\FacebookCustomer $facebookCustomerResource
     * @param \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface $collectionProcessor
     * @param \Magento\Framework\Api\SearchResultsFactory $searchResultsFactory
     * @param \Barwenock\SocialAuth\Model\FacebookCustomerFactory $facebookCustomerFactory
     */
    public function __construct(
        \Barwenock\SocialAuth\Model\ResourceModel\FacebookCustomer\CollectionFactory $collectionFactory,
        \Barwenock\SocialAuth\Model\ResourceModel\FacebookCustomer $facebookCustomerResource,
        \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface $collectionProcessor,
        \Magento\Framework\Api\SearchResultsFactory $searchResultsFactory,
        \Barwenock\SocialAuth\Model\FacebookCustomerFactory $facebookCustomerFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->facebookCustomerResource = $facebookCustomerResource;
        $this->collectionProcessor = $collectionProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->facebookCustomerFactory = $facebookCustomerFactory;
    }

    /**
     * Get customer collection by facebook id
     *
     * @param $customerId
     * @return \Barwenock\SocialAuth\Api\Data\FacebookCustomerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByCustomerId($customerId)
    {
        $facebookCustomer = $this->facebookCustomerFactory->create();
        $this->facebookCustomerResource->load($facebookCustomer, $customerId, 'customer_id');
        if (!$facebookCustomer->getEntityId()) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __('Requested facebook customer doesn\'t exist')
            );
        }
        return $facebookCustomer;
    }
    /**
     * Get customer collection by facebook id
     *
     * @param $facebookId
     * @return \Barwenock\SocialAuth\Api\Data\FacebookCustomerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByFacebookId($facebookId)
    {
        $facebookCustomer = $this->facebookCustomerFactory->create();
        $this->facebookCustomerResource->load($facebookCustomer, $facebookId, 'facebook_id');
        if (!$facebookCustomer->getEntityId()) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __('Requested facebook customer doesn\'t exist')
            );
        }
        return $facebookCustomer;
    }

    /**
     * Get entity by id
     *
     * @param $entityId
     * @return \Barwenock\SocialAuth\Api\Data\FacebookCustomerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($entityId)
    {
        $facebookCustomer = $this->facebookCustomerFactory->create();
        $this->facebookCustomerResource->load($facebookCustomer, $entityId);
        if (!$facebookCustomer->getEntityId()) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __('Requested facebook customer doesn\'t exist')
            );
        }
        return $facebookCustomer;
    }

    /**
     * Save entity
     *
     * @inheritDoc
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Barwenock\SocialAuth\Api\Data\FacebookCustomerInterface $facebookCustomer)
    {
        try {
            $this->facebookCustomerResource->save($facebookCustomer);
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(__($exception->getMessage()));
        }
        return $facebookCustomer;
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
     * Delete entity
     *
     * @inheritDoc
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\Barwenock\SocialAuth\Api\Data\FacebookCustomerInterface $facebookCustomer)
    {
        try {
            $this->facebookCustomerResource->delete($facebookCustomer);
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * @param $entityId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById($entityId)
    {
        return $this->delete($this->getById($entityId));
    }
}
