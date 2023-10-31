<?php

namespace Barwenock\SocialAuth\Controller\Message;

class Display implements \Magento\Framework\App\ActionInterface
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $coreSession;

    /**
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     */
    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->coreSession = $coreSession;
    }


    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $errorMsg = $this->coreSession->getErrorMsg();
        $successMsg = $this->coreSession->getSuccessMsg();

        $this->coreSession->unsSuccessMsg();
        $this->coreSession->unsErrorMsg();

        return $resultJson->setData(['status' => true, 'errorMsg'=> $errorMsg, 'successMsg' => $successMsg]);
    }
}
