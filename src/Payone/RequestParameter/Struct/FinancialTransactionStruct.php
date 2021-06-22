<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Struct\Traits\DeterminationTrait;
use PayonePayment\Payone\RequestParameter\Struct\Traits\RequestDataTrait;
use PayonePayment\Payone\RequestParameter\Struct\Traits\TransactionTrait;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\ParameterBag;

class FinancialTransactionStruct extends Struct
{
    use DeterminationTrait;
    use TransactionTrait;
    use RequestDataTrait;

    /** @var Context */
    protected $context;

    public function __construct(
        PaymentTransaction $paymentTransaction,
        Context $context,
        ParameterBag $requestData,
        string $paymentMethod,
        string $action
    ) {
        $this->paymentTransaction = $paymentTransaction;
        $this->context            = $context;
        $this->requestData        = $requestData;
        $this->paymentMethod      = $paymentMethod;
        $this->action             = $action;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function setContext(Context $context): void
    {
        $this->context = $context;
    }
}