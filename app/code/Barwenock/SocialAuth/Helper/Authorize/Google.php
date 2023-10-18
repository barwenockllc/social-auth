<?php

namespace Barwenock\SocialAuth\Helper\Authorize;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

class Google extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $_storeManager;

    /**
     * @param Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param Customer $customerModel
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->customerSession = $customerSession;
        $this->_customerModel = $customerModel;
        $this->_storeManager = $storeManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        parent::__construct($context);
    }

    /**
     * @param $customerData
     * @param $googleId
     * @param $token
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    public function connectByGoogleId($customerData, $googleId, $token)
    {
        $customers = $customerData->getItems();
        foreach ($customers as $customer) {
            $customerModel = $this->_customerModel->load($customer->getId());
            $customerModel
                ->setData('socialauth_google_id', $googleId)
                ->setData('socialauth_google_token', $token)
                ->save();

            $customerId = $customer->getId();
        }
        $this->customerSession->loginById($customerId);
    }

    /**
     * Loging by customer
     *
     * @param \Magento\Customer\Model\Data\Customer $customer customer object
     * @throws \Exception
     */
    public function loginByCustomer(\Magento\Customer\Model\Data\Customer $customer)
    {
        if ($customer->getConfirmation()) {
            $customer->setConfirmation(null);
            $this->customerRepository->save($customer);
        }

        $this->customerSession->loginById($customer->getId());
    }

    /**
     * @param $googleId
     * @return \Magento\Customer\Api\Data\CustomerSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomersByGoogleId($googleId)
    {
        $this->searchCriteriaBuilder->addFilter('socialauth_google_id', $googleId);
        if ($this->_customerModel->getSharingConfig()->isWebsiteScope()) {
            $this->searchCriteriaBuilder->addFilter(
                'website_id',
                $this->_storeManager->getStore()->getWebsiteId()
            );
        }

        if ($this->customerSession->isLoggedIn()) {
            $this->searchCriteriaBuilder->addFilter(
                'entity_id',
                $this->customerSession->getCustomerId(),
                'neq'
            );
        }

        return $this->customerRepository->getList($this->searchCriteriaBuilder->create());
    }

    /**
     * @param $email
     * @return \Magento\Customer\Api\Data\CustomerSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomersByEmail($email)
    {
        $this->searchCriteriaBuilder->addFilter('email', $email);
        if ($this->_customerModel->getSharingConfig()->isWebsiteScope()) {
            $this->searchCriteriaBuilder->addFilter(
                'website_id',
                $this->_storeManager->getStore()->getWebsiteId()
            );
        }
        if ($this->customerSession->isLoggedIn()) {
            $this->searchCriteriaBuilder->addFilter(
                'entity_id',
                $this->customerSession->getCustomerId(),
                'neq'
            );
        }
        return $this->customerRepository->getList($this->searchCriteriaBuilder->create());
    }
}
