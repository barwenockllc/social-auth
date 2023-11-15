<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Model\Config\Source;

class Redirect implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Customer Account
     * @var int
     */
    const CUSTOMER_ACCOUNT = 1;

    /**
     * Current Page
     * @var int
     */
    const CURRENT_PAGE = 2;

    /**
     * Home Page
     * @var int
     */
    const HOME_PAGE = 3;

    /**
     * Privacy And Cookie Policy
     * @var int
     */
    const PRIVACY_AND_COOKIE_POLICY = 4;

    /**
     * Custom Url
     * @var int
     */
    const CUSTOM_URL = 5;

    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::CUSTOMER_ACCOUNT, 'label' => __('Customer Account')],
            ['value' => self::CURRENT_PAGE,   'label' => __('Stay on the current page')],
            ['value' => self::HOME_PAGE, 'label' => __('Home page')],
            ['value' => self::PRIVACY_AND_COOKIE_POLICY, 'label' => __('Privacy and Cookie Policy')],
            ['value' => self::CUSTOM_URL, 'label' => __('Custom URL')],
        ];
    }
}
