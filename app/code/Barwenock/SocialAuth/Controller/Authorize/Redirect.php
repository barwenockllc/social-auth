<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Controller\Authorize;

/**
 * Redirect Class of Google
 */
class Redirect implements \Magento\Framework\App\ActionInterface
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     */
    public function __construct(
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->pageFactory = $pageFactory;
    }

    /**
     * Redirect to login page
     */
    public function execute()
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->addHandle('social_authorize_redirect');
        return $resultPage;
    }
}
