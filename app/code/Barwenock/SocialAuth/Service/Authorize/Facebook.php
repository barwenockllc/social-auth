<?php

namespace Barwenock\SocialAuth\Service\Authorize;

class Facebook
{
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $json;

    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    protected $urlDecoder;

    /**
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param \Magento\Framework\Url\DecoderInterface $urlDecoder
     */
    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\Url\DecoderInterface $urlDecoder
    ) {
        $this->curl = $curl;
        $this->cookieManager = $cookieManager;
        $this->json = $json;
        $this->urlDecoder = $urlDecoder;
    }

    /**
     * @param $url
     * @return array
     * @throws \Exception
     */
    public function getFacebookUserData($url)
    {
        try {
            $this->curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            $this->curl->get($url);

            $response = $this->curl->getBody();
            return $this->json->unserialize($response);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @param $cookie
     * @param $facebookAppSecretKey
     * @return string
     */
    public function buildUserDataUrl($cookie, $facebookAppSecretKey)
    {
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


        return $token . $queryParams;
    }

    /**
     * @param $appId
     * @param $appSecret
     * @return array|bool|float|int|mixed|string
     * @throws \Exception
     */
    public function getNewFacebookCookie($appId, $appSecret)
    {
        $signedRequest = [];
        try {
            $cookieData = $this->cookieManager->getCookie('fbsr_' . $appId);
            $signedRequest = $this->parseSignedRequest($cookieData, $appSecret);

            if (!empty($signedRequest)) {
                $base = "https://graph.facebook.com/v4.0/oauth/access_token?client_id=$appId";
                $signedCode = $signedRequest['code'];

                $accessTokenResponse = $this
                    ->getFacebookUserData("$base&redirect_uri=&client_secret=$appSecret&code=$signedCode");

                if (!empty($accessTokenResponse['access_token'])) {
                    $signedRequest['access_token'] = $accessTokenResponse['access_token'];
                    $signedRequest['expires'] = time() + $accessTokenResponse['expires_in'];
                }
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
        return $signedRequest;
    }

    /**
     * @param $appId
     * @param $appSecret
     * @return array
     * @throws \Exception
     */
    public function getOldFacebookCookie($appId, $appSecret)
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
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

        return [];
    }

    /**
     * @param $signedRequest
     * @param $secret
     * @return array|bool|float|int|mixed|string|null
     * @throws \Exception
     */
    protected function parseSignedRequest($signedRequest, $secret)
    {
        try {
            list($encodedSig, $payload) = explode('.', $signedRequest, 2);

            $sig = $this->base64UrlDecode($encodedSig);
            $data = $this->json->unserialize($this->base64UrlDecode($payload));

            if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
                return null;
            }

            $expectedSig = hash_hmac('sha256', $payload, $secret, true);
            if ($sig !== $expectedSig) {
                return null;
            }
            return $data;
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @param $input
     * @return string
     * @throws \Exception
     */
    protected function base64UrlDecode($input)
    {
        try {
            return $this->urlDecoder->decode(strtr($input, '-_', '+/'));
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }
}
