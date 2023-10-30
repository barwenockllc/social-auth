<?php

namespace Barwenock\SocialAuth\Controller\Message;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Check extends Action
{

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cartData;

    /**
     * Constuct intialization
     *
     * @param Context $context
     * @param \Magento\Checkout\Model\Session $_checkoutSession
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param \Magento\Checkout\Model\Cart $cartData
     */
    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $_checkoutSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Checkout\Model\Cart $cartData
    ) {
        $this->checkoutSession = $_checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cartData = $cartData;
        $this->coreSession = $coreSession;
        parent::__construct($context);
    }

    /**
     * Get message
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $errorMsg = $this->coreSession->getErrorMsg();
        $successMsg = $this->coreSession->getSuccessMsg();
        $this->unSetSessionMessages();
        return $resultJson->setData(['status' => true, 'errorMsg'=> $errorMsg, 'successMsg' => $successMsg]);
    }

    /**
     * Unset the messages from \Magento\Framework\Session\SessionManagerInterface
     */
    private function unSetSessionMessages()
    {
        $this->coreSession->unsSuccessMsg();
        $this->coreSession->unsErrorMsg();
    }
}
