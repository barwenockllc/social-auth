<?php

namespace Barwenock\SocialAuth\Block\Product;

class ProductShare extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get custom information for the current product
     *
     * @return string
     */
    public function productShareStatus()
    {
        return $this->_scopeConfig->getValue(
            'socialauth/socialauth/product_share',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed|null
     */
    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }
}
