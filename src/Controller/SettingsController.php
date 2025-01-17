<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use DateInterval;
use DateTimeImmutable;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\PaymentHandler as Handler;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\TestCredentialsStruct;
use PayonePayment\StoreApi\Route\ApplePayRoute;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Grouping\FieldGrouping;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class SettingsController extends AbstractController
{
    private const REFERENCE_PREFIX_TEST = 'TESTPO-';

    /** @var PayoneClientInterface */
    private $client;

    /** @var RequestParameterFactory */
    private $requestFactory;

    /** @var EntityRepositoryInterface */
    private $stateMachineTransitionRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $kernelDirectory;

    public function __construct(
        PayoneClientInterface $client,
        RequestParameterFactory $requestFactory,
        EntityRepositoryInterface $stateMachineTransitionRepository,
        LoggerInterface $logger,
        string $kernelDirectory
    ) {
        $this->client                           = $client;
        $this->requestFactory                   = $requestFactory;
        $this->stateMachineTransitionRepository = $stateMachineTransitionRepository;
        $this->logger                           = $logger;
        $this->kernelDirectory                  = $kernelDirectory;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/_action/payone_payment/validate-api-credentials", name="api.action.payone_payment.validate.api.credentials", methods={"POST"})
     * @Route("/api/v{version}/_action/payone_payment/validate-api-credentials", name="api.action.payone_payment.validate.api.credentials.legacy", methods={"POST"})
     */
    public function validateApiCredentials(Request $request, Context $context): JsonResponse
    {
        $testCount = 0;
        $errors    = [];

        /** @var EntityRepositoryInterface $paymentMethodRepository */
        $paymentMethodRepository = $this->get('payment_method.repository');

        foreach (ConfigurationPrefixes::CONFIGURATION_PREFIXES as $paymentClass => $configurationPrefix) {
            /** @var null|PaymentMethodEntity $paymentMethod */
            $criteria      = (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', $paymentClass));
            $paymentMethod = $paymentMethodRepository->search($criteria, $context)->first();

            if (!$paymentMethod || !$paymentMethod->getActive()) {
                continue;
            }

            ++$testCount;

            try {
                $parameters  = array_merge($this->getPaymentParameters($paymentClass), $this->getConfigurationParameters($request, $paymentClass));
                $testRequest = $this->requestFactory->getRequestParameter(new TestCredentialsStruct($parameters, AbstractRequestParameterBuilder::REQUEST_ACTION_TEST));

                $this->client->request($testRequest);
            } catch (Throwable $exception) {
                $errors[$configurationPrefix] = true;
            }
        }

        $this->logger->info('payone plugin credentials validated', [
            'success' => empty($errors),
            'results' => $errors,
        ]);

        return new JsonResponse([
            'testCount'        => $testCount,
            'credentialsValid' => empty($errors),
            'errors'           => $errors,
        ]);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/_action/payone_payment/get-state-machine-transition-actions", name="api.action.payone_payment.get.state_machine_transition.actions", methods={"GET"})
     * @Route("/api/v{version}/_action/payone_payment/get-state-machine-transition-actions", name="api.action.payone_payment.get.state_machine_transition.actions.legacy", methods={"GET"})
     */
    public function getStateMachineTransitionActions(Request $request, Context $context): JsonResponse
    {
        $criteria = (new Criteria())
            ->addAssociation('stateMachine')
            ->addFilter(new EqualsFilter('stateMachine.technicalName', 'order_transaction.state'))
            ->addGroupField(new FieldGrouping('actionName'));

        $searchResult    = $this->stateMachineTransitionRepository->search($criteria, $context);
        $transitionNames = [];

        if (count($searchResult->getElements()) > 0) {
            foreach ($searchResult->getElements() as $stateMachineAction) {
                $transitionNames[] = [
                    'label' => $stateMachineAction->getActionName(),
                    'value' => $stateMachineAction->getActionName(),
                ];
            }
        }

        return new JsonResponse(['data' => $transitionNames, 'total' => count($transitionNames)]);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/_action/payone_payment/check-apple-pay-cert", name="api.action.payone_payment.check.apple_pay_cert", methods={"GET"})
     * @Route("/api/v{version}/_action/payone_payment/check-apple-pay-cert", name="api.action.payone_payment.check.apple_pay_cert.legacy", methods={"GET"})
     */
    public function checkApplePayCert(): JsonResponse
    {
        if (!file_exists($this->kernelDirectory . ApplePayRoute::CERT_FOLDER . 'merchant_id.key')) {
            return new JsonResponse(['success' => false], 404);
        }

        if (!file_exists($this->kernelDirectory . ApplePayRoute::CERT_FOLDER . 'merchant_id.pem')) {
            return new JsonResponse(['success' => false], 404);
        }

        return new JsonResponse(['success' => true], 200);
    }

    private function getPaymentParameters(string $paymentClass): array
    {
        switch ($paymentClass) {
            case Handler\PayoneCreditCardPaymentHandler::class:
                return [
                    'request'        => 'preauthorization',
                    'clearingtype'   => 'cc',
                    'amount'         => 100,
                    'currency'       => 'EUR',
                    'reference'      => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'cardpan'        => '5500000000000004',
                    'pseudocardpan'  => '5500000000099999',
                    'cardtype'       => 'M',
                    'cardexpiredate' => (new DateTimeImmutable())->add(new DateInterval('P1Y'))->format('ym'),
                    'ecommercemode'  => 'internet',
                    'firstname'      => 'Test',
                    'lastname'       => 'Test',
                    'country'        => 'DE',
                    'successurl'     => 'https://www.payone.com',
                ];

            case Handler\PayoneDebitPaymentHandler::class:
                return [
                    'request'           => 'preauthorization',
                    'clearingtype'      => 'elv',
                    'iban'              => 'DE00123456782599100003',
                    'bic'               => 'TESTTEST',
                    'bankaccountholder' => 'Test Test',
                    'amount'            => 100,
                    'currency'          => 'EUR',
                    'reference'         => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'firstname'         => 'Test',
                    'lastname'          => 'Test',
                    'country'           => 'DE',
                    'successurl'        => 'https://www.payone.com',
                ];

            case Handler\PayonePaypalExpressPaymentHandler::class:
            case Handler\PayonePaypalPaymentHandler::class:
                return [
                    'request'      => 'preauthorization',
                    'clearingtype' => 'wlt',
                    'wallettype'   => 'PPE',
                    'amount'       => 100,
                    'currency'     => 'EUR',
                    'reference'    => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'firstname'    => 'Test',
                    'lastname'     => 'Test',
                    'country'      => 'DE',
                    'successurl'   => 'https://www.payone.com',
                ];

            case Handler\PayoneSofortBankingPaymentHandler::class:
                return [
                    'request'                => 'preauthorization',
                    'clearingtype'           => 'sb',
                    'onlinebanktransfertype' => 'PNT',
                    'bankcountry'            => 'DE',
                    'amount'                 => 100,
                    'currency'               => 'EUR',
                    'reference'              => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'firstname'              => 'Test',
                    'lastname'               => 'Test',
                    'country'                => 'DE',
                    'successurl'             => 'https://www.payone.com',
                ];

            case Handler\PayoneEpsPaymentHandler::class:
                return [
                    'request'                => 'preauthorization',
                    'clearingtype'           => 'sb',
                    'onlinebanktransfertype' => 'EPS',
                    'bankcountry'            => 'AT',
                    'bankgrouptype'          => 'ARZ_HTB',
                    'amount'                 => 100,
                    'currency'               => 'EUR',
                    'reference'              => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'firstname'              => 'Test',
                    'lastname'               => 'Test',
                    'country'                => 'AT',
                    'successurl'             => 'https://www.payone.com',
                ];

            case Handler\PayoneIDealPaymentHandler::class:
                return [
                    'request'                => 'preauthorization',
                    'clearingtype'           => 'sb',
                    'onlinebanktransfertype' => 'IDL',
                    'bankcountry'            => 'NL',
                    'bankgrouptype'          => 'ING_BANK',
                    'amount'                 => 100,
                    'currency'               => 'EUR',
                    'reference'              => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'firstname'              => 'Test',
                    'lastname'               => 'Test',
                    'country'                => 'NL',
                    'successurl'             => 'https://www.payone.com',
                ];

            case Handler\PayonePayolutionInvoicingPaymentHandler::class:
                return [
                    'request'                   => 'genericpayment',
                    'clearingtype'              => 'fnc',
                    'financingtype'             => 'PYV',
                    'add_paydata[action]'       => 'pre_check',
                    'add_paydata[payment_type]' => 'Payolution-Invoicing',
                    'amount'                    => 10000,
                    'currency'                  => 'EUR',
                    'reference'                 => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'birthday'                  => '19900505',
                    'firstname'                 => 'Test',
                    'lastname'                  => 'Test',
                    'country'                   => 'DE',
                    'email'                     => 'test@example.com',
                    'street'                    => 'teststreet 2',
                    'zip'                       => '12345',
                    'city'                      => 'Test',
                    'ip'                        => '127.0.0.1',
                ];

            case Handler\PayonePayolutionDebitPaymentHandler::class:
                return [
                    'request'                   => 'genericpayment',
                    'clearingtype'              => 'fnc',
                    'financingtype'             => 'PYD',
                    'add_paydata[action]'       => 'pre_check',
                    'add_paydata[payment_type]' => 'Payolution-Debit',
                    'amount'                    => 10000,
                    'currency'                  => 'EUR',
                    'reference'                 => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'birthday'                  => '19900505',
                    'firstname'                 => 'Test',
                    'lastname'                  => 'Test',
                    'country'                   => 'DE',
                    'email'                     => 'test@example.com',
                    'street'                    => 'teststreet 2',
                    'zip'                       => '12345',
                    'city'                      => 'Test',
                    'ip'                        => '127.0.0.1',
                    'iban'                      => 'DE00123456782599100004',
                    'bic'                       => 'TESTTEST',
                ];

            case Handler\PayonePayolutionInstallmentPaymentHandler::class:
                return [
                    'request'                   => 'genericpayment',
                    'clearingtype'              => 'fnc',
                    'financingtype'             => 'PYS',
                    'add_paydata[action]'       => 'pre_check',
                    'add_paydata[payment_type]' => 'Payolution-Installment',
                    'amount'                    => 10000,
                    'currency'                  => 'EUR',
                    'reference'                 => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'birthday'                  => '19900505',
                    'firstname'                 => 'Test',
                    'lastname'                  => 'Test',
                    'country'                   => 'DE',
                    'email'                     => 'test@example.com',
                    'street'                    => 'teststreet 2',
                    'zip'                       => '12345',
                    'city'                      => 'Test',
                    'ip'                        => '127.0.0.1',
                ];

            case Handler\PayonePrepaymentPaymentHandler::class:
                return [
                    'request'      => 'preauthorization',
                    'clearingtype' => 'vor',
                    'amount'       => 10000,
                    'currency'     => 'EUR',
                    'reference'    => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'firstname'    => 'Test',
                    'lastname'     => 'Test',
                    'country'      => 'DE',
                    'email'        => 'test@example.com',
                    'street'       => 'teststreet 2',
                    'zip'          => '12345',
                    'city'         => 'Test',
                    'ip'           => '127.0.0.1',
                ];

            case Handler\PayoneTrustlyPaymentHandler::class:
                return [
                    'request'                => 'preauthorization',
                    'clearingtype'           => 'sb',
                    'onlinebanktransfertype' => 'TRL',
                    'iban'                   => 'DE00123456782599100004',
                    'amount'                 => 100,
                    'currency'               => 'EUR',
                    'reference'              => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'firstname'              => 'Test',
                    'lastname'               => 'Test',
                    'country'                => 'DE',
                    'successurl'             => 'https://www.payone.com',
                ];

            case Handler\PayoneSecureInvoicePaymentHandler::class:
                return [
                    'request'          => 'preauthorization',
                    'clearingtype'     => 'rec',
                    'financingtype'    => 'POV',
                    'amount'           => 10000,
                    'currency'         => 'EUR',
                    'reference'        => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'birthday'         => '19900505',
                    'firstname'        => 'Test',
                    'lastname'         => 'Test',
                    'country'          => 'DE',
                    'email'            => 'test@example.com',
                    'street'           => 'teststreet 2',
                    'zip'              => '12345',
                    'city'             => 'Test',
                    'ip'               => '127.0.0.1',
                    'businessrelation' => 'b2c',
                ];

            case Handler\PayonePaydirektPaymentHandler::class:
                return [
                    'request'                             => 'genericpayment',
                    'clearingtype'                        => 'wlt',
                    'wallettype'                          => 'PDT',
                    'amount'                              => 10000,
                    'currency'                            => 'EUR',
                    'reference'                           => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'add_paydata[action]'                 => 'checkout',
                    'add_paydata[type]'                   => 'order',
                    'add_paydata[web_url_shipping_terms]' => 'https://www.payone.com',
                    'successurl'                          => 'https://www.payone.com',
                    'backurl'                             => 'https://www.payone.com',
                    'errorurl'                            => 'https://www.payone.com',
                ];

            case Handler\PayoneApplePayPaymentHandler::class:
                //TODO: Test request for apple pay is failing because of missing token params, we will use prepayment request to validate specific merchant data
                return [
                    'request'      => 'preauthorization',
                    'clearingtype' => 'vor',
                    'amount'       => 10000,
                    'currency'     => 'EUR',
                    'reference'    => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'firstname'    => 'Test',
                    'lastname'     => 'Test',
                    'country'      => 'DE',
                    'email'        => 'test@example.com',
                    'street'       => 'teststreet 2',
                    'zip'          => '12345',
                    'city'         => 'Test',
                    'ip'           => '127.0.0.1',
                ];

            default:
                $this->logger->error(sprintf('There is no test data defined for payment class %s', $paymentClass));
                throw new RuntimeException(sprintf('There is no test data defined for payment class %s', $paymentClass));
        }
    }

    private function getConfigurationParameters(Request $request, string $paymentClass): array
    {
        $config = $request->get('credentials', []);
        $prefix = ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentClass];

        if (!isset($config[$prefix])) {
            $this->logger->error(sprintf('There is no configuration for payment class %s', $paymentClass));

            throw new RuntimeException(sprintf('There is no configuration for payment class %s', $paymentClass));
        }

        return [
            'aid'         => $config[$prefix]['accountId'],
            'mid'         => $config[$prefix]['merchantId'],
            'portalid'    => $config[$prefix]['portalId'],
            'key'         => $config[$prefix]['portalKey'],
            'api_version' => '3.10',
            'mode'        => 'test',
            'encoding'    => 'UTF-8',
        ];
    }
}
