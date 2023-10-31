<?php

namespace Barwenock\SocialAuth\Controller\Google;

class Authorize implements \Magento\Framework\App\ActionInterface
{
    /**
     * Connect social media type
     */
    protected const CONNECT_TYPE = 'google';

    protected $isRegistor;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $eavAttribute;

    /**
     * @var \Magento\Store\Model\Store
     */
    protected $store;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $coreSession;

    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $session;

    /**
     * @var \Barwenock\SocialAuth\Service\Authorize\Google
     */
    protected $googleService;

    /**
     * @var \Barwenock\SocialAuth\Helper\CacheManagement
     */
    protected $cacheManagement;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\Url
     */
    protected $url;

    /**
     * @var \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer
     */
    protected $socialCustomerHelper;

    /**
     * @var \Barwenock\SocialAuth\Model\Customer\Create
     */
    protected $socialCustomerCreate;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * @param \Magento\Framework\Session\Generic $session
     * @param \Magento\Store\Model\Store $store
     * @param \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
     * @param \Barwenock\SocialAuth\Service\Authorize\Google $googleService
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     * @param \Magento\Framework\Message\ManagerInterface $massageManager
     * @param \Barwenock\SocialAuth\Helper\CacheManagement $cacheManagement
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\UrlInterface $url
     * @param \Barwenock\SocialAuth\Model\Customer\Create $socialCustomerCreate
     * @param \Magento\Framework\App\Response\Http $redirect
     */
    public function __construct(
        \Magento\Framework\Session\Generic $session,
        \Magento\Store\Model\Store $store,
        \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Barwenock\SocialAuth\Service\Authorize\Google $googleService,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\Message\ManagerInterface $massageManager,
        \Barwenock\SocialAuth\Helper\CacheManagement $cacheManagement,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\UrlInterface $url,
        \Barwenock\SocialAuth\Model\Customer\Create $socialCustomerCreate
    ) {
        $this->isRegistor = true;
        $this->customerSession = $customerSession;
        $this->socialCustomerHelper = $socialCustomerHelper;
        $this->eavAttribute = $eavAttribute;
        $this->store = $store;
        $this->session = $session;
        $this->coreSession = $coreSession;
        $this->googleService = $googleService;
        $this->resultFactory = $resultFactory;
        $this->messageManager = $massageManager;
        $this->cacheManagement = $cacheManagement;
        $this->request = $request;
        $this->url = $url;
        $this->socialCustomerCreate = $socialCustomerCreate;
    }

    public function execute()
    {
        $this->googleService->setParameters();
        $this->cacheManagement->cleanCache();

        try {
            $isSecure = $this->store->isCurrentlySecure();
            $isCheckoutPageReq = $this->coreSession->getIsSocialSignupCheckoutPageReq();

            $this->googleConnect();
        } catch (\Exception $e) {
            if (!$isCheckoutPageReq) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } else {
                $this->coreSession->setErrorMsg($e->getMessage());
            }
        }

        $redirectUrl = $this->url->getUrl('socialauth/authorize/redirect/');

        if (empty($this->referer)) {
            $redirectUrl .= '?' . http_build_query(['noroute' => true]);
        }

        if (!$isSecure) {
            $redirectUrl = str_replace("https://", "http://", $redirectUrl);
        }

