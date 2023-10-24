<?php

namespace Barwenock\SocialAuth\Controller\Instagram;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Session\Generic;
use Magento\Store\Model\Store;
use Magento\Framework\Url;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\Exception\LocalizedException;

/**
 * Connect class of instagram
 */
class Connect extends Action
{
    protected const CONNECT_TYPE = 'instagram';

    /**
     * @var isRegistor
     */
    protected $isRegistor;

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var eavAttribute
     */
    protected $eavAttribute;

    /**
     * @var store
     */
    protected $_store;

    /**
     * @var scopeConfig
     */
    protected $_scopeConfig;

    /**
     * @var Webkul\SocialSignup\Helper\Data
     */
    protected $helper;

    /**
     * Construct intialization
     *
     * @param Generic $session
     * @param Context $context
     * @param Store $store
     * @param Attribute $eavAttribute
     * @param InstagramClient $instagramClient
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Webkul\SocialSignup\Helper\Data $helper
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Generic $session,
        Context $context,
        Store $store,
        Attribute $eavAttribute,
        \Barwenock\SocialAuth\Controller\Instagram\InstagramClient $instagramClient,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Customer\Model\Session $customerSession,
        \Webkul\SocialSignup\Helper\Data $helper,
        PageFactory $resultPageFactory,
        \Barwenock\SocialAuth\Helper\CacheManagement $cacheManagement,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Url $url,
        \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper,
        \Barwenock\SocialAuth\Model\Customer\Create $socialCustomerCreate
    ) {
        $this->isRegistor = true;
        $this->customerSession = $customerSession;
        $this->eavAttribute = $eavAttribute;
        $this->_store = $store;
        $this->helper = $helper;
        $this->_scopeConfig = $scopeConfig;
        $this->coreSession = $coreSession;
        $this->session = $session;
        $this->instagramClient = $instagramClient;
        $this->_resultPageFactory = $resultPageFactory;
        $this->cacheManagment = $cacheManagement;
        $this->requset = $request;
        $this->url = $url;
        $this->socialCustomerHelper = $socialCustomerHelper;
        $this->socialCustomerCreate = $socialCustomerCreate;
        parent::__construct($context);
    }

    /**
     * Get userinformation from api
     */
    public function execute()
    {
        $this->instagramClient->setParameters();
        $this->cacheManagment->cleanCache();

        try {
            $isSecure = $this->_store->isCurrentlySecure();
            $isCheckoutPageReq = $this->coreSession->getIsSocialSignupCheckoutPageReq();

            $redirectPath = $this->instagramConnect();
            if ($redirectPath) {
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath($redirectPath);
            }
        } catch (\Exception $e) {
            if (!$isCheckoutPageReq) {
                $this->messageManager->addError($e->getMessage());
            } else {
                $this->coreSession->setErrorMsg($e->getMessage());
            }
        }

        if (!empty($this->referer)) {
            if (empty($this->flag)) {
                $redirectUrl = $this->_url->getUrl('socialsignup/google/redirect/');
                if (!$isSecure) {
                    $redirectUrl = str_replace("https://", "http://", $redirectUrl);
                }
                $this->coreSession->start();
                $this->coreSession->setIsRegistor($this->isRegistor);
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath($redirectUrl);
            } else {
                $this->helper->closeWindow($this);
            }
        } else {
            $this->helper->redirect404($this);
        }
    }

    /**
     * Login customer
     */
    protected function instagramConnect()
    {
        $isCheckoutPageReq = $this->coreSession->getIsSocialSignupCheckoutPageReq();
        $errorCode = $this->requset->getParam('error');
        $code = $this->requset->getParam('code');
        $state = $this->requset->getParam('state');

        if (!$this->isRequestValid($errorCode, $code, $state)) {
            return;
        }

        if ($code) {
            $attributeCodes = ['socialauth_instagram_id', 'socialauth_instagram_token'];
            foreach ($attributeCodes as $attributeCode) {
                $attributeId = $this->eavAttribute->getIdByCode('customer', $attributeCode);
                if (!$attributeId) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Attribute %1 does not exist', $attributeCode)
                    );
                }
            }

            $userInfo = $this->instagramClient->api();
            $token = $this->instagramClient->getAccessToken();

            $customersByInstagramId = $this->socialCustomerHelper
                ->getCustomersBySocialId($userInfo->id, self::CONNECT_TYPE);

            $this->connectExistingAccount($customersByInstagramId, $userInfo, $token);

            if ($this->checkAccountByInstagramId($customersByInstagramId)) {
                return;
            }

            $customersByEmail = $this->socialCustomerHelper
                ->getCustomersByEmail($userInfo->username . '@instagram-user.com');

