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
     * Gets the configuration status for LinkedIn
     *
     * @return int
     */
    protected function getConfigStatus(): int
    {
        return $this->configHelper->getLinkedinStatus();
    }

    /**
     * Gets the configuration value for the LinkedIn client ID
     *
     * @return string
     */
    protected function getClientIdConfig(): string
    {
        return 'getLinkedinClientId';
    }

    /**
     * Gets the configuration value for the LinkedIn client secret
     *
     * @return string
     */
    protected function getClientSecretConfig(): string
    {
        return 'getLinkedinSecret';
    }

    /**
     * Creates and returns specific parameters for the request
     *
     * @return array
     */
    protected function createRequestSpecificParams(): array
    {
        return [];
    }

    /**
     * Get the default scope for Instagram
     *
     * @return array
     */
    protected function getDefaultScope(): array
    {
        return ['openid', 'profile', 'email'];
    }

    /**
     * Fetches the access token using the provided authorization code
     *
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
     * Checks if the access token has expired
     *
     * @return bool
     */
    protected function isAccessTokenExpired(): bool
    {
        return false;
    }

    /**
     * Refreshes the access token using the stored refresh token
     *
     * @return bool
     */
    protected function refreshAccessToken()
    {
        return true;
    }

    /**
     * Get specific HTTP request parameters for the given method
     *
     * @param string $method
     * @param array $params
     * @return array
     */
    protected function getSpecificHttpRequestParams($method, $params): array
    {
        return [
            'Authorization' => 'Bearer ' . $params['access_token'],
            'Connection' => 'Keep-Alive'
        ];
    }

    /**
     * Get the scope separator for Facebook
     *
     * @return string
     */
    protected function getScopeSeparator(): string
    {
        return ',';
    }
}
