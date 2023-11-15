<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Model\Config\Source;

class Display implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Login Option
     * @var string
     */
    const DISPLAY_ON_LOGIN = 'customer_account_login';

    /**
     * Register Option
     * @var string
     */
    const DISPLAY_ON_REGISTER = 'customer_account_create';

    /**
     * Checkout Option
     * @var string
     */
    const DISPLAY_ON_CHECKOUT = 'checkout_index_index';

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::DISPLAY_ON_CHECKOUT, 'label' => __('On Checkout Page')],
            ['value' => self::DISPLAY_ON_LOGIN, 'label' => __('On Login Form')],
            ['value' => self::DISPLAY_ON_REGISTER, 'label' => __('On Registration Form')],
        ];
    }

    /**
     * Get multiselect options
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_column($this->toOptionArray(), 'label', 'value');
    }
}
