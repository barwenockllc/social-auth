<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

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
     * Get config for checkout
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig()
    {
        return [
            'socialauthConfiguration' => $this->socialAuthHelper->getSocialsConfiguration()
        ];
    }
}
