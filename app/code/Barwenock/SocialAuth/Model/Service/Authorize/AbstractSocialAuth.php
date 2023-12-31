<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Model\Service\Authorize;

abstract class AbstractSocialAuth
{
    /**
     * @var string
     */
    protected const REDIRECT_URI_ROUTE = '';

    /**
     * @var string
     */
    protected const OAUTH2_SERVICE_URI = '';

    /**
     * @var string
     */
    protected const OAUTH2_AUTH_URI = '';

    /**
     * @var string
     */
    protected const OAUTH2_TOKEN_URI = '';

    /**
     * @var null
     */
    protected $redirectUri = null;

    /**
     * @var string
     */
    protected $state = '';

    /**
     * @var mixed
     */
    protected $scope;

    /**
     * @var null
     */
    protected $token = null;

    /**
     * @var string
     */
    protected $protocol;

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
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

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
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Url $url
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     */
    public function __construct(
        \Magento\Store\Model\Store $store,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Url $url,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
    ) {
        $this->store = $store;
        $this->curl = $curl;
        $this->request = $request;
        $this->url = $url;
        $this->configHelper = $configHelper;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Get config social status
     *
     * @return mixed
     */
    abstract protected function getConfigStatus();

    /**
     * Get a client id config name
     *
     * @return string
     */
    abstract protected function getClientIdConfig(): string;

    /**
     * Get client secret config name
     *
     * @return string
     */
    abstract protected function getClientSecretConfig(): string;

    /**
     * Create request specific params
     *
     * @return array
     */
    abstract protected function createRequestSpecificParams(): array;

    /**
     * Fetches the access token based on the authorization code received
     *
     * @return mixed
     */
    abstract protected function fetchAccessTokenSpecific();

    /**
     * Checks if the access token has expired
     *
     * @return bool
     */
    abstract protected function isAccessTokenExpired(): bool;

    /**
     * Refresh access token
     *
     * @return mixed
     */
    abstract protected function refreshAccessToken();

    /**
     * Retrieves specific HTTP request parameters for the given method parameters
     *
     * @param string $method
     * @param array $params
     * @return array
     */
    abstract protected function getSpecificHttpRequestParams($method, $params): array;

    /**
     * Get the default scope for the specific social authentication class.
     *
     * @return array
     */
    abstract protected function getDefaultScope(): array;

    /**
     * Get the scope separator for the specific social authentication class.
     *
     * @return string
     */
    abstract protected function getScopeSeparator(): string;

    /**
     * Sets parameters for service based on the provided values
     *
     * @param array $params
     * @return void
     */
    public function setParameters($params = [])
    {
        if ($this->getConfigStatus() == 0) {
            return;
        }

        $isSecure = $this->store->isCurrentlySecure();
        $this->protocol = $isSecure ? "https" : "http";
        $this->redirectUri = $this->url->sessionUrlVar(
            $this->url->getUrl(static::REDIRECT_URI_ROUTE, ['_secure' => $isSecure])
        );

        $this->scope = $params['scope'] ?? $this->getDefaultScope();
        $this->state = $params['state'] ?? $this->getState();
    }

    /**
     * Creates the authorization request URL for initiating the OAuth 2.0 authentication process
     *
     * @return string
     */
    public function createRequestUrl(): string
    {
        $queryParams = [
            'response_type' => 'code',
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUri(),
            'state' => $this->getState(),
            'scope' => implode($this->getScopeSeparator(), $this->getScope()),
            'display' => 'popup'
        ];

        $specificParams = $this->createRequestSpecificParams();

        return static::OAUTH2_AUTH_URI . '?' . http_build_query(array_merge($queryParams, $specificParams));
    }

    /**
     * Perform an API request using OAuth 2.0 authentication
     *
     * @param string $endpoint
     * @param string $method
     * @param array $params
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function api($endpoint, $method = 'GET', $params = [])
    {
        $url = static::OAUTH2_SERVICE_URI . $endpoint;
        $method = strtoupper($method);

        $params = array_merge($this->getSpecificAccessTokenParams(), $params);

        return $this->httpRequest($url, $method, $params);
    }

    /**
     * Perform an HTTP request using cURL, handling different HTTP methods
     *
     * @param string $url
     * @param string $method
     * @param array $params
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function httpRequest($url, $method = 'GET', $params = [])
    {
        $this->curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        $this->curl->setOption(CURLOPT_TIMEOUT, 60);

        switch ($method) {
            case 'GET':
                $httpRequestParams = $this->getSpecificHttpRequestParams($method, $params);
                foreach ($httpRequestParams as $key => $value) {
                    $this->curl->addHeader($key, $value);
                }

                $this->curl->get($url);
                break;
            case 'POST':
                $this->curl->post($url, $params);
                break;
            case 'DELETE':
                $this->curl->delete($url, $params);
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
     * Get client ID from the configuration
     *
     * @return mixed
     */
    public function getClientId()
    {
        return $this->configHelper->{$this->getClientIdConfig()}();
    }

    /**
     * Get client secret from the configuration
     *
     * @return mixed
     */
    public function getClientSecret()
    {
        return $this->configHelper->{$this->getClientSecretConfig()}();
    }

    /**
     * Get redirect uri
     *
     * @return mixed|null
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * Get scope
     *
     * @return array|mixed
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Get state
     *
     * @return mixed|string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return void
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * Retrieve access token, refreshing it if expired
     *
     * @return mixed
     */
    public function getAccessToken()
    {
        if (empty($this->token)) {
            $this->fetchAccessToken();
        } elseif ($this->isAccessTokenExpired()) {
            $this->refreshAccessToken();
        }

        return $this->token;
    }

    /**
     * Fetch access token
     *
     * @return void
     */
    protected function fetchAccessToken()
    {
        $this->fetchAccessTokenSpecific();
    }

    /**
     * Get specific parameters required for API requests using access token
     *
     * @return array
     */
    public function getSpecificAccessTokenParams()
    {
        $token = $this->getAccessToken();
        return ['access_token' => $token->access_token];
    }
}
