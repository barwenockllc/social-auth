<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Controller\Twitter;

class Authorize implements \Magento\Framework\App\ActionInterface
{
    /**
     * Connect a social media type
     *
     * @var string
     */
    protected const CONNECT_TYPE = 'twitter';

    /**
     * @var bool
     */
    protected $isRegistor = false;

    /**
     * @var string
     */
    protected $referer;

    /**
     * @var string
     */
    protected $flag;

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
     * @var \Barwenock\SocialAuth\Service\Authorize\Twitter
     */
    protected $twitterService;

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
     * @var \Magento\Framework\App\Response\Http
     */
    protected $redirect;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * Constructor
     *
     * @param \Magento\Store\Model\Store $store
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Barwenock\SocialAuth\Helper\CacheManagement $cacheManagement
     * @param \Magento\Framework\App\Response\Http $redirect
     * @param \Magento\Framework\UrlInterface $url
     * @param \Barwenock\SocialAuth\Service\Authorize\Twitter $twitterService
     * @param \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper
     * @param \Barwenock\SocialAuth\Model\Customer\Create $socialCustomerCreate
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     */
    public function __construct(
        \Magento\Store\Model\Store $store,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\RequestInterface $request,
        \Barwenock\SocialAuth\Helper\CacheManagement $cacheManagement,
        \Magento\Framework\App\Response\Http $redirect,
        \Magento\Framework\UrlInterface $url,
        \Barwenock\SocialAuth\Service\Authorize\Twitter $twitterService,
        \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper,
        \Barwenock\SocialAuth\Model\Customer\Create $socialCustomerCreate,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\ResultFactory $resultFactory
    ) {
        $this->customerSession = $customerSession;
        $this->eavAttribute = $eavAttribute;
        $this->store = $store;
        $this->coreSession = $coreSession;
        $this->request = $request;
        $this->cacheManagement = $cacheManagement;
        $this->redirect = $redirect;
        $this->url = $url;
        $this->twitterService = $twitterService;
        $this->socialCustomerHelper = $socialCustomerHelper;
        $this->socialCustomerCreate = $socialCustomerCreate;
        $this->messageManager = $messageManager;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Execute the Twitter authentication process and clean the cache
     *
     * This method initiates the Twitter authentication process, cleans the cache before
     * proceeding, and handles exceptions
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Exception
     */
    public function execute()
    {
        $this->cacheManagement->cleanCache();

        try {
            $checkoutPage = $this->coreSession->getCheckoutPage();
            $isSecure = $this->store->isCurrentlySecure();
            $this->twitterConnect();
        } catch (\Exception $exception) {
            if (!$checkoutPage) {
                $this->messageManager->addErrorMessage($exception->getMessage());
            } else {
                $this->coreSession->setErrorMsg($exception->getMessage());
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
     * Connect the customer's account using Twitter authentication.
     *
     * This method handles the Twitter authentication process, including fetching access tokens,
     * user information, checks for existing accounts and connects them, also if no account is found
     * it creates a new user account
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function twitterConnect()
    {
        $checkoutPage = $this->coreSession->getCheckoutPage();
        $oauthToken = $this->request->getParam('oauth_token');
        $oauthVerifier = $this->request->getParam('oauth_verifier');

        if (!$this->isRequestValid($oauthToken)) {
            return;
        }

        if ($oauthToken) {
            $attributeCodes = ['socialauth_twitter_id', 'socialauth_twitter_token'];
            foreach ($attributeCodes as $attributeCode) {
                $attributeId = $this->eavAttribute->getIdByCode('customer', $attributeCode);
                if ($attributeId === 0) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Attribute %1 does not exist', $attributeCode)
                    );
                }
            }

            $token = $this->twitterService->getAccessToken($oauthToken, $oauthVerifier);
            if (!isset($token['oauth_token'])) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Could not fetch customer access token for Twitter')
                );
            }

            $userInfo = $this->twitterService->getUserInfo($token['oauth_token'], $token['oauth_token_secret']);
            if (!isset($userInfo['email'])) {
                $userInfo['email'] = $userInfo['screen_name'] . '@twitter-user.com';
            }

            $customersByTwitterId = $this->socialCustomerHelper
                ->getCustomersBySocialId($userInfo['id'], self::CONNECT_TYPE);

            $this->connectExistingAccount($customersByTwitterId, $userInfo, $token['oauth_token']);

            if ($this->checkAccountByTwitterId($customersByTwitterId)) {
                return;
            }

            $customersByEmail = $this->socialCustomerHelper->getCustomersByEmail($userInfo['email']);

            if ($customersByEmail->getTotalCount() !== 0) {
                $this->socialCustomerHelper
                    ->connectBySocialId(
                        $customersByEmail,
                        $userInfo['id'],
                        $token['oauth_token'],
                        self::CONNECT_TYPE
                    );

                if (!$checkoutPage) {
                    $this->messageManager->addSuccessMessage(
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

            if (empty($userInfo['name'])) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Sorry, could not retrieve your %1 last name. Please try again.', __('Twitter'))
                );
            }

            $customersCountByEmail = $customersByEmail->getTotalCount();
            $customersCountByTwitterId = $customersByTwitterId->getTotalCount();

            if (!$customersCountByTwitterId && !$customersCountByEmail) {
                try {
                    $name = explode(' ', $userInfo['name'], 2);

                    if (count($name) > 1) {
                        $firstName = $name[0];
                        $lastName = $name[1];
                    } else {
                        $firstName = $name[0];
                        $lastName = $name[0];
                    }

                    $this->socialCustomerCreate->create(
                        $userInfo['email'],
                        $firstName,
                        $lastName,
                        $userInfo['id'],
                        $token['oauth_token'],
                        self::CONNECT_TYPE
                    );
                } catch (\Exception $exception) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __($exception->getMessage()),
                        $exception,
                        $exception->getCode()
                    );
                }
            }

            if (!$checkoutPage) {
                $this->messageManager->addSuccessMessage(
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
    }

    /**
     * Check if a customer account is associated with the provided Twitter ID
     *
     * @param \Magento\Customer\Api\Data\CustomerSearchResultsInterface $customersByTwitterId
     * @return bool
     * @throws \Exception
     */
    public function checkAccountByTwitterId($customersByTwitterId): bool
    {
        $checkoutPage = $this->coreSession->getCheckoutPage();
        if ($customersByTwitterId->getTotalCount() !== 0) {
            $this->isRegistor = false;
            foreach ($customersByTwitterId->getItems() as $customerInfo) {
                $customer = $customerInfo;
            }

            $this->socialCustomerHelper->loginByCustomer($customer);

            if (!$checkoutPage) {
                $this->messageManager->addSuccessMessage(
                    __('You have successfully logged in using your %1 account.', __('Twitter'))
                );
            } else {
                $this->coreSession->setSuccessMsg(
                    __('You have successfully logged in using your %1 account.', __('Twitter'))
                );
            }

            return true;
        }

        return false;
    }

    /**
     *  Check if the Twitter authentication request is valid
     *
     * @param string|null $requestToken
     * @return bool
     */
    protected function isRequestValid($requestToken): bool
    {
        $params = $this->request->getParams();

        if ($params === [] || empty($requestToken)) {
            // Direct route access - deny
            return false;
        }

        $this->referer = $this->url->getCurrentUrl();

        if (isset($params['denied'])) {
            unset($this->referer);
            $this->flag = "noaccess";
            return false;
        }

        return true;
    }

    /**
     * Connect an existing store account with the Twitter authentication credentials
     *
     * This method checks if the customer is already logged in and connects their account with
     * the provided Twitter authentication credentials, also it displays success messages based on
     * the checkout page status
     *
     * @param \Magento\Customer\Api\Data\CustomerSearchResultsInterface $customersByTwitterId
     * @param array $userInfo
     * @param string $token
     * @return void
     * @throws \Exception
     */
    protected function connectExistingAccount($customersByTwitterId, $userInfo, $token)
    {
        $checkoutPage = $this->coreSession->getCheckoutPage();
        if ($this->customerSession->isLoggedIn()) {
            // Logged in user
            if ($customersByTwitterId->getTotalCount() !== 0) {
                // Twitter account already connected to other account - deny
                if (!$checkoutPage) {
                    $this->messageManager->addNoticeMessage(__(
                        'Your %1 account is already connected to one of our store accounts.',
                        __('Twitter')
                    ));
                } else {
                    $this->coreSession->setSuccessMsg(
                        __('Your %1 account is already connected to one of our store accounts.', __('Twitter'))
                    );
                }

                return;
            }

            $this->socialCustomerHelper
                ->connectBySocialId($customersByTwitterId, $userInfo['id'], $token, self::CONNECT_TYPE);
            if (!$checkoutPage) {
                $this->messageManager->addSuccessMessage(
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
        }
    }
}
