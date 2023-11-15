<?php

namespace Barwenock\SocialAuth\Test\Unit\Model\Customer;

use Barwenock\SocialAuth\Model\Customer\Create;
use Barwenock\SocialAuth\Model\LoginTypeFactory;
use Barwenock\SocialAuth\Api\LoginTypeRepositoryInterface;
use Barwenock\SocialAuth\Helper\Adminhtml\Config as ConfigHelper;
use Magento\Customer\Model\Session;
use Magento\Newsletter\Model\SubscriptionManager;
use Magento\Store\Model\StoreManagerInterface;

class CreateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Create
     */
    protected $createModel;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $customerModelMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $loginTypeFactoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $customerSessionMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $loginTypeRepositoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $configHelperMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $subscriptionManagerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $resourceModelMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $loginTypeMock;

    protected function setUp(): void
    {
        // Mock dependencies
        $this->customerModelMock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
                ->disableOriginalConstructor()
                ->addMethods(['setEmail', 'setFirstname', 'setLastname'])
                ->onlyMethods(['getId', 'sendNewAccountEmail'])
                ->getMockForAbstractClass();

        $this->loginTypeFactoryMock = $this->getMockBuilder(LoginTypeFactory::class)
            ->disableOriginalConstructor()
            ->addMethods(['setCustomerId', 'setLoginType'])
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();

        $this->customerSessionMock = $this->createMock(Session::class);
        $this->loginTypeRepositoryMock = $this->createMock(LoginTypeRepositoryInterface::class);
        $this->configHelperMock = $this->createMock(ConfigHelper::class);
        $this->subscriptionManagerMock = $this->createMock(SubscriptionManager::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->resourceModelMock = $this->createMock(\Magento\Customer\Model\ResourceModel\Customer::class);
        $this->loginTypeMock = $this->createMock(\Barwenock\SocialAuth\Model\LoginType::class);

        // Create an instance of the class under test
        $this->createModel = new Create(
            $this->customerModelMock,
            $this->loginTypeFactoryMock,
            $this->customerSessionMock,
            $this->loginTypeRepositoryMock,
            $this->configHelperMock,
            $this->subscriptionManagerMock,
            $this->storeManagerMock,
            $this->resourceModelMock
        );
    }

    public function testCreateMethod()
    {
        // Test data
        $email = 'test@example.com';
        $firstName = 'John';
        $lastName = 'Doe';
        $socialId = 'social123';
        $socialToken = 'token123';
        $social = 'facebook';

        $this->customerModelMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->customerModelMock->method('sendNewAccountEmail')->willReturnSelf();

        $this->customerModelMock->method('setEmail')->willReturnSelf();
        $this->customerModelMock->method('setFirstname')->willReturnSelf();
        $this->customerModelMock->method('setLastname')->willReturnSelf();

        $this->loginTypeMock->method('setCustomerId')->willReturnSelf();
        $this->loginTypeMock->method('setLoginType')->willReturnSelf();

        $this->loginTypeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->loginTypeMock);

        // Your actual method call
        $this->createModel->create($email, $firstName, $lastName, $socialId, $socialToken, $social);
    }
}
