<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Eps;

use PayonePayment\PaymentHandler\PayoneEpsPaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Struct\Struct;

class PreAuthorizeRequestParameterBuilder extends AuthorizeRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        return array_merge(parent::getRequestParameter($arguments), [
            'request' => 'preauthorization',
        ]);
    }

    /** @param PaymentTransactionStruct $arguments */
    public function supports(Struct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayoneEpsPaymentHandler::class && $action === self::REQUEST_ACTION_PREAUTHORIZE;
    }
}