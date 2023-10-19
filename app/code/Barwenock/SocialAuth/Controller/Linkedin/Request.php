<?php

namespace Barwenock\SocialAuth\Controller\Linkedin;

class Request implements \Magento\Framework\App\ActionInterface
{
    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $session;

    /**
     * @var \Barwenock\SocialAuth\Controller\Linkedin\LinkedinClient
     */
    protected $linkedinClient;

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
     * @param \Barwenock\SocialAuth\Controller\Linkedin\LinkedinClient $linkedinClient
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper
     * @param \Magento\Framework\App\Response\Http $redirect
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(
        \Magento\Framework\Session\Generic $session,
        \Barwenock\SocialAuth\Controller\Linkedin\LinkedinClient $linkedinClient,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Magento\Framework\App\Response\Http $redirect,
        \Magento\Framework\UrlInterface $url,
    ) {
        $this->session = $session;
        $this->linkedinClient = $linkedinClient;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->configHelper = $configHelper;
        $this->redirect = $redirect;
        $this->url = $url;
    }

    public function execute()
    {
        $this->session->unsIsSocialSignupCheckoutPageReq();
        $this->linkedinClient->setParameters();

        if (!$this->configHelper->getLinkedinStatus()) {
            return $this->redirect->setRedirect($this->url->getUrl('noroute'), 301);
        }

        $csrf = hash('sha256', uniqid(rand(), true));
        $this->session->setLinkedinCsrf($csrf);
        $this->linkedinClient->setState($csrf);

        $post = $this->request->getParams();
        $mainwProtocol = $this->request->getParam('mainw_protocol');

        if (isset($post['is_checkoutPageReq'])) {
            $this->session->setIsSocialSignupCheckoutPageReq(1);
        }

        $this->session->setIsSecure($mainwProtocol);

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
            ->setPath($this->linkedinClient->createRequestUrl());
    }
}