        return $this->resultFactory->create(
            \Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT
        )->setPath($redirectUrl);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function googleConnect()
    {
        $isCheckoutPageReq = $this->coreSession->getIsSocialSignupCheckoutPageReq();
        $errorCode = $this->request->getParam('error');
        $code = $this->request->getParam('code');
        $state = $this->request->getParam('state');

        if (!$this->isRequestValid($errorCode, $code, $state)) {
            return;
        }

        $attributeCodes = ['socialauth_google_id', 'socialauth_google_token'];
        foreach ($attributeCodes as $attributeCode) {
            $attributeId = $this->eavAttribute->getIdByCode('customer', $attributeCode);
            if (!$attributeId) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Attribute %1 does not exist', $attributeCode)
                );
            }
        }

            $userInfo = $this->googleService->api('/userinfo');
            $token = $this->googleService->getAccessToken();

            $customersByGoogleId = $this->socialCustomerHelper
                ->getCustomersBySocialId($userInfo->id, self::CONNECT_TYPE);

            $this->connectExistingAccount($customersByGoogleId, $userInfo, $token);

        if ($this->checkAccountByGoogleId($customersByGoogleId)) {
            return;
        }

            $customersByEmail = $this->socialCustomerHelper->getCustomersByEmail($userInfo->email);

        if ($customersByEmail->getTotalCount()) {
            $this->socialCustomerHelper
                ->connectBySocialId($customersByEmail, $userInfo->id, $token, self::CONNECT_TYPE);

            if (!$isCheckoutPageReq) {
                $this->messageManager->addSuccessMessage(
                    __(
                        'We have discovered you already have an account at our store.'
                        .' Your %1 account is now connected to your store account.',
                        __('Google')
                    )
                );
            } else {
                $this->coreSession->setSuccessMsg(__(
                    'We have discovered you already have an account at our store.'
                    .' Your %1 account is now connected to your store account.',
                    __('Google')
                ));
            }
            return;
        }

        if (empty($userInfo->given_name)) {
            if (!$isCheckoutPageReq) {
                $this->messageManager->addErrorMessage(
                    __('Sorry, could not retrieve your %1 first name. Please try again.', __('Google'))
                );
            } else {
                $this->coreSession->setErrorMsg(
                    __('Sorry, could not retrieve your %1 first name. Please try again.', __('Google'))
                );
            }
        }

        if (empty($userInfo->family_name)) {
            if (!$isCheckoutPageReq) {
                $this->messageManager->addErrorMessage(
                    __('Sorry, could not retrieve your %2 last name. Please try again.', __('Google'))
                );
            } else {
                $this->coreSession->setErrorMsg(
                    __('Sorry, could not retrieve your %1 last name. Please try again.', __('Google'))
                );
            }
        }
            $customersCountByGoogleId = $customersByGoogleId->getTotalCount();
            $customersCountByEmail = $customersByEmail->getTotalCount();

        if (!$customersCountByGoogleId && !$customersCountByEmail) {
             $this->socialCustomerCreate->create(
                 $userInfo->email,
                 $userInfo->given_name,
                 $userInfo->family_name,
                 $userInfo->id,
                 $token,
                 self::CONNECT_TYPE
             );
        }

        if (!$isCheckoutPageReq) {
            $this->messageManager->addSuccess(
                __(
                    'Your %1 account is now connected to your new user account at our store.'
                    .' Now you can login using our %1 Connect button or using store account credentials'
                    .' you will receive to your email address.',
                    __('Google')
                )
            );
        } else {
            $this->coreSession->setSuccessMsg(__(
                'Your %1 account is now connected to your new user account at our store.'
                .' Now you can login using our %1 Connect button or using store account credentials'
                .' you will receive to your email address.',
                __('Google')
            ));
        }
    }

    /**
     * @param $errorCode
     * @param $code
     * @param $state
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function isRequestValid($errorCode, $code, $state)
    {
        if (!($errorCode || $code) && !$state) {
            // Direct route access - deny
            return false;
        }

        $this->referer = $this->url->getCurrentUrl();

        if (!$state || $state != $this->session->getGoogleCsrf()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Unable to find Google Csrf Code')
            );
        }

        if ($errorCode) {
            if ($errorCode === 'access_denied') {
                unset($this->referer);
                $this->flag = "noaccess";
                return false;
            }
            return false;
        }

        return true;
    }

    /**
     * @param $customersByGoogleId
     * @param $userInfo
     * @param $token
     * @return void
     * @throws \Exception
     */
    protected function connectExistingAccount($customersByGoogleId, $userInfo, $token)
    {
        $isCheckoutPageReq = $this->coreSession->getIsSocialSignupCheckoutPageReq();
        if ($this->customerSession->isLoggedIn()) {
            // Logged in user
            if ($customersByGoogleId->getTotalCount()) {
                // Google account already connected to other account - deny
                if (!$isCheckoutPageReq) {
                    $this->messageManager->addNoticeMessage(__(
                        'Your %1 account is already connected to one of our store accounts.',
                        __(
                            'Google'
                        )
                    ));
                } else {
                    $this->coreSession->setSuccessMsg(
                        __(
                            'Your %1 account is already connected to one of our store accounts.',
                            __(
                                'Google'
                            )
                        )
                    );
                }
                return;
            }

            $this->socialCustomerHelper
                ->connectBySocialId($customersByGoogleId, $userInfo->id, $token, self::CONNECT_TYPE);
            if (!$isCheckoutPageReq) {
                $this->messageManager->addSuccessMessage(
                    __(
                        'Your %1 account is now connected to your store account.'
                        .' You can now login using our %1 Connect button or using store account credentials'
                        .' you will receive to your email address.',
                        __('Google')
                    )
                );
            } else {
                $this->coreSession->setSuccessMsg(
                    __(
                        'Your %1 account is now connected to your store account.'
                        .' You can now login using our %1 Connect button or using store account credentials'
                        .' you will receive to your email address.',
                        __('Google')
                    )
                );
            }
        }
    }

    /**
     * @param $customersByGoogleId
     * @return bool
     * @throws \Exception
     */
    protected function checkAccountByGoogleId($customersByGoogleId)
    {
        $isCheckoutPageReq = $this->coreSession->getIsSocialSignupCheckoutPageReq();
        if ($customersByGoogleId->getTotalCount()) {
            $this->isRegistor = false;

            foreach ($customersByGoogleId->getItems() as $customerInfo) {
                $customer = $customerInfo;
            }

            $this->socialCustomerHelper->loginByCustomer($customer);

            if (!$isCheckoutPageReq) {
                $this->messageManager
                    ->addSuccessMessage(
                        __('You have successfully logged in using your %1 account.', __('Google'))
                    );
            } else {
                $this->coreSession->setSuccessMsg(
                    __('You have successfully logged in using your %1 account.', __('Google'))
                );
            }
            return true;
        }
        return false;
    }
}
