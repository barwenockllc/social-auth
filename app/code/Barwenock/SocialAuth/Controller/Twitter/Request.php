<?php

namespace Barwenock\SocialAuth\Controller\Twitter;

class Request implements \Magento\Framework\App\ActionInterface
{
    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $session;

    /**
     * @var \Magento\Framework\App\Response\Http
     */
    protected $redirect;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * @var \Barwenock\SocialAuth\Helper\Adminhtml\Config
     */
    protected $configHelper;

    /**
     * @var \Barwenock\SocialAuth\Service\Authorize\Twitter
     */
    protected $twitterService;

    /**
     * @param \Magento\Framework\Session\Generic $session
     * @param \Magento\Framework\App\Response\Http $redirect
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper
     * @param \Barwenock\SocialAuth\Service\Authorize\Twitter $twitterService
     */
    public function __construct(
        \Magento\Framework\Session\Generic $session,
        \Magento\Framework\App\Response\Http $redirect,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Barwenock\SocialAuth\Service\Authorize\Twitter $twitterService
    ) {
        $this->session = $session;
        $this->redirect = $redirect;
        $this->url = $url;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->configHelper = $configHelper;
        $this->twitterService = $twitterService;
    }

    public function execute()
    {
        $this->session->unsIsSocialSignupCheckoutPageReq();

        if (!$this->configHelper->getTwitterStatus()) {
            return $this->redirect->setRedirect($this->url->getUrl('noroute'), 301);
        }

        $post = $this->request->getParams();
        $mainwProtocol = $this->request->getParam('mainw_protocol');

        if (isset($post['is_checkoutPageReq'])) {
            $this->session->setIsSocialSignupCheckoutPageReq(1);
        }

        $this->session->setIsSecure($mainwProtocol);

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
            ->setPath($this->twitterService->createRequestUrl());
    }
}
