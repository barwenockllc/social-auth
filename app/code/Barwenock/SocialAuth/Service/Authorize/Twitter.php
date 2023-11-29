<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Service\Authorize;

class Twitter
{
    /**
     * Request Token URL
     *
     * @var string
     */
    protected const REQUEST_TOKEN_URL = 'https://api.twitter.com/oauth/request_token';

    /**
     * Access Token URL
     *
     * @var string
     */
    protected const ACCESS_TOKEN_URL = 'https://api.twitter.com/oauth/access_token';

    /**
     * Authenticate URL
     *
     * @var string
     */
    protected const AUTHENTICATE_URL = 'https://api.twitter.com/oauth/authenticate';

    /**
     * Account Verify URL
     *
     * @var string
     */
    protected const ACCOUNT_VERIFY_URL = 'https://api.twitter.com/1.1/account/verify_credentials.json';

    /**
     * Redirect Route URL
     *
     * @var string
     */
    protected const REDIRECT_URI_ROUTE = 'socialauth/twitter/authorize';

    /**
     * @var \Magento\Framework\Url
     */
    protected $url;

    /**
     * @var \Barwenock\SocialAuth\Helper\Adminhtml\Config
     */
    protected $configHelper;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @param \Magento\Framework\Url $url
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function __construct(
        \Magento\Framework\Url $url,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        $this->url = $url;
        $this->configHelper = $configHelper;
        $this->curl = $curl;
    }

    /**
     * Retrieves the request token from Twitter for the OAuth authorization process
     *
     * @return array
     */
    public function getRequestToken()
    {
        $requestTokenUrl = self::REQUEST_TOKEN_URL;
        $additionalParams = [
            'oauth_callback' => $this->url->getUrl(self::REDIRECT_URI_ROUTE, ['_secure' => true])
        ];

        $authHeader = $this->authorization($additionalParams, self::REQUEST_TOKEN_URL, 'POST');

        $this->curl->addHeader('Authorization', $authHeader);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);

        $this->curl->post($requestTokenUrl, []);

        $response = $this->curl->getBody();

        // Parse the response to extract the access token and secret
        parse_str($response, $accessTokenInfo);
        return $accessTokenInfo;
    }

    /**
     * Retrieves the access token and token secret from Twitter using the provided OAuth token and verifier.
     *
     * @param string $oauthToken
     * @param string $oauthVerifier
     * @return array
     */
    public function getAccessToken($oauthToken, $oauthVerifier)
    {
        $accessTokenUrl = self::ACCESS_TOKEN_URL;
        $additionalParams = ['oauth_token' => $oauthToken, 'oauth_verifier' => $oauthVerifier];

        $authHeader = $this->authorization($additionalParams, $accessTokenUrl, 'POST');

        // Prepare the cURL request
        $this->curl->addHeader('Authorization', $authHeader);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);

        $this->curl->post($accessTokenUrl, []);

        $response = $this->curl->getBody();

        // Parse the response to extract the access token and secret
        parse_str($response, $accessTokenInfo);
        return $accessTokenInfo;
    }

    /**
     * Retrieves user information from Twitter using the provided OAuth token and token secret
     *
     * @param string $oauthToken
     * @param string $oauthTokenSecret
     * @return mixed
     */
    public function getUserInfo($oauthToken, $oauthTokenSecret)
    {
        $accessTokenUrl = self::ACCOUNT_VERIFY_URL;
        $additionalParams = ['include_email' => 'true', 'oauth_token' => $oauthToken];

        $authHeader = $this->authorization($additionalParams, $accessTokenUrl, 'GET', $oauthTokenSecret);

        $this->curl->addHeader('Authorization', $authHeader);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->get($accessTokenUrl . '?' . http_build_query(['include_email' => 'true']));

        $response = $this->curl->getBody();
        return json_decode($response, true);
    }

    /**
     * Generates the OAuth authorization header for Twitter API requests
     *
     * @param array $additionalParams
     * @param string $url
     * @param string $method
     * @param string|null $oauthTokenSecret
     * @return string
     */
    protected function authorization($additionalParams, $url, $method = 'GET', $oauthTokenSecret = null): string
    {
        $oauthParams = [
            'oauth_consumer_key' => $this->configHelper->getTwitterConsumerKey(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_nonce' => uniqid(),
            'oauth_version' => '1.0',
        ];

        $params = array_merge($oauthParams, $additionalParams);

        ksort($params);

        // Construct the base string
        $baseString = sprintf('%s&', $method) . rawurlencode($url) . '&';
        $baseString .= rawurlencode(http_build_query($params));

        // Construct the signing key
        $signingKey = rawurlencode($this->configHelper->getTwitterConsumerSecretKey()) . '&' . $oauthTokenSecret;

        // Calculate the signature
        $params['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        // Build the Authorization header
        return 'OAuth ' . http_build_query($params, '', ', ');
    }

    /**
     * Creates the request URL for initiating the Twitter authentication process
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createRequestUrl(): string
    {
        $token = $this->getRequestToken();

        if (!isset($token['oauth_token'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not fetch access token for Twitter')
            );
        }

        $queryParams = ['oauth_token' => $token['oauth_token']];
        return self::AUTHENTICATE_URL . '?' . http_build_query($queryParams);
    }
}
