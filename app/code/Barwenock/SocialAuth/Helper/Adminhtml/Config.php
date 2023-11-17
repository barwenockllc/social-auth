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
     * @return int
     */
    public function getGoogleStatus()
    {
        return (int) $this->scopeConfig->getValue(
            'socialauth/google_config/status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return int
     */
    public function getLinkedinStatus()
    {
        return (int) $this->scopeConfig->getValue(
            'socialauth/linkedin_config/status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return int
     */
    public function getInstagramStatus()
    {
        return (int) $this->scopeConfig->getValue(
            'socialauth/instagram_config/status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
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
     * @param $type
     * @return mixed
     */
    public function getSocialConnectImage($type)
    {
        $configPath = sprintf('socialauth/%s_config/icon_login', $type);
        return $this->scopeConfig->getValue($configPath, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return int
     */
    public function getFacebookStatus()
    {
        return (int) $this->scopeConfig->getValue(
            'socialauth/facebook_config/status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
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
     * @return int
     */
    public function getTwitterStatus()
    {
        return (int) $this->scopeConfig->getValue(
            'socialauth/twitter_config/status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
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
