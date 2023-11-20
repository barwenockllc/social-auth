<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Service\Authorize;

class Linkedin extends \Barwenock\SocialAuth\Model\Service\Authorize\AbstractSocialAuth
{
    /**
     * @var string
     */
    protected const REDIRECT_URI_ROUTE = 'socialauth/linkedin/authorize';

    /**
     * @var string
     */
    protected const OAUTH2_SERVICE_URI = 'https://api.linkedin.com';

    /**
     * @var string
     */
    protected const OAUTH2_AUTH_URI = 'https://www.linkedin.com/oauth/v2/authorization';

    /**
     * @var string
     */
    protected const OAUTH2_TOKEN_URI = 'https://www.linkedin.com/oauth/v2/accessToken';

    /**
     * @return int
     */
    protected function getConfigStatus(): int
    {
        return $this->configHelper->getLinkedinStatus();
    }

    /**
     * @return string
     */
    protected function getClientIdConfig(): string
    {
        return 'getLinkedinClientId';
    }

    /**
     * @return string
     */
    protected function getClientSecretConfig(): string
    {
        return 'getLinkedinSecret';
    }

    /**
     * @return array
     */
    protected function createRequestSpecificParams(): array
    {
        return [];
    }

    /**
     * Get the default scope for LinkedIn.
     *
     * @return array
     */
    protected function getDefaultScope(): array
    {
        return ['openid', 'profile', 'email'];
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function fetchAccessTokenSpecific()
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
     * @return bool
     */
    protected function isAccessTokenExpired(): bool
    {
        return false;
    }

    /**
     * @return void
     */
    protected function refreshAccessToken()
    {
        // Implement refresh logic if needed
    }

    /**
     * @param $method
     * @param $params
     * @return string[]
     */
    protected function getSpecificHttpRequestParams($method, $params): array
    {
        return [
            'Authorization' => 'Bearer ' . $params['access_token'],
            'Connection' => 'Keep-Alive'
        ];
    }

    /**
     * Get the scope separator for Facebook.
     *
     * @return string
     */
    protected function getScopeSeparator(): string
    {
        return ',';
    }
}
