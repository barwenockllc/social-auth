<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Controller\Facebook;

class Authorize implements \Magento\Framework\App\ActionInterface
{
    /**
     * Connect social media type
     * @var string
     */
    protected const CONNECT_TYPE = 'facebook';

    /**
     * @var bool
     */
    protected $isRegistor = false;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $coreSession;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Barwenock\SocialAuth\Helper\Adminhtml\Config
     */
    protected $configHelper;

    /**
     * @var \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer
     */
    protected $socialCustomerHelper;

    /**
     * @var \Barwenock\SocialAuth\Model\Customer\Create
     */
    protected $socialCustomerCreate;

    /**
     * @var \Barwenock\SocialAuth\Service\Authorize\Facebook
     */
    protected $facebookService;

    /**
     * @var \Barwenock\SocialAuth\Helper\Authorize\Redirect
     */
    protected $authorizeRedirectHelper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper
     * @param \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper
     * @param \Barwenock\SocialAuth\Model\Customer\Create $socialCustomerCreate
     * @param \Barwenock\SocialAuth\Service\Authorize\Facebook $facebookService
     * @param \Barwenock\SocialAuth\Helper\Authorize\Redirect $authorizeRedirectHelper
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper,
        \Barwenock\SocialAuth\Model\Customer\Create $socialCustomerCreate,
        \Barwenock\SocialAuth\Service\Authorize\Facebook $facebookService,
        \Barwenock\SocialAuth\Helper\Authorize\Redirect $authorizeRedirectHelper,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->customerSession = $customerSession;
        $this->coreSession = $coreSession;
        $this->cookieManager = $cookieManager;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->messageManager = $messageManager;
        $this->configHelper = $configHelper;
        $this->socialCustomerHelper = $socialCustomerHelper;
        $this->socialCustomerCreate = $socialCustomerCreate;
        $this->facebookService = $facebookService;
        $this->authorizeRedirectHelper = $authorizeRedirectHelper;
        $this->url = $url;
    }

    public function execute()
    {
        $checkoutPage = 0;
        $post = $this->request->getParams();
        if (isset($post['checkoutPage']) && $post['checkoutPage'] == 1) {
            $checkoutPage = 1;
        }

        try {
            $facebookUser = null;
            $facebookAppId = $this->configHelper->getFacebookAppId();
            $facebookAppSecretKey = $this->configHelper->getFacebookAppSecret();

            $cookie = $this->getFacebookCookie($facebookAppId, $facebookAppSecretKey);
            if (!empty($cookie['access_token'])) {
                $facebookUserUrl = $this->facebookService->buildUserDataUrl($cookie, $facebookAppSecretKey);
                $facebookUser  = $this->facebookService->getFacebookUserData($facebookUserUrl);
            }

            if ($facebookUser != null) {
                if (!isset($facebookUser['email'])) {
                    if ($checkoutPage === 0) {
                        $this->messageManager->addErrorMessage(
                            __(
                                'There is some privacy with this Facebook Account,'
                                .' so please check your account or authorize with another account.'
                            )
                        );
                    } else {
                        $this->coreSession->setErrorMsg(
                            __(
                                'There is some privacy with this Facebook Account,'
                                .'so please check your account or authorize with another account.'
                            )
                        );
                    }

                    $resultRedirect = $this->resultFactory
                        ->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
                    return $resultRedirect->setPath('customer/account/login');
                } else {
                    $this->callBack($facebookUser, $checkoutPage);

                    $redirectPage = $this->authorizeRedirectHelper->getRedirectPage();
                    if ($redirectPage != 'reload') {
                        return $this->resultFactory->create(
                            \Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT
                        )->setPath($redirectPage);
                    } else {
                        return $this->resultFactory->create(
                            \Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT
                        )->setPath($this->url->getCurrentUrl());
                    }
                }
            } else {
                return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
                    ->setPath('customer/account/login');
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @param $appId
     * @param $appSecret
     * @return array|bool|float|int|mixed|string
     * @throws \Exception
     */
    protected function getFacebookCookie($appId, $appSecret)
    {
        try {
            $cookieData = $this->cookieManager->getCookie('fbsr_' . $appId);
            if ($cookieData != '') {
                return $this->facebookService->getNewFacebookCookie($appId, $appSecret);
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->facebookService->getOldFacebookCookie($appId, $appSecret);
    }

    /**
     * @param $facebookUser
     * @param $checkoutPage
     * @return void
     * @throws \Exception
     */
    protected function callBack($facebookUser, $checkoutPage)
    {
        try {
            if (isset($facebookUser['id']) && $facebookUser['id']) {
                $customersByFacebookId = $this->socialCustomerHelper
                    ->getCustomersBySocialId($facebookUser['id'], self::CONNECT_TYPE);

                if ($customersByFacebookId->getTotalCount() !== 0) {
                    if (!$checkoutPage) {
                        $this->messageManager->addSuccessMessage(
                            __(
                                'You have successfully logged in using your facebook account'
                            )
                        );
                    } else {
                        $this->coreSession->setSuccessMsg(
                            __('You have successfully logged in using your %1 account.', __('Facebook'))
                        );
                    }

                    $this->customerSession->loginById($customersByFacebookId->getItems()[0]->getId());
                } else {
                    $customersByEmail = $this->socialCustomerHelper->getCustomersByEmail($facebookUser['email']);

                    if ($customersByEmail->getTotalCount() !== 0) {
                        if (!$checkoutPage) {
                            $this->messageManager->addSuccessMessage(
                                __(
                                    'You have successfully logged in using your facebook account'
                                )
                            );
                        } else {
                            $this->coreSession->setSuccessMsg(
                                __('You have successfully logged in using your %1 account.', __('Facebook'))
                            );
                        }

                        $this->socialCustomerHelper->connectBySocialId(
                            $customersByEmail,
                            $facebookUser['id'],
                            null,
                            self::CONNECT_TYPE
                        );

                        if (!$checkoutPage) {
                            $this->messageManager->addSuccessMessage(
                                __(
                                    'Your %1 account is now connected to your store account.'
                                    .' You can now login using our %1 Connect button or using store account credentials'
                                    .' you will receive to your email address.',
                                    __('Facebook')
                                )
                            );
                        } else {
                            $this->coreSession->setSuccessMsg(
                                __(
                                    'Your %1 account is now connected to your store account.'
                                    .' You can now login using our %1 Connect button or using store account credentials'
                                    .' you will receive to your email address.',
                                    __('Facebook')
                                )
                            );
                        }

                        $this->customerSession->loginById($customersByEmail->getItems()[0]->getId());
                    } else {
                        $this->socialCustomerCreate->create(
                            $facebookUser['email'],
                            $facebookUser['first_name'],
                            $facebookUser['last_name'],
                            $facebookUser['id'],
                            null,
                            self::CONNECT_TYPE
                        );

                        $this->isRegistor = true;

                        if (!$checkoutPage) {
                            $this->messageManager->addSuccessMessage(
                                __(
                                    'Your %1 account is now connected to your new user account at our store.'
                                    .' Now you can login using our %1 Connect button or using store account credentials'
                                    .' you will receive to your email address.',
                                    __('Facebook')
                                )
                            );
                        } else {
                            $this->coreSession->setSuccessMsg(__(
                                'Your %1 account is now connected to your new user account at our store.'
                                .' Now you can login using our %1 Connect button or using store account credentials'
                                .' you will receive to your email address.',
                                __('Facebook')
                            ));
                        }
                    }
                }
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
