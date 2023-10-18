<?php

namespace Barwenock\SocialAuth\Controller\Google;

class Request implements \Magento\Framework\App\ActionInterface
{
    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $session;

    /**
     * @var \Barwenock\SocialAuth\Controller\Google\GoogleClient
     */
    protected $googleClient;

    /**
     * @var \Barwenock\SocialAuth\Helper\Adminhtml\Config
     */
    protected $configHelper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * @var \Magento\Framework\App\Response\Http
     */
    protected $redirect;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @param \Magento\Framework\Session\Generic $session
     * @param \Barwenock\SocialAuth\Controller\Google\GoogleClient $googleClient
     * @param \Webkul\SocialSignup\Helper\Data $helper
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     * @param \Magento\Framework\App\Response\Http $redirect
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(
        \Magento\Framework\Session\Generic $session,
        \Barwenock\SocialAuth\Controller\Google\GoogleClient $googleClient,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\App\Response\Http $redirect,
        \Magento\Framework\UrlInterface $url,
    ) {
        $this->session = $session;
        $this->googleClient = $googleClient;
        $this->configHelper = $configHelper;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->redirect = $redirect;
        $this->url = $url;
    }

    /**
     * Redirect the customer to authentication page
     */
    public function execute()
    {
        $this->session->unsIsSocialSignupCheckoutPageReq();
        $this->googleClient->setParameters();

        if (!$this->configHelper->getGoogleStatus()) {
            return $this->redirect->setRedirect($this->url->getUrl('noroute'), 301);
        }

        $csrf = hash('sha256', uniqid(rand(), true));
        $this->session->setGoogleCsrf($csrf);
        $this->googleClient->setState($csrf);

        $post = $this->request->getParams();
        $mainwProtocol = $this->request->getParam('mainw_protocol');

        if (!empty($post['is_checkoutPageReq'])) {
            $this->session->setIsSocialSignupCheckoutPageReq(1);
        }

        $this->session->setIsSecure($mainwProtocol);

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
            ->setPath($this->googleClient->createRequestUrl());
    }
}
