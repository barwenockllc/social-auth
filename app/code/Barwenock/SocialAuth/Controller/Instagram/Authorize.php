<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Controller\Instagram;

class Authorize implements \Magento\Framework\App\ActionInterface
{
    /**
     * Connect a social media type
     *
     * @var string
     */
    protected const CONNECT_TYPE = 'instagram';

    /**
     * @var bool
     */
    protected $isRegistor = true;

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
     * @var \Magento\Framework\Session\Generic
     */
    protected $session;

    /**
     * @var \Barwenock\SocialAuth\Service\Authorize\Instagram
     */
    protected $instagramService;

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
     * Construct
     *
     * @param \Magento\Framework\Session\Generic $session
     * @param \Magento\Store\Model\Store $store
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
     * @param \Barwenock\SocialAuth\Service\Authorize\Instagram $instagramService
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Barwenock\SocialAuth\Helper\CacheManagement $cacheManagement
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Url $url
     * @param \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper
     * @param \Barwenock\SocialAuth\Model\Customer\Create $socialCustomerCreate
     * @param \Magento\Framework\App\Response\Http $redirect
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     */
    public function __construct(
        \Magento\Framework\Session\Generic                    $session,
        \Magento\Store\Model\Store                            $store,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute     $eavAttribute,
        \Barwenock\SocialAuth\Service\Authorize\Instagram     $instagramService,
        \Magento\Framework\Session\SessionManagerInterface    $coreSession,
        \Magento\Customer\Model\Session                       $customerSession,
        \Barwenock\SocialAuth\Helper\CacheManagement          $cacheManagement,
        \Magento\Framework\App\RequestInterface               $request,
        \Magento\Framework\Url                                $url,
        \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper,
        \Barwenock\SocialAuth\Model\Customer\Create           $socialCustomerCreate,
        \Magento\Framework\App\Response\Http                  $redirect,
        \Magento\Framework\Message\ManagerInterface           $messageManager,
        \Magento\Framework\Controller\ResultFactory           $resultFactory
    ) {
        $this->customerSession = $customerSession;
        $this->eavAttribute = $eavAttribute;
        $this->store = $store;
        $this->coreSession = $coreSession;
        $this->session = $session;
        $this->instagramService = $instagramService;
        $this->cacheManagement = $cacheManagement;
        $this->request = $request;
        $this->url = $url;
        $this->socialCustomerHelper = $socialCustomerHelper;
        $this->socialCustomerCreate = $socialCustomerCreate;
        $this->redirect = $redirect;
        $this->messageManager = $messageManager;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Execute the Instagram authentication process.
     *
     * This method sets parameters for the Instagram service, cleans the cache,
     * and attempts to connect the user with their Instagram account.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $this->instagramService->setParameters();
        $this->cacheManagement->cleanCache();

        try {
            $isSecure = $this->store->isCurrentlySecure();
            $checkoutPage = $this->coreSession->getCheckoutPage();

            $this->instagramConnect();
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
     * Connects a customer account using Instagram authentication
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function instagramConnect()
    {
        $checkoutPage = $this->coreSession->getCheckoutPage();
        $errorCode = $this->request->getParam('error');
        $code = $this->request->getParam('code');
        $state = $this->request->getParam('state');

        if (!$this->isRequestValid($errorCode, $code, $state)) {
            return;
        }

        if ($code) {
            $attributeCodes = ['socialauth_instagram_id', 'socialauth_instagram_token'];
            foreach ($attributeCodes as $attributeCode) {
                $attributeId = $this->eavAttribute->getIdByCode('customer', $attributeCode);
                if ($attributeId === 0) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Attribute %1 does not exist', $attributeCode)
                    );
                }
            }

            $token = $this->instagramService->getAccessToken();
            $userInfo = $this->instagramService->api(
                $token->user_id . '?fields=id,username' . '&access_token=' . $token->access_token
            );

            $customersByInstagramId = $this->socialCustomerHelper
                ->getCustomersBySocialId($userInfo->id, self::CONNECT_TYPE);

            $this->connectExistingAccount($customersByInstagramId, $userInfo, $token->access_token);

            if ($this->checkAccountByInstagramId($customersByInstagramId)) {
                return;
            }

            $customersByEmail = $this->socialCustomerHelper
                ->getCustomersByEmail($userInfo->username . '@instagram-user.com');

            if ($customersByEmail->getTotalCount() !== 0) {
                $this->socialCustomerHelper
                    ->connectBySocialId($customersByEmail, $userInfo->id, $token->access_token, self::CONNECT_TYPE);

                if (!$checkoutPage) {
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

            $userInfo->full_name = (isset($userInfo->full_name)) ? $userInfo->full_name : $userInfo->username;
            if (empty($userInfo->full_name)) {
                throw new \Magento\Framework\Exception\LocalizedException(
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
                        $token->access_token,
                        self::CONNECT_TYPE
                    );
                } catch (\Magento\Framework\Exception\LocalizedException $exception) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __($exception->getMessage()),
                        $exception->getCode(),
                        $exception
                    );
                }
            }

            if (!$checkoutPage) {
                $this->messageManager->addNoticeMessage(
                    __(
                        "Since instagram doesn't support third-party access to your email address,"
                        .' we were unable to send you your store account credentials.'
                        .' To be able to login using store account credentials you will need to update your'
                        .' email address and password using  Edit Account Information.'
                    )
                );
            } else {
                $this->coreSession->setSuccessMsg(__(
                    "Since instagram doesn't support third-party access to your email address,"
                    .' we were unable to send you your store account credentials.'
                    .' To be able to login using store account credentials you will need to update your'
                    .' email address and password using  Edit Account Information.'
                ));
            }
        }
    }

    /**
     * Connect an existing customer account by Instagram ID and display appropriate messages.
     *
     *  If a customer is logged in, this method checks if the Instagram account is already connected
     *  to another store account, also if not connected, it establishes the connection and sets success messages.
     *
     * @param \Magento\Customer\Api\Data\CustomerSearchResultsInterface $customersByInstagramId
     * @param object $userInfo
     * @param string $token
     * @return void
     * @throws \Exception
     */
    public function connectExistingAccount($customersByInstagramId, $userInfo, $token)
    {
        $checkoutPage = $this->coreSession->getCheckoutPage();
        if ($this->customerSession->isLoggedIn()) {
            // Logged in user
            if ($customersByInstagramId->getTotalCount() !== 0) {
                // Instagram account already connected to other account - deny
                if (!$checkoutPage) {
                    $this->messageManager->addNoticeMessage(__(
                        'Your %1 account is already connected to one of our store accounts.',
                        __('Instagram')
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
            if (!$checkoutPage) {
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
     * Check if an account is connected by Instagram ID and log in the customer if found.
     *
     *  This method checks the presence of a connected account by Instagram ID. If an account is found,
     *  the customer is logged in, and success messages are set.
     *
     * @param \Magento\Customer\Api\Data\CustomerSearchResultsInterface $customersByInstagramId
     * @return bool
     * @throws \Exception
     */
    protected function checkAccountByInstagramId($customersByInstagramId): bool
    {
        $checkoutPage = $this->coreSession->getCheckoutPage();
        if ($customersByInstagramId->getTotalCount() !== 0) {
            $this->isRegistor = false;
            foreach ($customersByInstagramId->getItems() as $customerInfo) {
                $customer = $customerInfo;
            }

            $this->socialCustomerHelper->loginByCustomer($customer);

            if (!$checkoutPage) {
                $this->messageManager->addSuccessMessage(
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

    /**
     * Check if the Instagram authentication request is valid
     *
     * @param string|null $errorCode
     * @param string|null $code
     * @param string|null $state
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function isRequestValid($errorCode, $code, $state): bool
    {
        if (!$errorCode && !$code && !$state) {
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
                return false;
            }

            return false;
        }

        return true;
    }
}
