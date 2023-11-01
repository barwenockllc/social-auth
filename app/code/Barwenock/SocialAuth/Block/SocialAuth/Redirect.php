<?php

namespace Barwenock\SocialAuth\Block\SocialAuth;

class Redirect extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Barwenock\SocialAuth\Helper\Authorize\Redirect
     */
    protected $authorizeRedirectHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Barwenock\SocialAuth\Helper\Authorize\Redirect $authorizeRedirectHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Barwenock\SocialAuth\Helper\Authorize\Redirect $authorizeRedirectHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->authorizeRedirectHelper = $authorizeRedirectHelper;
    }

    /**
     * Get redirect path
     *
     * @return string
     */
    public function getRedirectLink()
    {
        return $this->authorizeRedirectHelper->getRedirectPage();
    }
}
