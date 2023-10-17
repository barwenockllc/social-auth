<?php
namespace Barwenock\SocialAuth\Model\Config\Source;

class Redirect implements \Magento\Framework\Data\OptionSourceInterface
{
    const CURRENT_PAGE = '1';
    const ACCOUNT_DASHBOARD = '2';
    const HOME_PAGE = '3';
    const PRIVACY_AND_COOKIE_POLICY = '4';
    const CUSTOM_URL = '__custom__';

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::CURRENT_PAGE, 'label' => __('Current page')],
            ['value' => self::ACCOUNT_DASHBOARD, 'label' => __('Customer Account Dashboard')],
            ['value' => self::HOME_PAGE, 'label' => __('Home page')],
            ['value' => self::PRIVACY_AND_COOKIE_POLICY, 'label' => __('Privacy and Cookie Policy')],
            ['value' => self::CUSTOM_URL, 'label' => __('Custom URL')],
        ];
    }
}
