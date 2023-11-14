<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Helper;

class SocialAuth extends \Magento\Framework\App\Helper\AbstractHelper implements
    \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var \Barwenock\SocialAuth\Helper\Adminhtml\Config
     */
    protected $adminConfig;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    protected $resolver;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $asset;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonSerializer;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $adminConfig
     * @param \Magento\Framework\Locale\Resolver $resolver
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Framework\View\Asset\Repository $asset
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     */
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

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSocialsConfiguration()
    {
        $config = [
            'moduleStatus' => $this->adminConfig->getModuleStatus(),
            'isCheckoutOn' => $this->isCheckoutPageOn(),
            'socialStatus' => $this->isAnySocialEnabled(),
            'facebookStatus' => $this->adminConfig->getFacebookStatus(),
            'googleStatus' => $this->adminConfig->getGoogleStatus(),
            'twitterStatus' => $this->adminConfig->getTwitterStatus(),
            'linkedinStatus' => $this->adminConfig->getLinkedinStatus(),
            'instagramStatus' => $this->adminConfig->getInstagramStatus(),
            'facebookAppId' => $this->adminConfig->getFacebookAppId(),
            'facebookUserId' => 0,
            'localeCode' => $this->resolver->getLocale(),
            'facebookAuthUrl' => $this->url->getUrl('socialauth/facebook/authorize'),
            'facebookAuthIcon' => $this->getSocialImage('facebook'),
            'twitterAuthIcon' => $this->getSocialImage('twitter'),
            'googleAuthIcon' => $this->getSocialImage('google'),
            'linkedinAuthIcon' => $this->getSocialImage('linkedin'),
            'instagramAuthIcon' => $this->getSocialImage('instagram'),
            'popupConfiguration' => [
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
            'getMessagesUrl' => $this->url->getUrl('socialauth/message/display'),
        ];

        return $config;
    }

    /**
     * @return bool
     */
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

    /**
     * @param $socialType
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
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

    /**
     * @param $data
     * @return bool|string
     */
    public function serializeData($data)
    {
        return $this->jsonSerializer->serialize($data);
    }
}
