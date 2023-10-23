<?php

namespace Barwenock\SocialAuth\Controller\Instagram;

class InstagramClient
{
    public const REDIRECT_URI_ROUTE = 'socialauth/instagram/connect';
    public const OAUTH2_SERVICE_URI = 'https://graph.instagram.com/';
    public const OAUTH2_AUTH_URI = 'https://api.instagram.com/oauth/authorize';
    public const OAUTH2_TOKEN_URI = 'https://api.instagram.com/oauth/access_token';

    /**
     * RedirectUri
     */
    protected $redirectUri = null;

    /**
     * State
     */
    protected $state = '';

    /**
     * Scope
     */
    protected $scope = ['user_profile'];

    /**
     * Token
     */
    protected $token = null;

    /**
     * @var \Magento\Store\Model\Store
     */
    protected $store;

    /**
     * @var \Magento\Framework\Url
     */
    protected $url;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @var \Barwenock\SocialAuth\Helper\Adminhtml\Config
     */
    protected $configHelper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @param \Magento\Store\Model\Store $store
     * @param \Magento\Framework\Url $url
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Store\Model\Store $store,
        \Magento\Framework\Url $url,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->store = $store;
        $this->url = $url;
        $this->curl = $curl;
        $this->configHelper = $configHelper;
        $this->request = $request;
    }

    /**
     * Set parameters
     *
     * @param array $params contain params value
     */
    public function setParameters($params = [])
    {
        if (!$this->configHelper->getInstagramStatus()) {
            return;
        }

        $isSecure = $this->store->isCurrentlySecure();
        $this->protocol = $isSecure ? "https" : "http";
        $this->redirectUri = $this->url->sessionUrlVar(
            $this->url->getUrl(self::REDIRECT_URI_ROUTE, ['_secure' => true])
        );

        $this->scope = $params['scope'] ?? $this->getScope();
        $this->state = $params['state'] ?? $this->getState();
    }

    /**
     * Create request url
     *
     * @return string
     */
    public function createRequestUrl()
    {
        $queryParams = [
            'response_type' => 'code',
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUri(),
            'state' => $this->getState(),
            'scope' => implode(',', $this->getScope()),
        ];

        return self::OAUTH2_AUTH_URI . '?' . http_build_query($queryParams);
    }

    /**
     * Get the response from the api
     *
     * @param string $method method of endpoint
     * @param array $params
     * @return object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function api($method = 'GET', $params = [])
    {
        $authMethod = '&access_token=' . $this->getAccessToken();
        $url = self::OAUTH2_SERVICE_URI . $this->token->user_id . '?fields=id,username' . $authMethod;

        $method = strtoupper($method);
        return $this->httpRequest($url, $method, $params);
    }

    /**
     * Fetch access token
     */
    protected function fetchAccessToken()
    {
        $code = $this->request->getParam('code');

        if (empty($code)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Unable to retrieve access code.')
            );
        }

        $tokenParams = [
            'code' => $code,
            'redirect_uri' => $this->getRedirectUri(),
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'grant_type' => 'authorization_code',
        ];

        $endPointResponse = $this->httpRequest(self::OAUTH2_TOKEN_URI, 'POST', $tokenParams);
        $this->token = $endPointResponse;
    }

    /**
     * Get response from the api
     *
     * @param  string $url    endpoint url
     * @param  string $method name of method
     * @param  array  $params cotains param
     * @return object
     */
    protected function httpRequest($url, $method = 'GET', $params = [])
    {
        $this->curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        $this->curl->setOption(CURLOPT_TIMEOUT, 60);

        switch ($method) {
            case 'GET':
                $this->curl->addHeader('Connection', 'Keep-Alive');
                $this->curl->get($url, $params);
                break;
            case 'POST':
                $this->curl->addHeader('Content-Type', 'application/x-www-form-urlencoded');
                $this->curl->post($url, $params);
                break;
            case 'DELETE':
                $this->curl->get($url, $params);
                break;
            default:
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Required HTTP method is not supported.')
                );
        }

        $response = json_decode($this->curl->getBody());
        $status = $this->curl->getStatus();

        if ($status === 400 || $status === 401) {
            $message = $response->error->message ?? __('Unspecified OAuth error occurred.');
            throw new \Magento\Framework\Exception\LocalizedException(__($message));
        }

        return $response;
    }

    /**
     * Get client id
     *
     * @return string
     */
    protected function getClientId()
    {
        return $this->configHelper->getInstagramClientId();
    }

    /**
     * Get client secret key
     *
     * @return string
     */
    protected function getClientSecret()
    {
        return $this->configHelper->getInstagramSecretKey();
    }

    /**
     * Get redirect url
     *
     * @return String
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * Set state
     *
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * Get Scope
     *
     * @return array
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Get State
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Get Access token
     *
     * @return string
     */
    public function getAccessToken()
    {
        if (empty($this->token)) {
            $this->fetchAccessToken();
        }
        return $this->token->access_token;
    }
}
