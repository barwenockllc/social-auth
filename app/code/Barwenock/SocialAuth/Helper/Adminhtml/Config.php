<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Helper\Adminhtml;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        parent::__construct($context);
    }

    /**
     * Get module status
     *
     * @return int
     */
    public function getModuleStatus()
    {
        return $this->scopeConfig->getValue(
            'socialauth/socialauth/status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get social display on configuration
     *
     * @return mixed
     */
    public function getSocialDisplayOn()
    {
        return $this->scopeConfig->getValue(
            'socialauth/socialauth/display_on',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get social top text configuration
     *
     * @return string
     */
    public function getSocialTopText()
    {
        return $this->scopeConfig->getValue(
            'socialauth/socialauth/text_top',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get subscription status
     *
     * @return mixed
     */
    public function getSubscriptionStatus()
    {
        return $this->scopeConfig->getValue(
            'socialauth/socialauth/subscription',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get authorization redirect configuration
     *
     * @return mixed
     */
    public function getAuthorizationRedirect()
    {
        return $this->scopeConfig->getValue(
            'socialauth/socialauth/authorization_redirect',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get authorize redirect url
     *
     * @return mixed
     */
    public function getAuthorizeRedirectUrl()
    {
        return $this->scopeConfig->getValue(
            'socialauth/socialauth/authorize_redirect_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Google status
     *
     * @return int
     */
    public function getGoogleStatus(): int
    {
        return (int) $this->scopeConfig->getValue(
            'socialauth/google_config/status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get LinkedIn status
     *
     * @return int
     */
    public function getLinkedinStatus(): int
    {
        return (int) $this->scopeConfig->getValue(
            'socialauth/linkedin_config/status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Instagram status
     *
     * @return int
     */
    public function getInstagramStatus(): int
    {
        return (int) $this->scopeConfig->getValue(
            'socialauth/instagram_config/status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Instagram client id
     *
     * @return string
     */
    public function getInstagramClientId()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(
            'socialauth/instagram_config/client_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }

    /**
     * Get an Instagram secret key
     *
     * @return string
     */
    public function getInstagramSecretKey()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(
            'socialauth/instagram_config/client_secret',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }

    /**
     * Get social connect image
     *
     * @param string $type
     * @return mixed
     */
    public function getSocialConnectImage($type)
    {
        $configPath = sprintf('socialauth/%s_config/icon_login', $type);
        return $this->scopeConfig->getValue($configPath, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Facebook status
     *
     * @return int
     */
    public function getFacebookStatus(): int
    {
        return (int) $this->scopeConfig->getValue(
            'socialauth/facebook_config/status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Facebook app id
     *
     * @return string
     */
    public function getFacebookAppId()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(
            'socialauth/facebook_config/application_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }

    /**
     * Get Facebook app secret
     *
     * @return string
     */
    public function getFacebookAppSecret()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(
            'socialauth/facebook_config/application_secret',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }

    /**
     * Get Google client id
     *
     * @return string
     */
    public function getGoogleClientId()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(
            'socialauth/google_config/client_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }

    /**
     * Get Google client secret
     *
     * @return string
     */
    public function getGoogleSecret()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(
            'socialauth/google_config/client_secret',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }

    /**
     * Get LinkedIn client id
     *
     * @return string
     */
    public function getLinkedinClientId()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(
            'socialauth/linkedin_config/client_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }

    /**
     * Get LinkedIn client secret
     *
     * @return string
     */
    public function getLinkedinSecret()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(
            'socialauth/linkedin_config/client_secret',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }

    /**
     * Get Twitter status
     *
     * @return int
     */
    public function getTwitterStatus(): int
    {
        return (int) $this->scopeConfig->getValue(
            'socialauth/twitter_config/status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get a Twitter consumer key
     *
     * @return string
     */
    public function getTwitterConsumerKey()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(
            'socialauth/twitter_config/consumer_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }

    /**
     * Get a Twitter consumer secret key
     *
     * @return string
     */
    public function getTwitterConsumerSecretKey()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(
            'socialauth/twitter_config/consumer_secret_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }
}
