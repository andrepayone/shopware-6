<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\Ratepay\DeviceFingerprint\DeviceFingerprintServiceInterface;
use PayonePayment\Components\Validator\Birthday;
use PayonePayment\Components\Validator\Iban;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class PayoneRatepayDebitPaymentHandler extends AbstractPayonePaymentHandler implements SynchronousPaymentHandlerInterface
{
    /** @var PayoneClientInterface */
    protected $client;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    /** @var RequestParameterFactory */
    private $requestParameterFactory;

    /** @var DeviceFingerprintServiceInterface */
    private $deviceFingerprintService;

    public function __construct(
        ConfigReaderInterface $configReader,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $dataHandler,
        EntityRepositoryInterface $lineItemRepository,
        RequestStack $requestStack,
        RequestParameterFactory $requestParameterFactory,
        DeviceFingerprintServiceInterface $deviceFingerprintService
    ) {
        parent::__construct($configReader, $lineItemRepository, $requestStack);

        $this->client                   = $client;
        $this->translator               = $translator;
        $this->dataHandler              = $dataHandler;
        $this->requestParameterFactory  = $requestParameterFactory;
        $this->deviceFingerprintService = $deviceFingerprintService;
    }

    /**
     * {@inheritdoc}
     */
    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        $requestData = $this->fetchRequestData();

        // Get configured authorization method
        $authorizationMethod = $this->getAuthorizationMethod(
            $transaction->getOrder()->getSalesChannelId(),
            'ratepayDebitAuthorizationMethod',
            'preauthorization'
        );

        $paymentTransaction = PaymentTransaction::fromSyncPaymentTransactionStruct($transaction, $transaction->getOrder());

        $request = $this->requestParameterFactory->getRequestParameter(
            new PaymentTransactionStruct(
                $paymentTransaction,
                $requestData,
                $salesChannelContext,
                __CLASS__,
                $authorizationMethod
            )
        );

        try {
            $response = $this->client->request($request);
        } catch (PayoneRequestException $exception) {
            $this->deviceFingerprintService->deleteDeviceIdentToken();
            throw new SyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $exception->getResponse()['error']['CustomerMessage']
            );
        } catch (Throwable $exception) {
            $this->deviceFingerprintService->deleteDeviceIdentToken();
            throw new SyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        if (empty($response['status']) || $response['status'] === 'ERROR') {
            $this->deviceFingerprintService->deleteDeviceIdentToken();
            throw new SyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        // It differs depending on the authorization method
        $clearingReference = $response['addpaydata']['clearing_reference'] ?? $response['clearing']['Reference'];

        // Prepare custom fields for the transaction
        $data = $this->prepareTransactionCustomFields($request, $response, array_merge(
            $this->getBaseCustomFields($response['status']),
            [
                CustomFieldInstaller::WORK_ORDER_ID        => $requestData->get('workorder'),
                CustomFieldInstaller::CLEARING_REFERENCE   => $clearingReference,
                CustomFieldInstaller::CAPTURE_MODE         => AbstractPayonePaymentHandler::PAYONE_STATE_COMPLETED,
                CustomFieldInstaller::CLEARING_TYPE        => AbstractPayonePaymentHandler::PAYONE_CLEARING_FNC,
                CustomFieldInstaller::FINANCING_TYPE       => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPD,
                CustomFieldInstaller::USED_RATEPAY_SHOP_ID => $request['add_paydata[shop_id]'],
            ]
        ));

        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->dataHandler->logResponse($paymentTransaction, $salesChannelContext->getContext(), ['request' => $request, 'response' => $response]);
        $this->deviceFingerprintService->deleteDeviceIdentToken();
    }

    public function getValidationDefinitions(SalesChannelContext $salesChannelContext): array
    {
        $definitions = parent::getValidationDefinitions($salesChannelContext);

        $definitions['ratepayIban']     = [new NotBlank(), new Iban()];
        $definitions['ratepayBirthday'] = [new NotBlank(), new Birthday(['value' => $this->getMinimumDate()])];

        return $definitions;
    }

    /**
     * {@inheritdoc}
     */
    public static function isCapturable(array $transactionData, array $customFields): bool
    {
        if (static::isNeverCapturable($transactionData, $customFields)) {
            return false;
        }

        return static::isTransactionAppointedAndCompleted($transactionData) || static::matchesIsCapturableDefaults($transactionData, $customFields);
    }

    /**
     * {@inheritdoc}
     */
    public static function isRefundable(array $transactionData, array $customFields): bool
    {
        if (static::isNeverRefundable($transactionData, $customFields)) {
            return false;
        }

        return static::matchesIsRefundableDefaults($transactionData, $customFields);
    }
}