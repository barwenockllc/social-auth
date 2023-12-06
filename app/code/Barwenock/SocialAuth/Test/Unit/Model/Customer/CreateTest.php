<?php
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */

declare(strict_types=1);

namespace Barwenock\SocialAuth\Test\Unit\Model\Customer;

class CreateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Barwenock\SocialAuth\Model\Customer\Create
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

        $this->loginTypeFactoryMock = $this
            ->getMockBuilder(\Barwenock\SocialAuth\Model\LoginTypeFactory::class)
            ->disableOriginalConstructor()
            ->addMethods(['setCustomerId', 'setLoginType'])
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();

        $this->customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->loginTypeRepositoryMock = $this
            ->createMock(\Barwenock\SocialAuth\Api\LoginTypeRepositoryInterface::class);
        $this->configHelperMock = $this
            ->createMock(\Barwenock\SocialAuth\Helper\Adminhtml\Config::class);
        $this->subscriptionManagerMock = $this
            ->createMock(\Magento\Newsletter\Model\SubscriptionManager::class);
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->resourceModelMock = $this
            ->createMock(\Magento\Customer\Model\ResourceModel\Customer::class);
        $this->loginTypeMock = $this->createMock(\Barwenock\SocialAuth\Model\LoginType::class);

        // Create an instance of the class under test
        $this->createModel = new \Barwenock\SocialAuth\Model\Customer\Create(
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
