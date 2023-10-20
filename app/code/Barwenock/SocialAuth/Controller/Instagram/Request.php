<?php

namespace Barwenock\SocialAuth\Controller\Instagram;

class Request implements \Magento\Framework\App\ActionInterface
{
    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $session;

    /**
     * @var \Barwenock\SocialAuth\Controller\Instagram\InstagramClient
     */
    protected $instagramClient;

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
     * @param \Barwenock\SocialAuth\Controller\Instagram\InstagramClient $instagramClient
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     * @param \Magento\Framework\App\Response\Http $redirect
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(
        \Magento\Framework\Session\Generic $session,
        \Barwenock\SocialAuth\Controller\Instagram\InstagramClient $instagramClient,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\App\Response\Http $redirect,
        \Magento\Framework\UrlInterface $url,
    ) {

        $this->session = $session;
        $this->instagramClient = $instagramClient;
        $this->configHelper = $configHelper;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->redirect = $redirect;
        $this->url = $url;
    }

    public function execute()
    {
        $this->session->unsIsSocialSignupCheckoutPageReq();
        $this->instagramClient->setParameters();

        if (!$this->configHelper->getInstagramStatus()) {
            return $this->redirect->setRedirect($this->url->getUrl('noroute'), 301);
        }

        // CSRF protection
        $csrf = hash('sha256', uniqid(rand(), true));
        $this->session->setInstagramCsrf($csrf);
        $this->instagramClient->setState($csrf);

        $post = $this->request->getParams();
        $mainwProtocol = $this->request->getParam('mainw_protocol');


        if (!empty($post['is_checkoutPageReq'])) {
            $this->session->setIsSocialSignupCheckoutPageReq(1);
        }

        $this->session->setIsSecure($mainwProtocol);
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
            ->setPath($this->instagramClient->createRequestUrl());
    }
}