            if ($customersByEmail->getTotalCount()) {
                $this->socialCustomerHelper
                    ->connectBySocialId($customersByEmail, $userInfo->id, $token, self::CONNECT_TYPE);

                if (!$isCheckoutPageReq) {
                    $this->messageManager->addSuccessMessage(
                        __(
                            'We have discovered you already have an account at our store.'
                            .' Your %1 account is now connected to your store account.',
                            __('Instagram')
                        )
                    );
                } else {
                    $this->coreSession->setSuccessMsg(__(
                        'We have discovered you already have an account at our store.'
                        .' Your %1 account is now connected to your store account.',
                        __('Instagram')
                    ));
                }
                return;
            }

            $userInfo->full_name = (isset($userInfo->full_name))?$userInfo->full_name:$userInfo->username;
            if (empty($userInfo->full_name)) {
                throw new LocalizedException(
                    __('Sorry, could not retrieve your %1 last name. Please try again.', __('Instagram'))
                );
            }

            $customersCountByInstagramId = $customersByInstagramId->getTotalCount();
            $customerCountByEmail = $customersByEmail->getTotalCount();

            if (!$customersCountByInstagramId && !$customerCountByEmail) {
                try {
                    $name = explode(' ', $userInfo->full_name, 2);

                    if (count($name) > 1) {
                        $firstName = $name[0];
                        $lastName = $name[1];
                    } else {
                        $firstName = $name[0];
                        $lastName = $name[0];
                    }

                    $this->socialCustomerCreate->create(
                        $userInfo->username . '@instagram-user.com',
                        $firstName,
                        $lastName,
                        $userInfo->id,
                        $token,
                        self::CONNECT_TYPE
                    );
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                }
            }

            if (!$isCheckoutPageReq) {
                $this->messageManager->addNoticeMessage(
                    __(
                        'Since instagram doesn\'t support third-party access to your email address,'
                        .' we were unable to send you your store account credentials.'
                        .' To be able to login using store account credentials you will need to update your'
                        .' email address and password using  Edit Account Information.'
                    )
                );
            } else {
                $this->coreSession->setSuccessMsg(__(
                    'Since instagram doesn\'t support third-party access to your email address,'
                    .' we were unable to send you your store account credentials.'
                    .' To be able to login using store account credentials you will need to update your'
                    .' email address and password using  Edit Account Information.'
                ));
            }
        }
    }

    /**
     * Connect Customer By InstagramId
     *
     * @param mixed $customersByInstagramId
     * @param mixed $userInfo
     * @param mixed $token
     * @return void
     */
    public function connectExistingAccount($customersByInstagramId, $userInfo, $token)
    {
        $isCheckoutPageReq = $this->helper->getCoreSession()->getIsSocialSignupCheckoutPageReq();
        if ($this->customerSession->isLoggedIn()) {
            // Logged in user
            if ($customersByInstagramId->getTotalCount()) {
                // Instagram account already connected to other account - deny
                if (!$isCheckoutPageReq) {
                    $this->messageManager->addNoticeMessage(__(
                        'Your %1 account is already connected to one of our store accounts.',
                        __(
                            'Instagram'
                        )
                    ));
                } else {
                    $this->coreSession->setSuccessMsg(
                        __('Your %1 account is already connected to one of our store accounts.', __('Instagram'))
                    );
                }
                return;
            }

            $this->socialCustomerHelper
                ->connectBySocialId($customersByInstagramId, $userInfo->id, $token, self::CONNECT_TYPE);
            if (!$isCheckoutPageReq) {
                $this->messageManager->addSuccessMessage(
                    __(
                        'Your %1 account is now connected to your store account.'
                        .' You can now login using our %1 Connect button or using store account credentials'
                        .' you will receive to your email address.',
                        __('Instagram')
                    )
                );
            } else {
                $this->coreSession->setSuccessMsg(__(
                    'Your %1 account is now connected to your store account.'
                    .' You can now login using our %1 Connect button or using store account credentials'
                    .' you will receive to your email address.',
                    __('Instagram')
                ));
            }
        }
    }
    /**
     * Check Account By Instagram Id
     *
     * @param mixed $customersByInstagramId
     * @return bool
     */
    public function checkAccountByInstagramId($customersByInstagramId)
    {
        $isCheckoutPageReq = $this->helper->getCoreSession()->getIsSocialSignupCheckoutPageReq();
        if ($customersByInstagramId->getTotalCount()) {
            $this->isRegistor = false;
            // Existing connected user - login
            foreach ($customersByInstagramId->getItems() as $customerInfo) {
                $customer = $customerInfo;
            }

            $this->socialCustomerHelper->loginByCustomer($customer);

            if (!$isCheckoutPageReq) {
                $this->messageManager
                    ->addSuccessMessage(
                        __('You have successfully logged in using your %1 account.', __('Instagram'))
                    );
            } else {
                $this->coreSession->setSuccessMsg(
                    __('You have successfully logged in using your %1 account.', __('Instagram'))
                );
            }
            return true;
        }
        return false;
    }

    protected function isRequestValid($errorCode, $code, $state)
    {
        if (!($errorCode || $code) && !$state) {
            return false;
        }

        $this->referer = $this->url->getCurrentUrl();

        if (!$state || $state != $this->session->getInstagramCsrf()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Unable to find Instagram Csrf Code')
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
}
