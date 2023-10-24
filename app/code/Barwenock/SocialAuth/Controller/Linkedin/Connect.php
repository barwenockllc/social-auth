<?php

namespace Barwenock\SocialAuth\Controller\Linkedin;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Session\Generic;
use Magento\Store\Model\Store;
use Magento\Framework\Url;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use \Barwenock\SocialAuth\Helper\Authorize\Linkedin;
use Magento\Framework\Exception\LocalizedException;

class Connect extends Action
{
    protected const CONNECT_TYPE = 'linkedin';
     /**
      * @var isRegistor
      */
    protected $isRegistor;

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;
    /**
     * @var \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer
     */
    protected $socialCustomerHelper;
    /**
     * @var eavAttribute
     */
    protected $_eavAttribute;
    /**
     * @var store
     */
    protected $_store;
    /**
     * @var scopeConfig
     */
    protected $_scopeConfig;
    /**
     * @param Generic $session
     * @param Context $context
     * @param Store $store
     * @param \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper
     * @param Attribute $eavAttribute
     * @param LinkedinClient $linkedinClient
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param \Webkul\SocialSignup\Helper\Data $helper
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Generic $session,
        Context $context,
        Store $store,
        \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        LinkedinClient $linkedinClient,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Webkul\SocialSignup\Helper\Data $helper,
        PageFactory $resultPageFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\UrlInterface $url,
        \Barwenock\SocialAuth\Model\Customer\Create $customerCreate
    ) {
        $this->isRegistor = true;
        $this->customerSession = $customerSession;
        $this->socialCustomerHelper = $socialCustomerHelper;
        $this->eavAttribute = $eavAttribute;
        $this->store = $store;
        $this->_scopeConfig = $scopeConfig;
        $this->session = $session;
        $this->helper = $helper;
        $this->coreSession = $coreSession;
        $this->linkedinClient = $linkedinClient;
        $this->_resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->url = $url;
        $this->customerCreate = $customerCreate;
        parent::__construct($context);
    }

    /**
     * Execute function
     */
    public function execute()
    {
        try {
            $isCheckoutPageReq = $this->helper->getCoreSession()->getIsSocialSignupCheckoutPageReq();
            $this->linkedinClient->setParameters();
            $isSecure = $this->store->isCurrentlySecure();
            $redirectPath = $this->linkedinConnect();
            if ($redirectPath) {
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath($redirectPath);
            }
        } catch (\Exception $e) {
            if (!$isCheckoutPageReq) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } else {
                $this->coreSession->setErrorMsg(
                    $e->getMessage()
                );
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
     * Check customer is exist or not
     */
    protected function linkedinConnect()
    {
        $isCheckoutPageReq = $this->helper->getCoreSession()->getIsSocialSignupCheckoutPageReq();
        $errorCode = $this->request->getParam('error');
        $code = $this->request->getParam('code');
        $state = $this->request->getParam('state');

        if (!$this->isRequestValid($errorCode, $code, $state)) {
            return;
        }

        if ($code) {
            $attributeCodes = ['socialauth_linkedin_id', 'socialauth_linkedin_token'];
            foreach ($attributeCodes as $attributeCode) {
                $attributeId = $this->eavAttribute->getIdByCode('customer', $attributeCode);
                if (!$attributeId) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Attribute %1 does not exist', $attributeCode)
                    );
                }
            }

            $userInfo = $this->linkedinClient->api('/v2/userinfo');
            $token = $this->linkedinClient->getAccessToken();

            $customersByLinkedinId = $this->socialCustomerHelper
                ->getCustomersBySocialId($userInfo->sub, self::CONNECT_TYPE);

            $this->connectExistingAccount($customersByLinkedinId, $userInfo, $token);

            if ($this->checkAccountByLinkedinId($customersByLinkedinId)) {
                return;
            }

            $customersByEmail = $this->socialCustomerHelper->getCustomersByEmail($userInfo->email);

            if ($customersByEmail->getTotalCount()) {
                $this->socialCustomerHelper
                    ->connectBySocialId($customersByEmail, $userInfo->sub, $token, self::CONNECT_TYPE);

                if (!$isCheckoutPageReq) {
                    $this->messageManager->addSuccessMessage(
                        __(
                            'We have discovered you already have an account at our store.'
                            .' Your %1 account is now connected to your store account.',
                            __('LinkedIn')
                        )
                    );
                } else {
                    $this->coreSession->setSuccessMsg(__(
                        'We have discovered you already have an account at our store.'.
                        ' Your %1 account is now connected to your store account.',
                        __('LinkedIn')
                    ));
                }
                return;
            }

            if (empty($userInfo->given_name)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Sorry, could not retrieve your %1 first name. Please try again.', __('LinkedIn'))
                );
            }

            if (empty($userInfo->family_name)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Sorry, could not retrieve your %1 last name. Please try again.', __('LinkedIn'))
                );
            }
            $customerCountByLinkedinId = $customersByLinkedinId->getTotalCount();
            $customerCountByEmail = $customersByEmail->getTotalCount();

