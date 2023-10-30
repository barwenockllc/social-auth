<?php

namespace Barwenock\SocialAuth\Block\SocialAuth;

class Redirect extends \Magento\Framework\View\Element\Template
{
    /**
     * Home url
     */
    protected const HOME_PATH = 'home';

    /**
     * Customer account path
     */
    protected const CUSTOMER_ACCOUNT_PATH = 'customer/account';

    /**
     * Privacy policy path
     */
    protected const PRIVACY_POLICY_PATH = 'privacy-policy-cookie-restriction-mode';

    /**
     * No route path
     */
    protected const NO_ROUTE_PATH = 'noroute';

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Barwenock\SocialAuth\Helper\Adminhtml\Config
     */
    protected $adminConfig;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\UrlInterface $url
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $adminConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\UrlInterface $url,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $adminConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->url = $url;
        $this->adminConfig = $adminConfig;
    }

    /**
     * Get redirect path
     *
     * @return string
     */
    public function getRedirectLink()
    {
        if ($this->getRequest()->getParam('noroute')) {
            return $this->url->getUrl(self::NO_ROUTE_PATH);
        }

        $redirectFor = $this->adminConfig->getAuthorizationRedirect();
        switch ($redirectFor) {
            case 1:
                $redirect = $this->url->getUrl(self::CUSTOMER_ACCOUNT_PATH);
                break;
            case 2:
                $redirect = 'reload';
                break;
            case 3:
                $redirect = $this->url->getUrl(self::HOME_PATH);
                break;
            case 4:
                $redirect = $this->url->getUrl(self::PRIVACY_POLICY_PATH);
                break;
            case 5:
                $redirect = $this->url->getUrl($this->adminConfig->getAuthorizeRedirectUrl());
                break;
            default:
                $redirect = $this->url->getUrl(self::CUSTOMER_ACCOUNT_PATH);
        }
        return $redirect;
    }
}
