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
     * @param \Webkul\SocialSignup\Api\Data\LogintypeInterfaceFactory $logintypeFactory
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
        \Webkul\SocialSignup\Api\Data\LogintypeInterfaceFactory $logintypeFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Barwenock\SocialAuth\Api\FacebookCustomerRepositoryInterface $facebookCustomerRepository,
        \Barwenock\SocialAuth\Helper\Authorize\SocialCustomer $socialCustomerHelper,
        \Barwenock\SocialAuth\Model\Customer\Create $socialCustomerCreate
    ) {
        $this->urlDecoder = $urlDecoder;
        $this->customerSession = $customerSession;
        $this->curl = $curl;
        $this->coreSession = $coreSession;
        $this->cookieManager = $cookieManager;
        $this->logger = $logger;
        $this->isCheckoutPageReq = 0;
        $this->jsonHelper = $jsonHelper;
        $this->logintypeFactory = $logintypeFactory;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->messageManager = $messageManager;
        $this->configHelper = $configHelper;
        $this->facebookCustomerRepository = $facebookCustomerRepository;
        $this->socialCustomerHelper = $socialCustomerHelper;
        $this->socialCustomerCreate = $socialCustomerCreate;
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
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $facebookAppId = $this->configHelper->getFacebookAppId();
            $facebookAppSecretKey = $this->configHelper->getFacebookAppSecret();

            $cookie = $this->getFacebookCookie($facebookAppId, $facebookAppSecretKey);
            if (!empty($cookie['access_token'])) {
                $appsecretProof= hash_hmac('sha256', $cookie['access_token'], $facebookAppSecretKey);
                $base_url = 'https://graph.facebook.com/v18.0/me?appsecret_proof=';
                $token = $base_url . $appsecretProof . '&access_token=' . $cookie['access_token'];

                $facebookParams = [
                    'debug' => 'all',
                    'fields' => 'id,name,email,first_name,last_name,locale',
                    'format' => 'json',
                    'method' => 'get',
                    'pretty' => '0',
                    'suppress_http_code' => '1',
                ];
                $queryParams = '&' . http_build_query($facebookParams);

                $facebookUser  = $this->jsonHelper->jsonDecode($this->getFbData($token . $queryParams));
            }

            if ($facebookUser != null) {
                if (isset($facebookUser['email']) && !$facebookUser['email']) {
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
                    return $resultRedirect->setPath('customer/account/login');
                }
            } else {
                return $resultRedirect->setPath('customer/account/login');
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
                return $this->getNewFacebookCookie($appId, $appSecret);
            }
        } catch (\Exception $exception) {
            $this->logger->info('Controller Facebook getFacebookCookie: ' . $exception->getMessage());
        }

        return $this->getOldFacebookCookie($appId, $appSecret);
    }

    /**
     * Get old facebook cookie
     *
     * @param  int    $appId     faceboook id
     * @param  string $appSecret facebook secret key
     */
    private function getOldFacebookCookie($appId, $appSecret)
    {
        $args = [];
        try {
            $cookieData = $this->cookieManager->getCookie('fbsr_' . $appId);

            // Parse the query string into an array
            parse_str(trim($cookieData, '\\"'), $args);

            if (isset($args['sig'])) {
                $signature = $args['sig'];
                unset($args['sig']); // Remove 'sig' from the array

                // Sort the array by key
                ksort($args);

                // Recreate the query string without 'sig'
                $payload = http_build_query($args, null, null, PHP_QUERY_RFC3986);

                // Calculate the encrypted data using the payload and app secret
                $encryptedData = hash('sha256', $payload . $appSecret);

                // Compare the calculated signature with the one from the cookie
                if ($encryptedData === $signature) {
                    return $args;
                }
            }
        } catch (\Exception $e) {
            $this->logger->info('Controller Facebook getOldFacebookCookie: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Get new facebook cookie
     *
     * @param  int    $appId     faceboook id
     * @param  string $appSecret facebook secret key
     */
    private function getNewFacebookCookie($appId, $appSecret)
    {
        $signedRequest = [];
        try {
            $cookieData = $this->cookieManager->getCookie('fbsr_' . $appId);
            $signedRequest = $this->parseSignedRequest($cookieData, $appSecret);

            if (!empty($signedRequest)) {
                $base = "https://graph.facebook.com/v4.0/oauth/access_token?client_id=$appId";
                $signedCode = $signedRequest['code'];

                $accessTokenResponse = $this
                    ->getFbData("$base&redirect_uri=&client_secret=$appSecret&code=$signedCode");

                $response = $this->jsonHelper->jsonDecode($accessTokenResponse, true);
                if (!empty($response['access_token'])) {
                    $signedRequest['access_token'] = $response['access_token'];
                    $signedRequest['expires'] = time() + $response['expires_in'];
                }
            }
        } catch (\Exception $e) {
            $this->logger->info('Controller Facebook getNewFacebookCookie: ' . $e->getMessage());
        }
        return $signedRequest;
    }


    /**
     * Parse the signed request
     *
     * @param  string $signedRequest contain access token & expire date
     * @param  int   $secret        secret key
     * @return array
     */
    private function parseSignedRequest($signedRequest, $secret)
    {
        try {
            list($encodedSig, $payload) = explode('.', $signedRequest, 2);

            $sig = $this->base64UrlDecode($encodedSig);
            $data = $this->jsonHelper->jsonDecode($this->base64UrlDecode($payload), true);

            if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
                return null;
            }

            $expectedSig = hash_hmac('sha256', $payload, $secret, true);
            if ($sig !== $expectedSig) {
                return null;
            }
            return $data;
        } catch (\Exception $e) {
            $this->logger->info('Controller Facebook parseSignedRequest : '.$e->getMessage());
        }
    }

    /**
     * Decode the sign
     *
     * @param  string $input
     */
    private function base64UrlDecode($input)
    {
        try {
            return $this->urlDecoder->decode(strtr($input, '-_', '+/'));
        } catch (\Exception $e) {
            $this->logger->info('Controller Facebook base64UrlDecode : '.$e->getMessage());
        }
    }

    /**
     * Get facebook data
     *
     * @param  string $url
     * @return string
     */
    private function getFbData($url)
    {
        try {
            $this->curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            $this->curl->get($url);

            return $this->curl->getBody();
        } catch (\Exception $e) {
            $this->logger->info('Controller Facebook getFbData : '.$e->getMessage());
        }
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
            $session = $this->customerSession;
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

                    $session->loginById($customersByFacebookId->getItems()[0]->getId());
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

                        $session->loginById($customersByEmail->getItems()[0]->getId());
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
