<?php

namespace Barwenock\SocialAuth\Helper;

use Magento\Framework\App\Helper\Context;

class SocialAuth extends \Magento\Framework\App\Helper\AbstractHelper implements
    \Magento\Framework\View\Element\Block\ArgumentInterface
{
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $adminConfig,
        \Magento\Framework\Locale\Resolver $resolver,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\View\Asset\Repository $asset,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
    ) {
        $this->adminConfig = $adminConfig;
        $this->resolver = $resolver;
        $this->url = $url;
        $this->asset = $asset;
        $this->storeManager = $storeManager;
        $this->session = $session;
        $this->jsonSerializer = $jsonSerializer;
        parent::__construct($context);
    }

    public function getSocialsConfiguration()
    {
        $config = [
            'fb_status' => $this->adminConfig->getFacebookStatus(),
            'google_status' => $this->adminConfig->getGoogleStatus(),
            'twitter_status' => $this->adminConfig->getTwitterStatus(),
            'linkedin_status' => $this->adminConfig->getLinkedinStatus(),
            'insta_status' => $this->adminConfig->getInstagramStatus(),
            'fbAppId' => $this->adminConfig->getFacebookAppId(),
            'uId' => 0,
            'localeCode' => $this->resolver->getLocale(),
            'fbLoginUrl' => $this->url->getUrl('socialauth/facebook/authorize'),
            'status' => $this->isAnySocialEnabled(),
            'loginImg' => $this->getSocialImage('facebook'),
            'twitterLoginImg' => $this->getSocialImage('twitter'),
            'googleLoginImg' => $this->getSocialImage('google'),
            'LinkedinLoginImg' => $this->getSocialImage('linkedin'),
            'InstaLoginImg' => $this->getSocialImage('instagram'),
            'socialSignupModuleEnable' => $this->adminConfig->getModuleStatus(),
            'pageCallCheckout' => $this->isCheckoutPageOn(),
            'popupData' => [
                "width" => '700',
                "height" => '300',
                "twitterUrl" => $this->storeManager->getStore()
                    ->getUrl('socialauth/twitter/request', ['mainw_protocol' => 'http']),
                "linkedinUrl" => $this->storeManager->getStore()
                    ->getUrl('socialauth/linkedin/request', ['mainw_protocol' => 'http']),
                "googleUrl" => $this->storeManager->getStore()
                    ->getUrl('socialauth/google/request', ['mainw_protocol' => 'http']),
                "instagramUrl" => $this->storeManager->getStore()
                    ->getUrl('socialauth/instagram/request', ['mainw_protocol' => 'http']),
            ],
            'isCustomerLoggedIn' => $this->session->isLoggedIn(),
            'getMessagesUrl' => $this->url->getUrl('socialauth/message/check'),
        ];

        return $config;
    }

    public function isAnySocialEnabled()
    {
        // Define an array of status values to check
        $statuses = [
            $this->adminConfig->getFacebookStatus(),
            $this->adminConfig->getTwitterStatus(),
            $this->adminConfig->getGoogleStatus(),
            $this->adminConfig->getLinkedinStatus(),
            $this->adminConfig->getInstagramStatus(),
        ];

        // Check if any of the statuses is equal to 1 (enabled)
        return in_array(1, $statuses);
    }

    public function getSocialImage($socialType)
    {
        $image = $this->adminConfig->getSocialConnectImage($socialType);

        if (empty($image)) {
            return $this->asset->getUrl("Barwenock_SocialAuth::images/$socialType.png");
        } else {
            return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                . "socialauth/$socialType/" . $image;
        }
    }

    /**
     * @return int
     */
    public function isCheckoutPageOn()
    {
        $displayOn = $this->adminConfig->getSocialDisplayOn();
        $checkoutLayout = 'checkout_index_index';

        $displays = explode(",", $displayOn ?? '');
        if (in_array($checkoutLayout, $displays)) {
            return 1;
        } else {
            return 0;
        }
    }

    public function serializeData($data)
    {
        return $this->jsonSerializer->serialize($data);
    }
}
