<?php

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
     * @param \Magento\Customer\Model\Customer $customerModel
     * @param \Barwenock\SocialAuth\Model\LoginTypeFactory $loginTypeFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Barwenock\SocialAuth\Api\LoginTypeRepositoryInterface $loginTypeRepository
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper
     * @param \Magento\Newsletter\Model\SubscriptionManager $subscriptionManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Customer\Model\Customer $customerModel,
        \Barwenock\SocialAuth\Model\LoginTypeFactory $loginTypeFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Barwenock\SocialAuth\Api\LoginTypeRepositoryInterface $loginTypeRepository,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Magento\Newsletter\Model\SubscriptionManager $subscriptionManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->customerModel = $customerModel;
        $this->loginTypeFactory = $loginTypeFactory;
        $this->customerSession = $customerSession;
        $this->loginTypeRepository = $loginTypeRepository;
        $this->configHelper = $configHelper;
        $this->subscriptionManager = $subscriptionManager;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $email
     * @param $firstName
     * @param $lastName
     * @param $googleId
     * @param $token
     * @param $loginType
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
                ->setConfirmation(null)
                ->save();

            $customerId = $this->customerModel->getId();

            $this->loginTypeRepository->save(
                $this->loginTypeFactory->create()->setCustomerId($customerId)->setLoginType($social)
            );

            if ($this->configHelper->getSubscriptionStatus()) {
                $this->subscriptionManager->subscribeCustomer(
                    $customerId,
                    $this->storeManager->getStore()->getId()
                );
            }

            $this->customerModel->sendNewAccountEmail();

            $this->customerSession->loginById($this->customerModel->getId());
        } catch (\Exception $exception) {
            throw new \Exception(
                'Exception happened during authorization: ' . $exception->getMessage()
            );
        }
    }
}
