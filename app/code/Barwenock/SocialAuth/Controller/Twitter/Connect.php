<?php

namespace Barwenock\SocialAuth\Controller\Twitter;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Session\Generic;
use Magento\Store\Model\Store;
use Magento\Framework\Url;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Barwenock\SocialAuth\Helper\Twitter;
use Magento\Framework\Exception\LocalizedException;

/**
 * Connect class of Twitter
 */
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
     * @var Store
     */
    protected $store;

    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $_session;

    /**
     * @var Url
     */
    protected $_url;

    /**
     * @var Attribute
     */
    protected $_eavAttribute;

    /**
     * @var Google
     */
    protected $_helperTwitter;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Construct intialization
     *
     * @param Generic $session
     * @param Context $context
     * @param Store $store
     * @param Twitter $helperTwitter
     * @param Attribute $eavAttribute
     * @param TwitterClient $twitterClient
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Barwenock\SocialAuth\Helper\Data $helper
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\App\RequestInterface $httpRequest
     */
    public function __construct(
        Generic $session,
        Context $context,
        Store $store,
        Twitter $helperTwitter,
        Attribute $eavAttribute,
        TwitterClient $twitterClient,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Customer\Model\Session $customerSession,
        \Barwenock\SocialAuth\Helper\Data $helper,
        PageFactory $resultPageFactory,
        \Magento\Framework\App\RequestInterface $httpRequest,
        \Barwenock\SocialAuth\Helper\CacheManagement $cacheManagement,
        \Magento\Framework\App\Response\Http $redirect,
        \Magento\Framework\UrlInterface $url,
        \Barwenock\SocialAuth\Service\Authorize\Twitter $twitterService,
        \Barwenock\SocialAuth\Service\Authorize\TwitterSecond $twitterSecond
    ) {

        $this->isRegistor = true;
        $this->_customerSession = $customerSession;
        $this->_helperTwitter = $helperTwitter;
        $this->eavAttribute = $eavAttribute;
        $this->store = $store;
        $this->_scopeConfig = $scopeConfig;
        $this->coreSession = $coreSession;
        $this->_session = $session;
        $this->helper = $helper;
        $this->_twitterClient = $twitterClient;
        $this->_resultPageFactory = $resultPageFactory;
        $this->request = $httpRequest;
        $this->cacheManagement = $cacheManagement;
        $this->redirect = $redirect;
        $this->url = $url;
        $this->twitterService = $twitterService;
        $this->twitterSecond = $twitterSecond;
        parent::__construct($context);
    }

    /**
     * Login customer
     */
    public function execute()
    {
        $this->_twitterClient->setParameters();
        $this->cacheManagement->cleanCache();

        try {
            $isSecure = $this->store->isCurrentlySecure();
            $redirectPath = $this->twitterConnect();

            if ($redirectPath) {
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath($redirectPath);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
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
            return $this->redirect->setRedirect($this->url->getUrl('noroute'), 301);
        }
    }

    /**
     * Get the infromation from end points
     */
    protected function twitterConnect()
    {
        $isCheckoutPageReq = $this->coreSession->getIsSocialSignupCheckoutPageReq();
        $domain = $this->request->getServer('SERVER_NAME');
        $oauthToken = $this->request->getParam('oauth_token');
        $oauthVerifier = $this->request->getParam('oauth_verifier');

        if (!$this->isRequestValid($oauthToken)) {
            return;
        }

        $attributeCodes = ['socialauth_twitter_id', 'socialauth_twitter_token'];
        foreach ($attributeCodes as $attributeCode) {
            $attributeId = $this->eavAttribute->getIdByCode('customer', $attributeCode);
            if (!$attributeId) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Attribute %1 does not exist', $attributeCode)
                );
            }
        }

        $token = $this->twitterService->getAccessToken($oauthToken, $oauthVerifier);

        $userInfo = $this->twitterService->getUserInfo($token['oauth_token'], $token['oauth_token_secret']);

        if (!isset($userInfo['emaill'])) {
            $userInfo['email'] = $userInfo['screen_name'].'@'.$domain;
        }
        $customersByTwitterId = $this->_helperTwitter
            ->getCustomersByTwitterId($userInfo['id']);

        $this->_connectWithLoggedInCustomer($customersByTwitterId, $userInfo, $token['oauth_token']);

        if ($customersByTwitterId->count()) {
            $this->isRegistor = false;
            // Existing connected user - login
            foreach ($customersByTwitterId as $key => $customerInfo) {
                $customer = $customerInfo;
            }
            $this->_helperTwitter->loginByCustomer($customer);
            if (!$isCheckoutPageReq) {
                $this->messageManager
                    ->addSuccess(
                        __('You have successfully logged in using your %1 account.', __('Twitter'))
                    );
            } else {
                $this->coreSession->setSuccessMsg(
                    __('You have successfully logged in using your %1 account.', __('Twitter'))
                );
            }
            return;
        }

        $customersByEmail = $this->_helperTwitter
            ->getCustomersByEmail($userInfo['email']);

        if ($customersByEmail->count()) {
            // Email account already exists - attach, login
            foreach ($customersByEmail as $key => $customerInfo) {
                $customer = $customerInfo;
            }
            $this->_helperTwitter->connectByTwitterId(
                $customer,
                $userInfo['id'],
                $token['oauth_token']
            );
            if (!$isCheckoutPageReq) {
                $this->messageManager->addSuccess(
                    __(
                        'We have discovered you already have an account at our store.'.
                        ' Your %1 account is now connected to your store account.',
                        __('Twitter')
                    )
                );
            } else {
                $this->coreSession->setSuccessMsg(__(
                    'We have discovered you already have an account at our store.'.
                    ' Your %1 account is now connected to your store account.',
                    __('Twitter')
                ));
            }
            return;
        }

        // New connection - create, attach, login
        if (empty($userInfo['name'])) {
            throw new LocalizedException(
                __('Sorry, could not retrieve your %1 last name. Please try again.', __('Twitter'))
            );
        }
        if (empty($userInfo['email'])) {
            $userInfo['email'] = $userInfo['screen_name'].'@'.$domain;
        }
        $customersCountByEmail = $customersByEmail->getSize();
        $customersCountByTwitterId = $customersByTwitterId->getSize();

        if (!$customersCountByTwitterId && !$customersCountByEmail) {
            if ($this->helper->getCustomerAttributes()) {
                $name = explode(' ', $userInfo['name'], 2);

                if (count($name) > 1) {
                    $firstName = $name[0];
                    $lastName = $name[1];
                } else {
                    $firstName = $name[0];
                    $lastName = $name[0];
                }
                $customerData = [
                    'firstname' => $firstName,
                    'lastname'  => $lastName,
                    'email'     => $userInfo['email'],
                    'confirmation'  => null,
                    'is_active' => 1,
                    'socialsignup_tid' => $userInfo['id'],
                    'socialsignu_ttoken'    => $token,
                    'label'     => __('twitter'),
                    'redirect_path' => 'socialsignup/twitter/redirect/'
                ];
                $this->helper->setInSession($customerData);
                return 'socialsignup/index/index';
            } else {
                $this->_helperTwitter->connectByCreatingAccount(
                    $userInfo['email'],
                    $userInfo['name'],
                    $userInfo['id'],
                    $token['oauth_token']
                );
            }
        }

        if (!$isCheckoutPageReq) {
            $this->messageManager->addSuccess(
                __(
                    'Your Twitter account is now connected to your new user account at our store.'
                    .' Now you can login using our Twitter Connect button.'
                )
            );
        } else {
            $this->coreSession->setSuccessMsg(__(
                'Your Twitter account is now connected to your new user account at our store.'
                .' Now you can login using our Twitter Connect button.'
            ));
        }
    }

    protected function isRequestValid($requestToken)
    {
        $params = $this->getRequest()->getParams();

        if (empty($params) || empty($requestToken)) {
            // Direct route access - deny
            return false;
        }

        $this->referer = $this->_url->getCurrentUrl();

        if (isset($params['denied'])) {
            unset($this->referer);
            $this->flag = "noaccess";
            $this->helper->closeWindow($this);
            return false;
        }

        return true;
    }

    /**
     * Connect with logged in customer
     *
     * @param object $customersByTwitterId
     * @param array  $userInfo
     * @param int    $token
     * @return void
     */
    private function _connectWithLoggedInCustomer($customersByTwitterId, $userInfo, $token)
    {
        $isCheckoutPageReq = 0;
        $isCheckoutPageReq = $this->helper->getCoreSession()->getIsSocialSignupCheckoutPageReq();
        if ($this->_customerSession->isLoggedIn()) {
            // Logged in user
            if ($customersByTwitterId->count()) {
                // Twitter account already connected to other account - deny
                if (!$isCheckoutPageReq) {
                    $this->messageManager
                    ->addNotice(
                        __('Your %1 account is already connected to one of our store accounts.', __('Twitter'))
                    );
                } else {
                    $this->coreSession->setSuccessMsg(
                        __('Your %1 account is already connected to one of our store accounts.', __('Twitter'))
                    );
                }
                return;
            }

            // Connect from account dashboard - attach
            $customer = $this->_customerSession->getCustomer();

            $this->_helperTwitter->connectByTwitterId(
                $customer,
                $userInfo['id'],
                $token
            );
            if (!$isCheckoutPageReq) {
                $this->messageManager->addSuccess(
                    __(
                        'Your %1 account is now connected to your store account.'
                        .' You can now login using our %1 Connect button or using store account credentials'
                        .' you will receive to your email address.',
                        __(
                            'Twitter'
                        )
                    )
                );
            } else {
                $this->coreSession->setSuccessMsg(__(
                    'Your %1 account is now connected to your store account.'.
                    ' You can now login using our %1 Connect button or using store'
                    .' account credentials you will receive to your email address.',
                    __(
                        'Twitter'
                    )
                ));
            }
            return;
        }
    }
}
