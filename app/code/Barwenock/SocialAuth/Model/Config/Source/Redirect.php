<?php

namespace Barwenock\SocialAuth\Model\Config\Source;

class Redirect implements \Magento\Framework\Data\OptionSourceInterface
{
    const CUSTOMER_ACCOUNT = '1';
    const HOME_PAGE = '3';
    const PRIVACY_AND_COOKIE_POLICY = '4';
    const CUSTOM_URL = '__custom__';

    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::CUSTOMER_ACCOUNT, 'label' => __('Customer Account')],
            ['value' => self::HOME_PAGE, 'label' => __('Home page')],
            ['value' => self::PRIVACY_AND_COOKIE_POLICY, 'label' => __('Privacy and Cookie Policy')],
            ['value' => self::CUSTOM_URL, 'label' => __('Custom URL')],
        ];
    }
}
