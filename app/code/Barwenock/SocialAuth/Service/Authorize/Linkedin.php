<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Service\Authorize;

class Linkedin
{
    protected const REDIRECT_URI_ROUTE = 'socialauth/linkedin/authorize';
    protected const OAUTH2_SERVICE_URI = 'https://api.linkedin.com';
    protected const OAUTH2_AUTH_URI = 'https://www.linkedin.com/oauth/v2/authorization';
    protected const OAUTH2_TOKEN_URI = 'https://www.linkedin.com/oauth/v2/accessToken';

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
    protected $scope = ['openid', 'profile', 'email'];

    /**
     * Token
     */
    protected $token = null;

    /**
     * @var \Magento\Store\Model\Store
     */
    protected $store;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\Url
     */
    protected $url;

    /**
     * @var \Barwenock\SocialAuth\Helper\Adminhtml\Config
     */
    protected $configHelper;

    public function __construct(
        \Magento\Store\Model\Store $store,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Url $url,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper
    ) {
        $this->store = $store;
        $this->curl = $curl;
        $this->request = $request;
        $this->url = $url;
        $this->configHelper = $configHelper;
    }

    /**
     * Set parameters
     *
     * @param array $params contain params value
     */
    public function setParameters($params = [])
    {
        if (!$this->configHelper->getLinkedinStatus()) {
            return;
        }

        $this->clientId = $this->getClientId();
        $this->clientSecret = $this->getClientSecret();

        $isSecure = $this->store->isCurrentlySecure();
        $this->protocol = $isSecure ? "https" : "http";
        $this->redirectUri = $this->url->sessionUrlVar(
            $this->url->getUrl(self::REDIRECT_URI_ROUTE, ['_secure' => $isSecure])
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
            'display' => 'popup'
        ];

        return self::OAUTH2_AUTH_URI . '?' . http_build_query($queryParams);
    }

    /**
     * Get response from the api
     *
     * @param  string $endpoint endpoint url
     * @param  string $method   name of method
     * @param  array  $params   cotains param
     * @return object
     */
    public function api($endpoint, $method = 'GET', $params = [])
    {
        $url = self::OAUTH2_SERVICE_URI . $endpoint;
        $method = strtoupper($method);

        $params['oauth2_access_token'] = $this->getAccessToken();
        return $this->httpRequest($url, $method, $params);
    }

    /**
     * Fetch access token
     * @throws \Magento\Framework\Exception\LocalizedException
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
            'grant_type' => 'authorization_code'
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
                $this->curl->addHeader('Authorization', 'Bearer ' . $params['oauth2_access_token']);
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
    public function getClientId()
    {
        return $this->configHelper->getLinkedinClientId();
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->configHelper->getLinkedinSecret();
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
     * @param string $state user state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * Get Access token
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAccessToken()
    {
        if (empty($this->token)) {
            $this->fetchAccessToken();
        }
        return $this->token->access_token;
    }
}
