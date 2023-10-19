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
     /**
      * @var isRegistor
      */
    protected $isRegistor;

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;
    /**
     * @var helperLinkedin
     */
    protected $_helperLinkedin;
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
     * @param Linkedin $helperLinkedin
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
        Linkedin $helperLinkedin,
        Attribute $eavAttribute,
        LinkedinClient $linkedinClient,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Webkul\SocialSignup\Helper\Data $helper,
        PageFactory $resultPageFactory,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->isRegistor = true;
        $this->customerSession = $customerSession;
        $this->_helperLinkedin = $helperLinkedin;
        $this->_eavAttribute = $eavAttribute;
        $this->store = $store;
        $this->_scopeConfig = $scopeConfig;
        $this->_session = $session;
        $this->helper = $helper;
        $this->coreSession = $coreSession;
        $this->linkedinClient = $linkedinClient;
        $this->_resultPageFactory = $resultPageFactory;
        $this->request = $request;
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

        if (!($errorCode || $code) && !$state) {
            return;
        }
        $this->referer = $this->_url->getCurrentUrl();

        if ($errorCode) {
            if ($errorCode === 'access_denied') {
                unset($this->referer);
                $this->flag = "noaccess";
                $this->helper->closeWindow($this);
            }
            return;
        }
        if ($code) {
            $attributegId = $this->_eavAttribute->getIdByCode('customer', 'socialsignup_lid');
            $attributegtoken = $this->_eavAttribute->getIdByCode('customer', 'socialsignup_ltoken');
            if ($attributegId == false || $attributegtoken == false) {
                throw new  \Magento\Framework\Exception\LocalizedException(
                    __('Attribute `socialsignup_lid` or `socialsignup_ltoken` not exist')
                );
            }
            $token = $this->linkedinClient->getAccessToken();

            $userInfo = $this->linkedinClient->api('/v2/userinfo');

            $customersByLinkedinId = $this->_helperLinkedin
                ->getCustomersByLinkedinId($userInfo->sub);

            $this->_connectWithCurrentCustomer($customersByLinkedinId, $userInfo, $token);

            if ($customersByLinkedinId->count()) {
                $this->isRegistor = false;
                foreach ($customersByLinkedinId as $key => $customerInfo) {
                    $customer = $customerInfo;
                }
                $this->_helperLinkedin->loginByCustomer($customer);
                if (!$isCheckoutPageReq) {
                    $this->messageManager
                        ->addSuccess(
                            __('You have successfully logged in using your %1 account.', __('LinkedIn'))
                        );
                } else {
                    $this->coreSession->setSuccessMsg(
                        __('You have successfully logged in using your %1 account.', __('LinkedIn'))
                    );
                }
                return;
            }

            $customersByEmail = $this->_helperLinkedin
                ->getCustomersByEmail($userInfo->email);

            if ($customersByEmail->count()) {
                $this->_helperLinkedin->connectByLinkedinId(
                    $customersByEmail,
                    $userInfo->sub,
                    $token
                );
                if (!$isCheckoutPageReq) {
                    $this->messageManager->addSuccess(
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
            $customerCountByLinkedinId = $customersByLinkedinId->getSize();
            $customerCountByEmail = $customersByEmail->getSize();

            if (!$customerCountByLinkedinId && !$customerCountByEmail) {
                if ($this->helper->getCustomerAttributes()) {
                    $customerData = [
                        'firstname' => $userInfo['localizedFirstName'],
                        'lastname'  => $userInfo['localizedLastName'],
                        'email'     => $userInfo['emailAddress'],
                        'confirmation'  => null,
                        'is_active' => 1,
                        'socialsignup_lid' => $userInfo['id'],
                        'socialsignup_ltoken'    => $token,
                        'label'     => __('linkedIn'),
                        'redirect_path' => 'socialsignup/linkedin/redirect/'
                    ];
                    $this->helper->setInSession($customerData);
                    return 'socialsignup/index/index';
                } else {
                    $this->_helperLinkedin->connectByCreatingAccount(
                        $userInfo->email,
                        $userInfo->given_name,
                        $userInfo->family_name,
                        $userInfo->sub,
                        $token
                    );
                }
            }

            if (!$isCheckoutPageReq) {
                $this->messageManager->addSuccess(
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

    /**
     * Connection message
     *
     * @param object $customersByLinkedinId get customer by linkedin id
     * @param array  $userInfo              user information
     * @param string $token                 user token
     */
    private function _connectWithCurrentCustomer($customersByLinkedinId, $userInfo, $token)
    {
        $isCheckoutPageReq = 0;
        $isCheckoutPageReq = $this->helper->getCoreSession()->getIsSocialSignupCheckoutPageReq();
        if ($this->customerSession->isLoggedIn()) {
            if ($customersByLinkedinId->count()) {
                if (!$isCheckoutPageReq) {
                    $this->messageManager
                        ->addNotice(
                            __('Your %1 account is already connected to one of our store accounts.', __('LinkedIn'))
                        );
                } else {
                    $this->coreSession->setSuccessMsg(
                        __('Your %1 account is already connected to one of our store accounts.', __('LinkedIn'))
                    );
                }
                return;
            }

            $customer = $this->customerSession->getCustomer();

            $this->_helperLinkedin->connectByLinkedinId(
                $customer,
                $userInfo['sub'],
                $token
            );
            if (!$isCheckoutPageReq) {
                $this->messageManager->addSuccess(
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
}
