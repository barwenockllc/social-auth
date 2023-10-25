<?php

namespace Barwenock\SocialAuth\Service\Authorize;

class Twitter
{
    protected const ACCESS_TOKEN_URL = 'https://api.twitter.com/oauth/request_token';
    protected const REDIRECT_URI_ROUTE = 'socialauth/twitter/connect';
    public function __construct(
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Magento\Framework\Url $url,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        $this->url = $url;
        $this->configHelper = $configHelper;
        $this->curl = $curl;
    }

    public function getOauthToken()
    {
        $accessTokenUrl = self::ACCESS_TOKEN_URL;
        $callbackUrl = $this->url->getUrl(self::REDIRECT_URI_ROUTE, ['_secure' => true]);
        $oauthParams = [
            'oauth_callback' => $callbackUrl,
            'oauth_consumer_key' => $this->configHelper->getTwitterConsumerKey(),
            'oauth_nonce' => md5(uniqid()),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0',
        ];

        // Sort the parameters alphabetically
        ksort($oauthParams);

        // Construct the base string
        $baseString = 'POST&' . rawurlencode($accessTokenUrl) . '&';
        $baseString .= rawurlencode(http_build_query($oauthParams));

        // Construct the signing key
        $signingKey = rawurlencode($this->configHelper->getTwitterConsumerSecretKey()) . '&';

        // Calculate the signature
        $oauthParams['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        // Build the Authorization header
        $authHeader = 'OAuth ' . http_build_query($oauthParams, '', ', ');

        $ch = curl_init($accessTokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $authHeader]);

        // Execute the cURL request
        $response = curl_exec($ch);

        // Check for errors
        if ($response === false) {
            return ['error' => 'cURL Error: ' . curl_error($ch)];
        }

        // Parse the response to extract the access token and secret
        parse_str($response, $accessTokenInfo);

        // Close the cURL session
        curl_close($ch);

        return $accessTokenInfo;
    }

    function getAccessToken($oauthToken, $oauthVerifier)
    {
        $accessTokenUrl = 'https://api.twitter.com/oauth/access_token';
        // Prepare the OAuth parameters for the access token request
        $oauthParams = [
            'oauth_consumer_key' => $this->configHelper->getTwitterConsumerKey(),
            'oauth_token' => $oauthToken,
            'oauth_verifier' => $oauthVerifier,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_nonce' => uniqid(),
            'oauth_version' => '1.0',
        ];

        // Sort the parameters alphabetically
        ksort($oauthParams);

        // Construct the base string
        $baseString = 'POST&' . rawurlencode($accessTokenUrl) . '&';
        $baseString .= rawurlencode(http_build_query($oauthParams));

        // Construct the signing key
        $signingKey = rawurlencode($this->configHelper->getTwitterConsumerSecretKey()) . '&';

        // Calculate the signature
        $oauthParams['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        // Build the Authorization header
        $authHeader = 'OAuth ' . http_build_query($oauthParams, '', ', ');

        // Prepare the cURL request
        $ch = curl_init($accessTokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $authHeader]);

        // Execute the cURL request
        $response = curl_exec($ch);

        // Check for errors
        if ($response === false) {
            return ['error' => 'cURL Error: ' . curl_error($ch)];
        }

        // Parse the response to extract the access token and secret
        parse_str($response, $accessTokenInfo);

        // Close the cURL session
        curl_close($ch);

        return $accessTokenInfo;
    }

    public function getUserInfo($oauthToken, $oauthTokenSecret)
    {
        $accessTokenUrl = 'https://api.twitter.com/1.1/account/verify_credentials.json';

        $oauthParams = [
            'oauth_consumer_key' => $this->configHelper->getTwitterConsumerKey(),
            'oauth_token' => $oauthToken,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_nonce' => uniqid(),
            'oauth_version' => '1.0',
        ];

        // Sort the parameters alphabetically
        ksort($oauthParams);

        // Construct the base string
        $baseString = 'GET&' . rawurlencode($accessTokenUrl) . '&';
        $baseString .= rawurlencode(http_build_query($oauthParams));

        // Construct the signing key
        $signingKey = rawurlencode($this->configHelper->getTwitterConsumerSecretKey()) . '&' . $oauthTokenSecret;

        // Calculate the signature
        $oauthParams['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        // Build the Authorization header
        $authHeader = 'OAuth ' . http_build_query($oauthParams, '', ', ');

        $ch = curl_init($accessTokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $authHeader]);

        // Execute the cURL request
        $response = curl_exec($ch);

        // Check for errors
        if ($response === false) {
            return ['error' => 'cURL Error: ' . curl_error($ch)];
        }

        // Parse the response to extract the access token and secret

        // Close the cURL session
        curl_close($ch);

        return json_decode($response, true);
    }
}
