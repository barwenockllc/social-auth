<?php

namespace Barwenock\SocialAuth\Controller\Google;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Session\Generic;
use Magento\Store\Model\Store;
use Magento\Framework\Url;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Webkul\SocialSignup\Helper\Data;

/**
 * Connect class of google
 */
class Connect implements \Magento\Framework\App\ActionInterface
{
    /**
     * @var isRegistor
     */
    protected $isRegistor;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var Store
     */
    private $store;

    /**
     * @var \Magento\Framework\Session\Generic
     */
    private $session;

    /**
     * @var Url
     */
    protected $url;

    /**
     * @var Attribute
     */
    private $eavAttribute;

    /**
     * @var Google
     */
    private $helperGoogle;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * Construct intialization
     *
     * @param Data $helper
     * @param Generic $session
     * @param Context $context
     * @param Store $store
     * @param \Barwenock\SocialAuth\Helper\Authorize\Google $helperGoogle
     * @param Attribute $eavAttribute
     * @param GoogleClient $googleClient
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Data $helper,
        Generic $session,
        Context $context,
        Store $store,
        \Barwenock\SocialAuth\Helper\Authorize\Google $helperGoogle,
        Attribute $eavAttribute,
        \Barwenock\SocialAuth\Controller\Google\GoogleClient $googleClient,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Customer\Model\Session $customerSession,
        PageFactory $resultPageFactory,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\Message\ManagerInterface $massageManager,
        \Barwenock\SocialAuth\Helper\CacheManagement $cacheManagement,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\UrlInterface $url,
        \Barwenock\SocialAuth\Model\Customer\Create $socialAuthCustomer
    ) {
        $this->isRegistor = true;
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        $this->helperGoogle = $helperGoogle;
        $this->eavAttribute = $eavAttribute;
        $this->store = $store;
        $this->_scopeConfig = $scopeConfig;
        $this->session = $session;
        $this->coreSession = $coreSession;
        $this->googleClient = $googleClient;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultFactory = $resultFactory;
        $this->messageManager = $massageManager;
        $this->cacheManagment = $cacheManagement;
        $this->requset = $request;
        $this->url = $url;
        $this->socialAuthCustomer = $socialAuthCustomer;
    }

    public function execute()
    {
        $this->googleClient->setParameters();
        $this->cacheManagment->cleanCache();

        try {
            $isSecure = $this->store->isCurrentlySecure();
            $isCheckoutPageReq = $this->coreSession->getIsSocialSignupCheckoutPageReq();

            $redirectPath = $this->googleConnect();
            if ($redirectPath) {
                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath($redirectPath);
            }
        } catch (\Exception $e) {
            $message = $isCheckoutPageReq ? 'errorMsg' : 'addError';
            $this->messageManager->$message($e->getMessage());
        }

        if (!empty($this->referer) && empty($this->flag)) {
            $redirectUrl = $this->url->getUrl('socialsignup/google/redirect/');
            if (!$isSecure) {
                $redirectUrl = str_replace("https://", "http://", $redirectUrl);
            }
            $this->coreSession->start();
            $this->coreSession->setIsRegistor($this->isRegistor);

            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setPath($redirectUrl);
        }

        $this->helper->redirect404($this);
    }

    /**
     * Get google account
     */
    protected function googleConnect()
    {
        $isCheckoutPageReq = 0;
        $isCheckoutPageReq = $this->helper->getCoreSession()->getIsSocialSignupCheckoutPageReq();
        $errorCode = $this->requset->getParam('error');
        $code = $this->requset->getParam('code');
        $state = $this->requset->getParam('state');

        if (!$this->isRequestValid($errorCode, $code, $state)) {
            return;
        }

        if ($code) {
            $attributeCodes = ['socialauth_google_id', 'socialauth_google_token'];
            foreach ($attributeCodes as $attributeCode) {
                $attributeId = $this->eavAttribute->getIdByCode('customer', $attributeCode);
                if ($attributeId === false) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Attribute %1 does not exist', $attributeCode)
                    );
                }
            }

            // Google API green light - proceed
            $userInfo = $this->googleClient->api('/userinfo');
            $token = $this->googleClient->getAccessToken();

            $customersByGoogleId = $this->helperGoogle->getCustomersByGoogleId($userInfo->id);

            $this->connectExistingAccount($customersByGoogleId, $userInfo, $token);

            if ($this->_checkAccountByGoogleId($customersByGoogleId)) {
                return;
            }

            $customersByEmail = $this->helperGoogle->getCustomersByEmail($userInfo->email);

            if ($customersByEmail->getTotalCount()) {
                // Email account already exists - attach, login
                $this->helperGoogle->connectByGoogleId($customersByEmail, $userInfo->id, $token);

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

            // New connection - create, attach, login
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
                if ($this->helper->getCustomerAttributes()) {
                    $customerData = [
                        'firstname' => $userInfo->given_name,
                        'lastname' => $userInfo->family_name,
                        'email' => $userInfo->email,
                        'confirmation' => null,
                        'is_active' => 1,
                        'socialauth_google_id' => $userInfo->id,
                        'socialauth_google_token'    => $token,
                        'label'     => __('google'),
                        'redirect_path' => 'socialsignup/google/redirect/'
                    ];
                    $this->helper->setInSession($customerData);
                    return 'socialsignup/index/index';
                } else {
                    $this->socialAuthCustomer->create(
                        $userInfo->email,
                        $userInfo->given_name,
                        $userInfo->family_name,
                        $userInfo->id,
                        $token
                    );
                }
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
    }

    private function isRequestValid($errorCode, $code, $state)
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
                $this->helper->closeWindow($this);
            }
            return false;
        }

        return true;
    }

    /**
     * Get active account
     *
     * @param object $customersByGoogleId
     * @param object  $userInfo
     * @param string $token
     * @return void
     */
    private function connectExistingAccount($customersByGoogleId, $userInfo, $token)
    {
        $isCheckoutPageReq = $this->helper->getCoreSession()->getIsSocialSignupCheckoutPageReq();
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

            // Connect from account dashboard - attach
            $customer = $this->customerSession->getCustomer();

            $this->helperGoogle->connectByGoogleId(
                $customer,
                $userInfo->id,
                $token
            );
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
            return;
        }
    }

    /**
     * Check customer account by google id
     *
     * @param  object $customersByGoogleId
     */
    private function _checkAccountByGoogleId($customersByGoogleId)
    {
        $isCheckoutPageReq = 0;
        $isCheckoutPageReq = $this->helper->getCoreSession()->getIsSocialSignupCheckoutPageReq();
        if ($customersByGoogleId->getTotalCount()) {
            $this->isRegistor = false;
            // Existing connected user - login
            foreach ($customersByGoogleId->getItems() as $customerInfo) {
                $customer = $customerInfo;
            }

            $this->helperGoogle->loginByCustomer($customer);

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
