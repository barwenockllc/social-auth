<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Helper\Authorize;

class SocialCustomer extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->customerSession = $customerSession;
        $this->customer = $customer;
        $this->storeManager = $storeManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        parent::__construct($context);
    }

    /**
     *  Connects the customer by updating social authentication data
     *
     * @param object $customerData
     * @param string $socialId
     * @param string $socialToken
     * @param string $social
     * @return void
     * @throws \Exception
     */
    public function connectBySocialId($customerData, $socialId, $socialToken, $social)
    {
        $customers = $customerData->getItems();
        foreach ($customers as $customer) {
            $customer = $this->customer->load($customer->getId());
            $customer
                ->setData('socialauth_' . $social . '_id', $socialId)
                ->setData('socialauth_' . $social . '_token', $socialToken)
                ->save();

            $customerId = $customer->getId();
        }

        $this->customerSession->loginById($customerId);
    }

    /**
     *  Logs in a customer based on the provided customer data
     *
     * @param \Magento\Customer\Model\Data\Customer $customer
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
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
     * Retrieves a list of customers based on the provided social ID and attribute name
     *
     * @param string $socialId
     * @param string $attributeName
     * @return \Magento\Customer\Api\Data\CustomerSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomersBySocialId($socialId, $attributeName)
    {
        $this->searchCriteriaBuilder->addFilter('socialauth_' . $attributeName . '_id', $socialId);

        if ($this->customer->getSharingConfig()->isWebsiteScope()) {
            $this->searchCriteriaBuilder->addFilter(
                'website_id',
                $this->storeManager->getStore()->getWebsiteId()
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
     * Retrieves a customer based on the provided email address.
     *
     * @param string $email
     * @return \Magento\Customer\Api\Data\CustomerSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomersByEmail($email)
    {
        try {
            $this->searchCriteriaBuilder->addFilter('email', $email);
            if ($this->customer->getSharingConfig()->isWebsiteScope()) {
                $this->searchCriteriaBuilder->addFilter(
                    'website_id',
                    $this->storeManager->getStore()->getWebsiteId()
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
        } catch (\Magento\Framework\Exception\LocalizedException $localizedException) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($localizedException->getMessage()),
                $localizedException->getCode(),
                $localizedException
            );
        }
    }
}
