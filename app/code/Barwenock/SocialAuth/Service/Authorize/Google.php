<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Service\Authorize;

class Google extends \Barwenock\SocialAuth\Model\Service\Authorize\AbstractSocialAuth
{
    /**
     * @var string
     */
    protected const REDIRECT_URI_ROUTE = 'socialauth/google/authorize';

    /**
     * @var string
     */
    protected const OAUTH2_TOKEN_URI = 'https://accounts.google.com/o/oauth2/token';

    /**
     * @var string
     */
    protected const OAUTH2_AUTH_URI = 'https://accounts.google.com/o/oauth2/auth';

    /**
     * @var string
     */
    protected const OAUTH2_SERVICE_URI = 'https://www.googleapis.com/oauth2/v2';

    /**
     * @var string[]
     */
    protected $scope = [
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
    ];

    /**
     * @var string
     */
    protected $access = 'offline';

    /**
     * @var string
     */
    protected $prompt = 'auto';

    /**
     * Gets the configuration status for Google
     *
     * @return int
     */
    protected function getConfigStatus(): int
    {
        return $this->configHelper->getGoogleStatus();
    }

    /**
     * Gets the configuration value for the Google client ID
     *
     * @return string
     */
    protected function getClientIdConfig(): string
    {
        return 'getGoogleClientId';
    }

    /**
     * Gets the configuration value for the Google client secret
     *
     * @return string
     */
    protected function getClientSecretConfig(): string
    {
        return 'getGoogleSecret';
    }

    /**
     * Creates and returns specific parameters for the request
     *
     * @return array
     */
    protected function createRequestSpecificParams(): array
    {
        return [
            'access_type' => $this->access,
            'approvalprompt' => $this->prompt
        ];
    }

    /**
     * Get the default scope for Google.
     *
     * @return array
     */
    protected function getDefaultScope(): array
    {
        return ['https://www.googleapis.com/auth/userinfo.profile', 'https://www.googleapis.com/auth/userinfo.email'];
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

        $response = $this->httpRequest(self::OAUTH2_TOKEN_URI, 'POST', $tokenParams);
        $response->created = time();
        $this->token = $response;
    }

    /**
     * Checks if the access token has expired
     *
     * @return bool
     */
    protected function isAccessTokenExpired(): bool
    {
        return ($this->token->created + ($this->token->expires_in - 30)) < time();
    }

    /**
     * Refreshes the access token using the stored refresh token
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function refreshAccessToken()
    {
        if (empty($this->token->refresh_token)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('No refresh token, unable to refresh access token.')
            );
        }

        $response = $this->httpRequest(
            self::OAUTH2_TOKEN_URI,
            'POST',
            [
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'refresh_token' => $this->token->refresh_token,
                'grant_type' => 'refresh_token'
            ]
        );

        $this->token->access_token = $response->access_token;
        $this->token->expires_in = $response->expires_in;
        $this->token->created = time();
    }

    /**
     * Get specific HTTP request parameters for the given method
     *
     * @param string $method
     * @param array $params
     * @return string[]
     */
    protected function getSpecificHttpRequestParams($method, $params): array
    {
        return [
            'Authorization' => 'Bearer ' . $params['access_token']
        ];
    }

    /**
     * Get the scope separator for Google
     *
     * @return string
     */
    protected function getScopeSeparator(): string
    {
        return ' ';
    }
}
