<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Controller\Linkedin;

class Request implements \Magento\Framework\App\ActionInterface
{
    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $session;

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
     * @var \Barwenock\SocialAuth\Service\Authorize\Linkedin
     */
    protected $linkedinService;

    /**
     * @param \Magento\Framework\Session\Generic $session
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     * @param \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper
     * @param \Magento\Framework\App\Response\Http $redirect
     * @param \Magento\Framework\UrlInterface $url
     * @param \Barwenock\SocialAuth\Service\Authorize\Linkedin $linkedinService
     */
    public function __construct(
        \Magento\Framework\Session\Generic $session,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Barwenock\SocialAuth\Helper\Adminhtml\Config $configHelper,
        \Magento\Framework\App\Response\Http $redirect,
        \Magento\Framework\UrlInterface $url,
        \Barwenock\SocialAuth\Service\Authorize\Linkedin $linkedinService
    ) {
        $this->session = $session;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->configHelper = $configHelper;
        $this->redirect = $redirect;
        $this->url = $url;
        $this->linkedinService = $linkedinService;
    }

    public function execute()
    {
        $this->session->unsIsSocialSignupCheckoutPageReq();
        $this->linkedinService->setParameters();

        if ($this->configHelper->getLinkedinStatus() === 0) {
            return $this->redirect->setRedirect($this->url->getUrl('noroute'), 301);
        }

        $csrf = hash('sha256', uniqid((string) rand(), true));
        $this->session->setLinkedinCsrf($csrf);

        $post = $this->request->getParams();
        $mainwProtocol = $this->request->getParam('mainw_protocol');

        if (isset($post['checkoutPage'])) {
            $this->session->setCheckoutPage(1);
        }

        $this->session->setIsSecure($mainwProtocol);

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
            ->setPath($this->linkedinService->createRequestUrl());
    }
}
