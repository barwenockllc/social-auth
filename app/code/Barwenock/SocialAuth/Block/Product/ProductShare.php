<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Block\Product;

class ProductShare extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Framework\App\RequestInterface $request
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\App\RequestInterface $request,
        array $data = []
    ) {
        $this->productRepository = $productRepository;
        $this->request = $request;
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
     * @return \Magento\Catalog\Api\Data\ProductInterface|mixed|void|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrentProduct()
    {
        $productId = $this->request->getParam('id');

        if ($productId) {
            return $this->productRepository->getById($productId);
        }
    }
}
