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
     *
     * @var int
     */
    protected const CUSTOMER_ACCOUNT = 1;

    /**
     * Current Page
     *
     * @var int
     */
    protected const CURRENT_PAGE = 2;

    /**
     * Home Page
     *
     * @var int
     */
    protected const HOME_PAGE = 3;

    /**
     * Privacy And Cookie Policy
     *
     * @var int
     */
    protected const PRIVACY_AND_COOKIE_POLICY = 4;

    /**
     * Custom Url
     *
     * @var int
     */
    protected const CUSTOM_URL = 5;

    /**
     * Returns an array of options for a dropdown admin configuration
     *
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
