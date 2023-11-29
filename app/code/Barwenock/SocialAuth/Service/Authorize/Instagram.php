<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Service\Authorize;

class Instagram extends \Barwenock\SocialAuth\Model\Service\Authorize\AbstractSocialAuth
{
    /**
     * @var string
     */
    protected const REDIRECT_URI_ROUTE = 'socialauth/instagram/authorize';

    /**
     * @var string
     */
    protected const OAUTH2_SERVICE_URI = 'https://graph.instagram.com/';

    /**
     * @var string
     */
    protected const OAUTH2_AUTH_URI = 'https://api.instagram.com/oauth/authorize';

    /**
     * @var string
     */
    protected const OAUTH2_TOKEN_URI = 'https://api.instagram.com/oauth/access_token';

    /**
     * Gets the configuration status for Instagram
     *
     * @return int
     */
    protected function getConfigStatus(): int
    {
        return $this->configHelper->getInstagramStatus();
    }

    /**
     * Gets the configuration value for the Instagram client ID
     *
     * @return string
     */
    protected function getClientIdConfig(): string
    {
        return 'getInstagramClientId';
    }

    /**
     * Gets the configuration value for the Instagram client secret
     *
     * @return string
     */
    protected function getClientSecretConfig(): string
    {
        return 'getInstagramSecretKey';
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
     * Get the default scope for Instagram.
     *
     * @return array
     */
    protected function getDefaultScope(): array
    {
        return ['user_profile'];
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
            'grant_type' => 'authorization_code',
        ];

        $endPointResponse = $this->httpRequest(self::OAUTH2_TOKEN_URI, 'POST', $tokenParams);
        $this->token = $endPointResponse;
    }

    /**
     *  Checks if the access token has expired
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
        return [];
    }

    /**
     * Get the scope separator for Instagram.
     *
     * @return string
     */
    protected function getScopeSeparator(): string
    {
        return ',';
    }
}
