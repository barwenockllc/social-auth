<?php

namespace Barwenock\SocialAuth\Model;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var \Barwenock\SocialAuth\Helper\SocialAuth
     */
    protected $socialAuthHelper;

    /**
     * @param \Barwenock\SocialAuth\Helper\SocialAuth $socialAuthHelper
     */
    public function __construct(
        \Barwenock\SocialAuth\Helper\SocialAuth $socialAuthHelper
    ) {
        $this->socialAuthHelper = $socialAuthHelper;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'socialauthConfiguration' => $this->socialAuthHelper->getSocialsConfiguration()
        ];
    }
}
