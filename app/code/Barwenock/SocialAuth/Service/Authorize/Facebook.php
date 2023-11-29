<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

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
     * Retrieves user data from Facebook using the provided URL
     *
     * @param string $url
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
        } catch (\Magento\Framework\Exception\LocalizedException $localizedException) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($localizedException->getMessage()),
                $localizedException->getCode(),
                $localizedException
            );
        }
    }

    /**
     * Builds the URL for retrieving user data from Facebook
     *
     * @param array $cookie
     * @param string $facebookAppSecretKey
     * @return string
     */
    public function buildUserDataUrl($cookie, $facebookAppSecretKey): string
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
     * Retrieves a new Facebook cookie using the provided app ID and app secret
     *
     * @param string $appId
     * @param string $appSecret
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
                $base = sprintf('https://graph.facebook.com/v4.0/oauth/access_token?client_id=%s', $appId);
                $signedCode = $signedRequest['code'];

                $accessTokenResponse = $this
                    ->getFacebookUserData(sprintf(
                        '%s&redirect_uri=&client_secret=%s&code=%s',
                        $base,
                        $appSecret,
                        $signedCode
                    ));

                if (!empty($accessTokenResponse['access_token'])) {
                    $signedRequest['access_token'] = $accessTokenResponse['access_token'];
                    $signedRequest['expires'] = time() + $accessTokenResponse['expires_in'];
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $localizedException) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($localizedException->getMessage()),
                $localizedException->getCode(),
                $localizedException
            );
        }

        return $signedRequest;
    }

    /**
     * Retrieves an old Facebook cookie using the provided app ID and app secret
     *
     * @param string $appId
     * @param string $appSecret
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
                $payload = http_build_query($args, '', null, PHP_QUERY_RFC3986);

                // Calculate the encrypted data using the payload and app secret
                $encryptedData = hash('sha256', $payload . $appSecret);

                // Compare the calculated signature with the one from the cookie
                if ($encryptedData === $signature) {
                    return $args;
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $localizedException) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($localizedException->getMessage()),
                $localizedException->getCode(),
                $localizedException
            );
        }

        return [];
    }

    /**
     * Parses a signed Facebook request and verifies its integrity using the provided secret
     *
     * @param string $signedRequest
     * @param string $secret
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
        } catch (\Magento\Framework\Exception\LocalizedException $localizedException) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($localizedException->getMessage()),
                $localizedException->getCode(),
                $localizedException
            );
        }
    }

    /**
     * Decodes a base64url-encoded string
     *
     * @param string $input
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function base64UrlDecode($input)
    {
        try {
            return $this->urlDecoder->decode(strtr($input, '-_', '+/'));
        } catch (\Magento\Framework\Exception\LocalizedException $localizedException) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($localizedException->getMessage()),
                $localizedException->getCode(),
                $localizedException
            );
        }
    }
}
