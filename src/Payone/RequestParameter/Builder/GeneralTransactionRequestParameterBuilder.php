<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class GeneralTransactionRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @var CartHasherInterface */
    private $cartHasher;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var EntityRepositoryInterface */
    private $currencyRepository;

    public function __construct(CartHasherInterface $cartHasher, ConfigReaderInterface $configReader, EntityRepositoryInterface $currencyRepository)
    {
        $this->cartHasher         = $cartHasher;
        $this->configReader       = $configReader;
        $this->currencyRepository = $currencyRepository;
    }

    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        $paymentTransaction  = $arguments->getPaymentTransaction();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $requestData         = $arguments->getRequestData();
        $paymentMethod       = $arguments->getPaymentMethod();
        $currency            = $this->getOrderCurrency($paymentTransaction->getOrder(), $salesChannelContext->getContext());

        $parameters = [
            'amount'      => $this->getConvertedAmount($paymentTransaction->getOrder()->getAmountTotal(), $currency->getDecimalPrecision()),
            'currency'    => $currency->getIsoCode(),
            'reference'   => $this->getReferenceNumber($paymentTransaction, true),
            'workorderid' => $this->getWorkOrderId($paymentTransaction, $requestData, $salesChannelContext),
        ];

        $this->addNarrativeTextIfAllowed(
            $parameters,
            $salesChannelContext->getSalesChannel()->getId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentMethod],
            (string) $paymentTransaction->getOrder()->getOrderNumber()
        );

        return $parameters;
    }

    /** @param PaymentTransactionStruct $arguments */
    public function supports(Struct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        return true;
    }

    protected function addNarrativeTextIfAllowed(array &$parameters, string $salesChannelId, string $prefix, string $narrativeText = ''): void
    {
        $config = $this->configReader->read($salesChannelId);

        if ($config->get(sprintf('%sProvideNarrativeText', $prefix), false) === false) {
            return;
        }

        if (empty($narrativeText)) {
            return;
        }

        $parameters['narrative_text'] = mb_substr($narrativeText, 0, 81);
    }

    protected function getReferenceNumber(PaymentTransaction $transaction, bool $generateNew = false): string
    {
        $latestReferenceNumber = $this->getLatestReferenceNumber($transaction);

        if (!empty($latestReferenceNumber) && $generateNew === false) {
            return $latestReferenceNumber;
        }

        $order       = $transaction->getOrder();
        $orderNumber = $order->getOrderNumber();
        $suffix      = $this->getReferenceSuffix($transaction->getOrder());

        return $orderNumber . $suffix;
    }

    protected function getOrderCurrency(OrderEntity $order, Context $context): CurrencyEntity
    {
        $criteria = new Criteria([$order->getCurrencyId()]);

        /** @var null|CurrencyEntity $currency */
        $currency = $this->currencyRepository->search($criteria, $context)->first();

        if (null === $currency) {
            throw new RuntimeException('missing order currency entity');
        }

        return $currency;
    }

    private function getWorkOrderId(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): ?string {
        $workOrderId = $dataBag->get('workorder');

        if (null === $workOrderId) {
            return null;
        }

        $cartHash = $dataBag->get('carthash');

        if (null === $cartHash) {
            return null;
        }

        if (!$this->cartHasher->validate($transaction->getOrder(), $cartHash, $context)) {
            return null;
        }

        return $workOrderId;
    }

    /**
     * TODO: refactor, just taken from old request
     */
    private function getLatestReferenceNumber(PaymentTransaction $transaction): ?string
    {
        /** @var null|OrderTransactionCollection $transactions */
        $transactions = $transaction->getOrder()->getTransactions();

        if ($transactions === null) {
            return null;
        }

        $transactions = $transactions->filter(static function (OrderTransactionEntity $transaction) {
            $paymentMethod = $transaction->getPaymentMethod();

            if ($paymentMethod === null) {
                return false;
            }

            $customFields = $paymentMethod->getCustomFields();

            if (!isset($customFields[CustomFieldInstaller::IS_PAYONE])) {
                return false;
            }

            return $customFields[CustomFieldInstaller::IS_PAYONE];
        });

        if ($transactions->count() === 0) {
            return null;
        }

        $transactions->sort(static function (OrderTransactionEntity $a, OrderTransactionEntity $b) {
            return $a->getCreatedAt() <=> $b->getCreatedAt();
        });
        $orderTransaction = $transactions->last();

        if ($orderTransaction === null) {
            return null;
        }

        $customFields = $orderTransaction->getCustomFields();

        if (empty($customFields[CustomFieldInstaller::TRANSACTION_DATA])) {
            return null;
        }

        $payoneTransactionData = array_pop($customFields[CustomFieldInstaller::TRANSACTION_DATA]);

        if (!isset($payoneTransactionData['request'])) {
            return null;
        }

        $request = $payoneTransactionData['request'];

        return (string) $request['reference'];
    }

    private function getReferenceSuffix(OrderEntity $order): string
    {
        $transactions = $order->getTransactions();

        if ($transactions === null || $transactions->count() <= 1) {
            return '';
        }

        return sprintf('_%d', $transactions->count());
    }
}