            if (!$customerCountByLinkedinId && !$customerCountByEmail) {
                    $this->customerCreate->create(
                        $userInfo->email,
                        $userInfo->given_name,
                        $userInfo->family_name,
                        $userInfo->sub,
                        $token,
                        self::CONNECT_TYPE
                    );
            }

            if (!$isCheckoutPageReq) {
                $this->messageManager->addSuccessMessage(
                    __(
                        'Your %1 account is now connected to your new user account at our store.'.
                        ' Now you can login using our %1 Connect button or using store account credentials'
                        .' you will receive to your email address.',
                        __('LinkedIn')
                    )
                );
            } else {
                $this->coreSession->setSuccessMsg(
                    __(
                        'Your %1 account is now connected to your new user account at our store.'
                        .' Now you can login using our %1 Connect button or using store account credentials'
                        .' you will receive to your email address.',
                        __('LinkedIn')
                    )
                );
            }
        }
    }

    protected function isRequestValid($errorCode, $code, $state)
    {
        if (!($errorCode || $code) && !$state) {
            // Direct route access - deny
            return false;
        }

        $this->referer = $this->url->getCurrentUrl();

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
     * Connection message
     *
     * @param object $customersByLinkedinId get customer by linkedin id
     * @param object  $userInfo              user information
     * @param string $token                 user token
     */
    private function connectExistingAccount($customersByLinkedinId, $userInfo, $token)
    {
        $isCheckoutPageReq = $this->helper->getCoreSession()->getIsSocialSignupCheckoutPageReq();
        if ($this->customerSession->isLoggedIn()) {
            if ($customersByLinkedinId->getTotalCount()) {
                if (!$isCheckoutPageReq) {
                    $this->messageManager
                        ->addNoticeMessage(
                            __(
                                'Your %1 account is already connected to one of our store accounts.',
                                __('LinkedIn')
                            )
                        );
                } else {
                    $this->coreSession->setSuccessMsg(
                        __('Your %1 account is already connected to one of our store accounts.', __('LinkedIn'))
                    );
                }
                return;
            }

            $this->socialCustomerHelper
                ->connectBySocialId($customersByLinkedinId, $userInfo->sub, $token, self::CONNECT_TYPE);
            if (!$isCheckoutPageReq) {
                $this->messageManager->addSuccessMessage(
                    __(
                        'Your %1 account is now connected to your store account.'
                        .' You can now login using our %1 Connect button or using store account credentials'
                        .' you will receive to your email address.',
                        __(
                            'Linkedin'
                        )
                    )
                );
            } else {
                $this->coreSession->setSuccessMsg(
                    __(
                        'Your %1 account is now connected to your store account.'.
                        ' You can now login using our %1 Connect button or using store account credentials'
                        .' you will receive to your email address.',
                        __(
                            'Linkedin'
                        )
                    )
                );
            }

            return;
        }
    }

    /**
     * @param $customersByLinkedinId
     * @return bool
     * @throws \Exception
     */
    protected function checkAccountByLinkedinId($customersByLinkedinId)
    {
        $isCheckoutPageReq = 0;
        $isCheckoutPageReq = $this->helper->getCoreSession()->getIsSocialSignupCheckoutPageReq();
        if ($customersByLinkedinId->getTotalCount()) {
            $this->isRegistor = false;
            // Existing connected user - login
            foreach ($customersByLinkedinId->getItems() as $customerInfo) {
                $customer = $customerInfo;
            }

            $this->socialCustomerHelper->loginByCustomer($customer);

            if (!$isCheckoutPageReq) {
                $this->messageManager
                    ->addSuccessMessage(
                        __('You have successfully logged in using your %1 account.', __('LinkedIn'))
                    );
            } else {
                $this->coreSession->setSuccessMsg(
                    __('You have successfully logged in using your %1 account.', __('LinkedIn'))
                );
            }
            return true;
        }
        return false;
    }
}
