<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;

class PayonePayolutionDebit extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone Paysafe Pay Later Debit';

    /** @var string */
    protected $description = 'SEPA Direct Debit by Paysafe Pay Later.';

    /** @var string */
    protected $paymentHandler = PayonePayolutionDebitPaymentHandler::class;

    /** @var null|string */
    protected $template = '@Storefront/storefront/payone/payolution/payolution-debit-form.html.twig';

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone Paysafe Pay Later Lastschrift',
            'description' => 'Gesicherte Lastschrift von Paysafe Pay Later.',
        ],
        'en-GB' => [
            'name'        => 'Payone Paysafe Pay Later Debit',
            'description' => 'SEPA Direct Debit by Paysafe Pay Later.',
        ],
    ];

    /** @var int */
    protected $position = 107;
}
