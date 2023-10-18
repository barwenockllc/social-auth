<?php

namespace Barwenock\SocialAuth\Model\Customer;

class Create
{
    public function __construct(
        \Magento\Customer\Model\Customer $customerModel,
        \Webkul\SocialSignup\Api\Data\LogintypeInterfaceFactory $logintypeFactory,
        \Psr\Log\LoggerInterface $logger,
        \Webkul\SocialSignup\Helper\Data $dataHelper,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->customerModel = $customerModel;
        $this->logintypeFactory = $logintypeFactory;
        $this->logger = $logger;
        $this->dataHelper = $dataHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * @param $email
     * @param $firstName
     * @param $lastName
     * @param $googleId
     * @param $token
     * @return void
     * @throws \Exception
     */
    public function create($email, $firstName, $lastName, $googleId, $token)
    {
        $this->customerModel
            ->setEmail($email)
            ->setFirstname($firstName)
            ->setLastname($lastName)
            ->setSocialauthGoogleId($googleId)
            ->setSocialauthGoogleToken($token)
            ->save();


        $this->customerModel->setConfirmation(null);
        $this->customerModel->save();

        $setcollection = $this->logintypeFactory->create()
            ->setCustomerId($this->customerModel->getId())
            ->setLoginType(\Webkul\SocialSignup\Api\Data\LogintypeInterface::GOOGLE);
        $setcollection->save();

        $this->dataHelper->getNewsletter($this->customerModel->getId());
        try {
            $this->customerModel->sendNewAccountEmail();
        } catch (\Exception $e) {
            $this->logger->info('Helper Twitter connectByCreatingAccount '.$e->getMessage());
        }
        $this->customerSession->loginById($this->customerModel->getId());
    }
}
