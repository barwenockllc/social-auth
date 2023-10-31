<?php

namespace Barwenock\SocialAuth\Block\SocialAuth;

class Socials extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\View\Element\Template\Context
     */
    protected $context;

    /**
     * @var \Barwenock\SocialAuth\Helper\Adminhtml\Config
     */
    public $configHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    protected $localeResolver;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    /**
     * @var \Barwenock\SocialAuth\Helper\SocialAuth
     */
    protected $socialAuthHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Locale\Resolver $localeResolver
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param \Barwenock\SocialAuth\Helper\SocialAuth $socialAuthHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config    $configHelper,
        \Magento\Customer\Model\Session                  $customerSession,
        \Magento\Framework\Locale\Resolver               $localeResolver,
        \Magento\Framework\Serialize\Serializer\Json     $serializer,
        \Barwenock\SocialAuth\Helper\SocialAuth          $socialAuthHelper,
        array                                            $data = []
    ) {
        $this->configHelper = $configHelper;
        $this->customerSession = $customerSession;
        $this->localeResolver = $localeResolver;
        $this->serializer = $serializer;
        $this->socialAuthHelper = $socialAuthHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function displaySocialsOn()
    {
        $action = $this->_request->getFullActionName();

        $enableFor = $this->_scopeConfig->getValue(
            'socialauth/socialauth/display_on',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $enableForActions = explode(",", $enableFor ?? '');
        if (in_array($action, $enableForActions)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function ifCustomerLogin()
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * @return bool
     */
    public function isAnySocialEnabled()
    {
        return $this->socialAuthHelper->isAnySocialEnabled();
    }

    /**
     * @param $type
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSocialConnectImage($type)
    {
        $image = $this->configHelper->getSocialConnectImage($type);

        if (empty($image)) {
            $image = $this->getViewFileUrl("Barwenock_SocialAuth::images/{$type}.png");
        } else {
            $mediaBaseUrl = $this->_storeManager->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            $image = $mediaBaseUrl . "socialauth/{$type}/" . $image;
        }
        return $image;
    }

    /**
     * @return string
     */
    public function getLocaleCode()
    {
        return $this->localeResolver->getLocale();
    }

    /**
     * @param $url
     * @param $param
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRequestUrl($url, $param)
    {
        return $this->_storeManager->getStore()->getUrl($url, $param);
    }

    /**
     * @return bool|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPopupData()
    {
        $popupData = [
            "width"=>'700',
            "height" => '300',
            "twitterUrl" => $this->getRequestUrl('socialauth/twitter/request', ['mainw_protocol'=>'http']),
            "linkedinUrl" => $this->getRequestUrl('socialauth/linkedin/request', ['mainw_protocol'=>'http']),
            "googleUrl" => $this->getRequestUrl('socialauth/google/request', ['mainw_protocol'=>'http']),
            "instagramUrl" => $this->getRequestUrl('socialauth/instagram/request', ['mainw_protocol'=>'http'])
        ];

        return $this->serializer->serialize($popupData);
    }

    /**
     * @return bool|string
     */
    public function getFacebookBlockData()
    {
        $data = [
            "fbAppId" => $this->configHelper->getFacebookAppId(),
            "uId" => 0,
            "customerSession" => $this->ifCustomerLogin(),
            "localeCode" => $this->getLocaleCode(),
            "fbLoginUrl" => $this->getUrl('socialauth/facebook/authorize')
        ];

        return $this->serializer->serialize($data);
    }
}
