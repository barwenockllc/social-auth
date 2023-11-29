<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Model\Customer;

class Create
{
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customerModel;

    /**
     * @var \Barwenock\SocialAuth\Model\LoginTypeFactory
     */
    protected $loginTypeFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Barwenock\SocialAuth\Api\LoginTypeRepositoryInterface
     */
    protected $loginTypeRepository;

    /**
     * @var \Barwenock\SocialAuth\Helper\Adminhtml\Config
     */
    protected $configHelper;

    /**
     * @var \Magento\Newsletter\Model\SubscriptionManager
     */
    protected $subscriptionManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    protected $resourceCustomer;

    /**
     * @param \Magento\Customer\Model\Customer $customerModel
     * @param \Barwenock\SocialAuth\Model\LoginTypeFactory $loginTypeFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Barwenock\SocialAuth\Api\LoginTypeRepositoryInterface $loginTypeRepository
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper
     * @param \Magento\Newsletter\Model\SubscriptionManager $subscriptionManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
     */
    public function __construct(
        \Magento\Customer\Model\Customer $customerModel,
        \Barwenock\SocialAuth\Model\LoginTypeFactory $loginTypeFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Barwenock\SocialAuth\Api\LoginTypeRepositoryInterface $loginTypeRepository,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Magento\Newsletter\Model\SubscriptionManager $subscriptionManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
    ) {
        $this->customerModel = $customerModel;
        $this->loginTypeFactory = $loginTypeFactory;
        $this->customerSession = $customerSession;
        $this->loginTypeRepository = $loginTypeRepository;
        $this->configHelper = $configHelper;
        $this->subscriptionManager = $subscriptionManager;
        $this->storeManager = $storeManager;
        $this->resourceCustomer = $resourceCustomer;
    }

    /**
     * Creates a new customer account during social authentication
     *
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param string $socialId
     * @param string $socialToken
     * @param string $social
     * @return void
     * @throws \Exception
     */
    public function create($email, $firstName, $lastName, $socialId, $socialToken, $social)
    {
        try {
            $this->customerModel
                ->setEmail($email)
                ->setFirstname($firstName)
                ->setLastname($lastName)
                ->setData('socialauth_' . $social . '_id', $socialId)
                ->setData('socialauth_' . $social . '_token', $socialToken)
                ->setConfirmation(null);

            $this->resourceCustomer->save($this->customerModel);

            $customerId = $this->customerModel->getId();

            $this->loginTypeRepository->save(
                $this->loginTypeFactory->create()->setCustomerId($customerId)->setLoginType($social)
            );

            if ($this->configHelper->getSubscriptionStatus()) {
                $this->subscriptionManager->subscribeCustomer(
                    (int) $customerId,
                    (int) $this->storeManager->getStore()->getId()
                );
            }

            $this->customerModel->sendNewAccountEmail();

            $this->customerSession->loginById($customerId);
        } catch (\Magento\Framework\Exception\LocalizedException $localizedException) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Exception happened during authorization: ' . $localizedException->getMessage()),
                $localizedException->getCode(),
                $localizedException
            );
        }
    }
}
