<?php

namespace Barwenock\SocialAuth\Block\SocialAuth;

class Socials extends \Magento\Framework\View\Element\Template
{
    protected $customerSession;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Barwenock\SocialAuth\Api\FacebookCustomerRepositoryInterface $facebookCustomerRepository,
        \Magento\Framework\Locale\Resolver $localeResolver,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        array $data = []
    ) {
        $this->configHelper = $configHelper;
        $this->customerSession = $customerSession;
        $this->facebookCustomerRepository = $facebookCustomerRepository;
        $this->localeResolver = $localeResolver;
        $this->serializer = $serializer;
        parent::__construct($context, $data);
    }

    /**
     * Social Signup enable or disable on login or signup page
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

    public function ifCustomerLogin()
    {
        return $this->customerSession->isLoggedIn();
    }

    public function isAnySocialEnabled()
    {
        // Define an array of status values to check
        $statuses = [
            $this->configHelper->getFacebookStatus(),
            $this->configHelper->getTwitterStatus(),
            $this->configHelper->getGoogleStatus(),
            $this->configHelper->getLinkedinStatus(),
            $this->configHelper->getInstagramStatus(),
        ];

        // Check if any of the statuses is equal to 1 (enabled)
        return in_array(1, $statuses);
    }

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

    public function getFacebookUserId()
    {
        $userId = 0;
        $customerId = $this->customerSession->getCustomerId();

        $collection = $this->facebookCustomerRepository->getByCustomerId($customerId);
        foreach ($collection as $data) {
            if (isset($data['facebook_id'])) {
                $userId = $data['facebook_id'];
            }
        }
        return $userId;
    }

    public function getLocaleCode()
    {
        return $this->localeResolver->getLocale();
    }

    public function getRequestUrl($url, $param)
    {
        return $this->_storeManager->getStore()->getUrl($url, $param);
    }

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

    public function getFacebookBlockData()
    {
        $data = [
            "fbAppId" => $this->configHelper->getFacebookAppId(),
            "uId" => $this->getFacebookUserId(),
            "customerSession" => $this->ifCustomerLogin(),
            "localeCode" => $this->getLocaleCode(),
            "fbLoginUrl" => $this->getUrl('socialsignup/facebook/login')
        ];

        return $this->serializer->serialize($data);
    }
}
