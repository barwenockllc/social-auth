<?php

namespace Barwenock\SocialAuth\Controller\Google;

class GoogleClient
{
    protected const REDIRECT_URI_ROUTE = 'socialauth/google/connect';
    protected const OAUTH2_TOKEN_URI = 'https://accounts.google.com/o/oauth2/token';
    protected const OAUTH2_AUTH_URI = 'https://accounts.google.com/o/oauth2/auth';
    protected const OAUTH2_SERVICE_URI = 'https://www.googleapis.com/oauth2/v2';

    /**
     * Scope
     */
    protected $scope = [
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
    ];

    /**
     * Access
     */
    protected $access = 'offline';

    /**
     * Prompt
     */
    protected $prompt = 'auto';

    /**
     * RedirectUri
     */
    protected $redirectUri = null;

    /**
     * State
     */
    protected $state = '';

    /**
     * @var \Magento\Store\Model\Store
     */
    protected $store;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @var \Magento\Framework\Url
     */
    protected $url;

    /**
     * @var \Magento\Framework\App\Action\Context
     */
    protected $contextController;

    /**
     * @var \Barwenock\SocialAuth\Helper\Adminhtml\Config
     */
    protected $configHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonSerializer;

    /**
     * @param \Magento\Store\Model\Store $store
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\App\Action\Context $contextController
     * @param \Magento\Framework\Url $url
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     */
    public function __construct(
        \Magento\Store\Model\Store $store,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Action\Context $contextController,
        \Magento\Framework\Url $url,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
    ) {
        $this->store = $store;
        $this->curl = $curl;
        $this->contextController = $contextController;
        $this->url = $url;
        $this->configHelper = $configHelper;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Set parameters
     *
     * @param array $params contain params value
     */
    public function setParameters($params = [])
    {
        if (!$this->configHelper->getGoogleStatus()) {
            return;
        }

        $isSecure = $this->store->isCurrentlySecure();
        $this->protocol = $isSecure ? "https" : "http";
        $this->redirectUri = $this->url->sessionUrlVar(
            $this->url->getUrl(self::REDIRECT_URI_ROUTE, ['_secure' => $isSecure])
        );

        $this->scope = $params['scope'] ?? $this->getScope();
        $this->state = $params['state'] ?? $this->getState();
        $this->access = $params['access'] ?? $this->getAccess();
        $this->prompt = $params['prompt'] ?? $this->getPrompt();
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
            'redirect_uri' => $this->getRedirectUri(),
            'client_id' => $this->configHelper->getGoogleClientId(),
            'scope' => implode(' ', $this->getScope()),
            'state' => $this->getState(),
            'access_type' => $this->getAccess(),
            'approvalprompt' => $this->getPrompt(),
            'display' => 'popup',
        ];

        return self::OAUTH2_AUTH_URI . '?' . http_build_query($queryParams);
    }

    /**
     * Get the response from the API
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method (GET by default)
     * @param array $params Additional parameters
     * @return object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function api($endpoint, $method = 'GET', $params = [])
    {
        $this->ensureAccessToken();

        $url = self::OAUTH2_SERVICE_URI . $endpoint;
        $method = strtoupper($method);

        $params['access_token'] = $this->token->access_token;
        return $this->httpRequest($url, $method, $params);
    }

    /**
     * Ensure a valid access token (fetch or refresh if needed)
     */
    protected function ensureAccessToken()
    {
        if (empty($this->token) || $this->isAccessTokenExpired()) {
            $this->fetchOrRefreshAccessToken();
        }
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function fetchOrRefreshAccessToken()
    {
        if (empty($this->token) || $this->isAccessTokenExpired()) {
            $this->fetchAccessToken();
        } else {
            $this->refreshAccessToken();
        }
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function fetchAccessToken()
    {
        $code = $this->contextController->getRequest()->getParam('code');

        if (empty($code)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Unable to retrieve access code.')
            );
        }

        $tokenParams = [
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->configHelper->getGoogleClientId(),
            'client_secret' => $this->configHelper->getGoogleSecret(),
            'grant_type' => 'authorization_code'
        ];

        $response = $this->httpRequest(self::OAUTH2_TOKEN_URI, 'POST', $tokenParams);
        $response->created = time();
        $this->token = $response;
    }


    /**
     * Refresh the access token
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function refreshAccessToken()
    {
        if (empty($this->token->refresh_token)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('No refresh token, unable to refresh access token.')
            );
        }

        $response = $this->httpRequest(
            self::OAUTH2_TOKEN_URI,
            'POST',
            [
                'client_id' => $this->configHelper->getGoogleClientId(),
                'client_secret' => $this->configHelper->getGoogleSecret(),
                'refresh_token' => $this->token->refresh_token,
                'grant_type' => 'refresh_token'
            ]
        );

        $this->token->access_token = $response->access_token;
        $this->token->expires_in = $response->expires_in;
        $this->token->created = time();
    }

    /**
     * Check access token expiry
     *
     * @return boolean
     */
    protected function isAccessTokenExpired()
    {
        // If the token is set to expire in the next 30 seconds.
        return ($this->token->created + ($this->token->expires_in - 30)) < time();
    }

    /**
     * @param $url
     * @param $method
     * @param $params
     * @return object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function httpRequest($url, $method = 'GET', $params = [])
    {
        $this->curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        $this->curl->setOption(CURLOPT_TIMEOUT, 60);

        switch ($method) {
            case 'GET':
                $this->curl->addHeader('Authorization', 'Bearer ' . $params['access_token']);
                $this->curl->get($url, $params);
                break;
            case 'POST':
                $this->curl->post($url, $params);
                break;
            case 'DELETE':
                break;
            default:
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Required HTTP method is not supported.')
                );
        }

        $response = $this->curl->getBody();
        $decodedResponse = json_decode($response, true);
        $status = $this->curl->getStatus();

        if ($status === 400 || $status === 401) {
            $message = $decodedResponse['error']['message'] ?? __('Unspecified OAuth error occurred.');
            throw new \Magento\Framework\Exception\LocalizedException(__($message));
        }

        return (object)$decodedResponse;
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
     * Set state
     *
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * Get access
     */
    public function getAccess()
    {
        return $this->access;
    }

    /**
     * Get Promt
     */
    public function getPrompt()
    {
        return $this->prompt;
    }

    /**
     * Set access token
     *
     * @param string $token
     */
    public function setAccessToken($token)
    {
        $this->token = $this->jsonSerializer->unserialize($token);
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        $this->ensureAccessToken();
        return $this->jsonSerializer->serialize($this->token);
    }
}
