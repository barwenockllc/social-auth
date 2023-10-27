<?php

namespace Barwenock\SocialAuth\Controller\Facebook;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;
use Webkul\SocialSignup\Api\FacebooksignupRepositoryInterface;
use Magento\Framework\Url\DecoderInterface;
use Magento\Customer\Model\Url;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;

class Authorize implements \Magento\Framework\App\ActionInterface
{
    const CONNECT_TYPE = 'facebook';

    /**
     * @var isRegistor
     */
    protected $isRegistor;

    /**
     * @var PageFactory
     */
    private $customerSession;

    /**
     * @var EncoderInterface
     */
    private $urlDecoder;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $curl;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var $isCheckoutPageReq
     */
    private $isCheckoutPageReq;

    /**
     * Construct intialization
     *
     * @param Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel
     * @param \Magento\Customer\Model\Customer $customerModel
     * @param \Webkul\SocialSignup\Api\Data\FacebooksignupInterfaceFactory $facebooksignupFactory
     * @param FacebooksignupRepositoryInterface $facebooksignupRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param DecoderInterface $urlDecoder
     * @param Url $customerUrlModel
     * @param ScopeConfigInterface $scopeConfig
     * @param TransportBuilder $transportBuilder
     * @param SessionManagerInterface $coreSession
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param CookieManagerInterface $cookieManager
     * @param PageFactory $resultPageFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Zend\Uri\Uri $zendUri
     * @param \Webkul\SocialSignup\Helper\Data $helperData
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param \Magento\Framework\Serialize\Serializer\Base64Json $base64Json
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        DecoderInterface $urlDecoder,
        SessionManagerInterface $coreSession,
        \Magento\Framework\HTTP\Client\Curl $curl,
        CookieManagerInterface $cookieManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper,
        \Barwenock\SocialAuth\Model\Customer\Create $socialCustomerCreate,
        \Barwenock\SocialAuth\Service\Authorize\Facebook $facebookService
    ) {
        $this->urlDecoder = $urlDecoder;
        $this->customerSession = $customerSession;
        $this->curl = $curl;
        $this->coreSession = $coreSession;
        $this->cookieManager = $cookieManager;
        $this->logger = $logger;
        $this->isCheckoutPageReq = 0;
        $this->jsonHelper = $jsonHelper;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->messageManager = $messageManager;
        $this->configHelper = $configHelper;
        $this->socialCustomerHelper = $socialCustomerHelper;
        $this->socialCustomerCreate = $socialCustomerCreate;
        $this->facebookService = $facebookService;
        $this->isRegistor = false;
    }

    /**
     * Execute function
     */
    public function execute()
    {
        $isCheckoutPageReq = 0;
        $post = $this->request->getParams();
        if (isset($post['is_checkoutPageReq']) && $post['is_checkoutPageReq'] == 1) {
            $isCheckoutPageReq = 1;
        }
        $this->isCheckoutPageReq = $isCheckoutPageReq;
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
     * Get facebook cookie
     *
     * @param  int    $appId     faceboook id
     * @param  string $appSecret facebook secret key
     * @return array
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
     * Get facebook data
     *
     * @param  array   $facebookUser      facebook user data
     * @param  boolean $isCheckoutPageReq check socialsignup request from checkoutpage
     * @return array
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
