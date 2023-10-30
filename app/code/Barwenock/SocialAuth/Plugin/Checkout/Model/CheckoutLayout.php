<?php

namespace Barwenock\SocialAuth\Plugin\Checkout\Model;

class CheckoutLayout
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(\Magento\Customer\Model\Session $customerSession)
    {
        $this->customerSession = $customerSession;
    }

    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $subject, array $jsLayout)
    {
        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerSession->getCustomer();

            // Define the values to be set
            $values = [
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname(),
            ];

            // Set the values in the layout processor
            $jsLayout = $this->setFieldValues($jsLayout, $values);
        }

        return $jsLayout;
    }

    /**
     * @param array $jsLayout
     * @param array $values
     * @return array
     */
    private function setFieldValues(array $jsLayout, array $values)
    {
        $fieldsToUpdate = ['firstname', 'lastname'];

        foreach ($fieldsToUpdate as $field) {
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                ['shippingAddress']['children']['shipping-address-fieldset']['children'][$field])) {
                $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                ['shippingAddress']['children']['shipping-address-fieldset']['children'][$field]
                ['value'] = $values[$field];
            }
        }

        return $jsLayout;
    }
}