<?php

namespace Barwenock\SocialAuth\Ui\Component\Listing\Column;

class TypeUserLogin extends \Magento\Ui\Component\Listing\Columns\Column
{
    public const DEFAULT = 'Default';

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Barwenock\SocialAuth\Api\LoginTypeRepositoryInterface
     */
    protected $loginTypeRepository;

    /**
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Barwenock\SocialAuth\Api\LoginTypeRepositoryInterface $loginTypeRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Barwenock\SocialAuth\Api\LoginTypeRepositoryInterface $loginTypeRepository,
        array $components = [],
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;
        $this->loginTypeRepository = $loginTypeRepository;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $customer  = $this->customerRepository->getById($item['entity_id']);

                $customerLoginType = $this->loginTypeRepository->getByCustomerId($customer->getId());

                if ($customerLoginType->getLoginType() == null) {
                    $type = self::DEFAULT;
                } else {
                    $type = $customerLoginType->getLoginType();
                }

                $item[$this->getData('name')] = $type;
            }
        }
        return $dataSource;
    }
}
