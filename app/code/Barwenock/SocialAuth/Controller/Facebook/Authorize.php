<?php

namespace Barwenock\SocialAuth\Controller\Facebook;

class Authorize implements \Magento\Framework\App\ActionInterface
{
    /**
     * Connect social media type
     */
    const CONNECT_TYPE = 'facebook';

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper
     * @param \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper
     * @param \Barwenock\SocialAuth\Model\Customer\Create $socialCustomerCreate
     * @param \Barwenock\SocialAuth\Service\Authorize\Facebook $facebookService
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper,
        \Barwenock\SocialAuth\Model\Customer\Create $socialCustomerCreate,
        \Barwenock\SocialAuth\Service\Authorize\Facebook $facebookService
    ) {
        $this->customerSession = $customerSession;
        $this->coreSession = $coreSession;
        $this->cookieManager = $cookieManager;
        $this->logger = $logger;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->messageManager = $messageManager;
        $this->configHelper = $configHelper;
        $this->socialCustomerHelper = $socialCustomerHelper;
        $this->socialCustomerCreate = $socialCustomerCreate;
        $this->facebookService = $facebookService;
        $this->isRegistor = false;
    }

    public function execute()
    {
        $isCheckoutPageReq = 0;
        $post = $this->request->getParams();
        if (isset($post['is_checkoutPageReq']) && $post['is_checkoutPageReq'] == 1) {
            $isCheckoutPageReq = 1;
        }
        $facebookUser = null;

        try {
            $facebookAppId = $this->configHelper->getFacebookAppId();
            $facebookAppSecretKey = $this->configHelper->getFacebookAppSecret();

            $cookie = $this->getFacebookCookie($facebookAppId, $facebookAppSecretKey);
            if (!empty($cookie['access_token'])) {
                $facebookUserUrl = $this->facebookService->buildUserDataUrl($cookie, $facebookAppSecretKey);
                $facebookUser  = $this->facebookService->getFacebookUserData($facebookUserUrl);
            }

            if ($facebookUser != null) {
                if (!isset($facebookUser['email'])) {
                    if (!$isCheckoutPageReq) {
                        $this->messageManager->addErrorMessage(
                            __(
                                'There is some privacy with this Facebook Account,'
                                .' so please check your account or signup with another account.'
                            )
                        );
                    } else {
                        $this->coreSession->setErrorMsg(
                            __(
                                'There is some privacy with this Facebook Account,'
                                .'so please check your account or signup with another account.'
                            )
                        );
                    }
                    $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                    return $resultRedirect->setPath('customer/account/login');
                } else {
                    $redirectPath = $this->callBack($facebookUser, $isCheckoutPageReq);
                    if ($redirectPath) {
                        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                        return $resultRedirect->setPath($redirectPath);
                    }
                    return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                        ->setPath('customer/account/login');
                }
            } else {
                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                    ->setPath('customer/account/login');
            }
        } catch (\Exception $e) {
            $this->logger->info('Controller Facebook Login : '.$e->getMessage());
            $this->messageManager->addErrorMessage(__('Something went wrong, please check log file.'));
            $this->coreSession->setErrorMsg(
                __('Something went wrong, please check log file.')
            );
            return $resultRedirect->setPath('customer/account/login');
        }
    }

    /**
     * @param $appId
     * @param $appSecret
     * @return array|bool|float|int|mixed|string
     * @throws \Exception
     */
    private function getFacebookCookie($appId, $appSecret)
    {
        try {
            $cookieData = $this->cookieManager->getCookie('fbsr_' . $appId);
            if ($cookieData != '') {
                return $this->facebookService->getNewFacebookCookie($appId, $appSecret);
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

        return $this->facebookService->getOldFacebookCookie($appId, $appSecret);
    }

    /**
     * @param $facebookUser
     * @param $isCheckoutPageReq
     * @return void
     * @throws \Exception
     */
    private function callBack($facebookUser, $isCheckoutPageReq)
    {
        try {
            if (isset($facebookUser['id']) && $facebookUser['id']) {
                $customersByFacebookId = $this->socialCustomerHelper
                    ->getCustomersBySocialId($facebookUser['id'], self::CONNECT_TYPE);

                if ($customersByFacebookId->getTotalCount()) {
                    if (!$isCheckoutPageReq) {
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

                    if ($customersByEmail->getTotalCount()) {
                        if (!$isCheckoutPageReq) {
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

                        if (!$isCheckoutPageReq) {
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

                        if (!$isCheckoutPageReq) {
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
            throw new \Exception($exception->getMessage());
        }
    }
}
