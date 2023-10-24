<?php

namespace Barwenock\SocialAuth\Controller\Twitter;

class Request implements \Magento\Framework\App\ActionInterface
{
    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $session;

    /**
     * @var \Barwenock\SocialAuth\Controller\Twitter\TwitterClient
     */
    protected $twitterClient;

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
     * @param \Magento\Framework\Session\Generic $session
     * @param \Barwenock\SocialAuth\Controller\Twitter\TwitterClient $twitterClient
     * @param \Magento\Framework\App\Response\Http $redirect
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     */
    public function __construct(
        \Magento\Framework\Session\Generic $session,
        \Barwenock\SocialAuth\Controller\Twitter\TwitterClient $twitterClient,
        \Magento\Framework\App\Response\Http $redirect,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Controller\ResultFactory $resultFactory
    ) {
        $this->session = $session;
        $this->twitterClient = $twitterClient;
        $this->redirect = $redirect;
        $this->url = $url;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
    }

    public function execute()
    {
        $this->session->unsIsSocialSignupCheckoutPageReq();
        $this->twitterClient->setParameters();

        if (!($this->twitterClient->isEnabled())) {
            return $this->redirect->setRedirect($this->url->getUrl('noroute'), 301);
        }

        $post = $this->request->getParams();
        $mainwProtocol = $this->request->getParam('mainw_protocol');

        if (isset($post['is_checkoutPageReq'])) {
            $this->session->setIsSocialSignupCheckoutPageReq(1);
        }

        $this->session->setIsSecure($mainwProtocol);

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
            ->setPath($this->twitterClient->createRequestUrl());
    }
}
