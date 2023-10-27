<?php

namespace Barwenock\SocialAuth\Service\Authorize;

class Twitter
{
    protected const REQUEST_TOKEN_URL = 'https://api.twitter.com/oauth/request_token';
    protected const ACCESS_TOKEN_URL = 'https://api.twitter.com/oauth/access_token';
    protected const AUTHENTICATE_URL = 'https://api.twitter.com/oauth/authenticate';
    protected const ACCOUNT_VERIFY_URL = 'https://api.twitter.com/1.1/account/verify_credentials.json';
    protected const REDIRECT_URI_ROUTE = 'socialauth/twitter/connect';

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
     * @param $oauthToken
     * @param $oauthVerifier
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
     * @param $oauthToken
     * @param $oauthTokenSecret
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
     * @param $additionalParams
     * @param $url
     * @param $method
     * @param $oauthTokenSecret
     * @return string
     */
    protected function authorization($additionalParams, $url, $method = 'GET', $oauthTokenSecret = null)
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
        $baseString = "$method&" . rawurlencode($url) . '&';
        $baseString .= rawurlencode(http_build_query($params));

        // Construct the signing key
        $signingKey = rawurlencode($this->configHelper->getTwitterConsumerSecretKey()) . '&' . $oauthTokenSecret;

        // Calculate the signature
        $params['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        // Build the Authorization header
        return 'OAuth ' . http_build_query($params, '', ', ');
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function createRequestUrl()
    {
        $token = $this->getRequestToken();

        if (!isset($token['oauth_token'])) {
            throw new \Exception('Could not fetch access token for Twitter');
        }

        $queryParams = ['oauth_token' => $token['oauth_token']];
        return self::AUTHENTICATE_URL . '?' . http_build_query($queryParams);
    }
}
