<?php

namespace Barwenock\SocialAuth\Service\Authorize;

class Twitter
{
    protected const REDIRECT_URI_ROUTE = 'socailauth/twitter/connect';
    public function __construct(
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Magento\Framework\Url $url
    ) {
        $this->url = $url;
        $this->configHelper = $configHelper;
    }

    public function getOauthToken()
    {
        $callbackUrl = $this->url->getUrl(self::REDIRECT_URI_ROUTE, ['_secure' => true]);
        $oauthParams = [
            'oauth_callback' => $callbackUrl,
            'oauth_consumer_key' => $this->configHelper->getTwitterConsumerKey(),
            'oauth_nonce' => md5(uniqid()),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0',
        ];


        $baseURI = 'https://api.twitter.com/oauth/request_token';
        $baseString = $this->buildBaseString($baseURI, $oauthParams); // build the base string


        $consumerSecret = $this->configHelper->getTwitterConsumerSecretKey();
        $compositeKey = $this->getCompositeKey($consumerSecret); // first request, no request token yet
        $oauthParams['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $compositeKey, true));

        return $this->sendRequest($oauthParams, $baseURI);
    }

    protected function buildBaseString($baseURI, $oauthParams)
    {
        $baseStringParts = [];
        ksort($oauthParams);

        foreach ($oauthParams as $key => $value) {
            $baseStringParts[] = "$key=" . rawurlencode($value);
        }

        return 'POST&' . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $baseStringParts));
    }

    protected function sendRequest($oauthParams, $baseURI)
    {
        $header = [$this->buildAuthorizationHeader($oauthParams)];

        $options = [
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_HEADER => false,
            CURLOPT_URL => $baseURI,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

    protected function buildAuthorizationHeader($oauthParams)
    {
        $authHeader = 'Authorization: OAuth ';
        $values = [];

        foreach ($oauthParams as $key => $value) {
            $values[] = "$key=\"" . rawurlencode($value) . "\"";
        }

        $authHeader .= implode(', ', $values);
        return $authHeader;
    }

    protected function getCompositeKey($consumerSecret)
    {
        return rawurlencode($consumerSecret) . '&';
    }
}
