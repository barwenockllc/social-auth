<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Controller\Linkedin;

class Authorize implements \Magento\Framework\App\ActionInterface
{
    /**
     * Connect social media type
     * @var string
     */
    protected const CONNECT_TYPE = 'linkedin';

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
     * @var \Barwenock\SocialAuth\Service\Authorize\Linkedin
     */
    protected $linkedinService;

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
     * @param \Magento\Store\Model\Store $store
     * @param \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
     * @param \Barwenock\SocialAuth\Service\Authorize\Linkedin $linkedinService
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\UrlInterface $url
     * @param \Barwenock\SocialAuth\Model\Customer\Create $customerCreate
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     */
    public function __construct(
        \Magento\Store\Model\Store $store,
        \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Barwenock\SocialAuth\Service\Authorize\Linkedin $linkedinService,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\UrlInterface $url,
        \Barwenock\SocialAuth\Model\Customer\Create $socialCustomerCreate,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\ResultFactory $resultFactory
    ) {
        $this->customerSession = $customerSession;
        $this->socialCustomerHelper = $socialCustomerHelper;
        $this->eavAttribute = $eavAttribute;
        $this->store = $store;
        $this->coreSession = $coreSession;
        $this->linkedinService = $linkedinService;
        $this->request = $request;
        $this->url = $url;
        $this->socialCustomerCreate = $socialCustomerCreate;
        $this->messageManager = $messageManager;
        $this->resultFactory = $resultFactory;
    }

    public function execute()
    {
        try {
            $checkoutPage = $this->coreSession->getCheckoutPage();
            $isSecure = $this->store->isCurrentlySecure();

            $this->linkedinService->setParameters();
            $this->linkedinConnect();
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
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function linkedinConnect()
    {
        $checkoutPage = $this->coreSession->getCheckoutPage();
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
                if ($attributeId === 0) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Attribute %1 does not exist', $attributeCode)
                    );
                }
            }

            $userInfo = $this->linkedinService->api('/v2/userinfo');
            $token = $this->linkedinService->getAccessToken();

            $customersByLinkedinId = $this->socialCustomerHelper
                ->getCustomersBySocialId($userInfo->sub, self::CONNECT_TYPE);

            $this->connectExistingAccount($customersByLinkedinId, $userInfo, $token);

            if ($this->checkAccountByLinkedinId($customersByLinkedinId)) {
                return;
            }

            $customersByEmail = $this->socialCustomerHelper->getCustomersByEmail($userInfo->email);

            if ($customersByEmail->getTotalCount() !== 0) {
                $this->socialCustomerHelper
                    ->connectBySocialId($customersByEmail, $userInfo->sub, $token, self::CONNECT_TYPE);

                if (!$checkoutPage) {
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
                    $this->socialCustomerCreate->create(
                        $userInfo->email,
                        $userInfo->given_name,
                        $userInfo->family_name,
                        $userInfo->sub,
                        $token,
                        self::CONNECT_TYPE
                    );
            }

            if (!$checkoutPage) {
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

    /**
     * @param $errorCode
     * @param $code
     * @param $state
     * @return bool
     */
    protected function isRequestValid($errorCode, $code, $state): bool
    {
        if (!$errorCode && !$code && !$state) {
            // Direct route access - deny
            return false;
        }

        $this->referer = $this->url->getCurrentUrl();

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
     * @param $customersByLinkedinId
     * @param $userInfo
     * @param $token
     * @return void
     * @throws \Exception
     */
    protected function connectExistingAccount($customersByLinkedinId, $userInfo, $token)
    {
        $checkoutPage = $this->coreSession->getCheckoutPage();
        if ($this->customerSession->isLoggedIn()) {
            if ($customersByLinkedinId->getTotalCount()) {
                if (!$checkoutPage) {
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
            if (!$checkoutPage) {
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
        }
    }

    /**
     * @param $customersByLinkedinId
     * @return bool
     * @throws \Exception
     */
    protected function checkAccountByLinkedinId($customersByLinkedinId): bool
    {
        $checkoutPage = 0;
        $checkoutPage = $this->coreSession->getCheckoutPage();
        if ($customersByLinkedinId->getTotalCount()) {
            $this->isRegistor = false;

            foreach ($customersByLinkedinId->getItems() as $customerInfo) {
                $customer = $customerInfo;
            }

            $this->socialCustomerHelper->loginByCustomer($customer);

            if (!$checkoutPage) {
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
