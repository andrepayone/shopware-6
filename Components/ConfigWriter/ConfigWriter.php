<?php

declare(strict_types=1);

namespace PayonePayment\Components\ConfigWriter;

use PayonePayment\DataAbstractionLayer\Entity\PayonePaymentConfig\PayonePaymentConfigEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ConfigWriter implements ConfigWriterInterface
{
    /** @var EntityRepositoryInterface */
    private $repository;

    public function __construct(EntityRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function write(string $key, string $value, string $salesChannelId = ''): void
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter(
            'payone_payment_config.key',
            $key
        ));

        if (empty($salesChannelId)) {
            $criteria->addFilter(new EqualsFilter(
                'payone_payment_config.salesChannelId',
                null
            ));
        } else {
            $criteria->addFilter(new EqualsFilter(
                'payone_payment_config.salesChannelId',
                $salesChannelId
            ));
        }

        /** @var null|PayonePaymentConfigEntity $existingEntry */
        $existingEntry = $this->repository->search($criteria, $context)->first();

        if (null === $existingEntry) {
            $data = [
                'key'   => $key,
                'value' => $value,
            ];

            $this->repository->create([$data], $context);
        } else {
            $data = [
                'id'    => $existingEntry->getId(),
                'key'   => $key,
                'value' => $value,
            ];

            $this->repository->update([$data], $context);
        }
    }
}