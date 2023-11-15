<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Helper\Authorize;

class Redirect extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Home url
     * @var string
     */
    protected const HOME_PATH = 'home';

    /**
     * Customer account path
     * @var string
     */
    protected const CUSTOMER_ACCOUNT_PATH = 'customer/account';

    /**
     * Privacy policy path
     * @var string
     */
    protected const PRIVACY_POLICY_PATH = 'privacy-policy-cookie-restriction-mode';

    /**
     * No route path
     * @var string
     */
    protected const NO_ROUTE_PATH = 'noroute';

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var \Barwenock\SocialAuth\Helper\Adminhtml\Config
     */
    protected $adminConfig;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\UrlInterface $url
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $adminConfig
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\UrlInterface $url,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $adminConfig
    ) {
        $this->url = $url;
        $this->adminConfig = $adminConfig;
        parent::__construct($context);
    }

    /**
     * @return string
     */
    public function getRedirectPage()
    {
        if ($this->_request->getParam('noroute')) {
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
