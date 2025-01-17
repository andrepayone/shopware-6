<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;

class PayonePayolutionInstallment extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone Paysafe Pay Later Installment';

    /** @var string */
    protected $description = 'Installment payment by Paysafe Pay Later.';

    /** @var string */
    protected $paymentHandler = PayonePayolutionInstallmentPaymentHandler::class;

    /** @var null|string */
    protected $template = '@Storefront/storefront/payone/payolution/payolution-installment-form.html.twig';

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone Paysafe Pay Later Ratenkauf',
            'description' => 'Bezahlen Sie einfach und bequem in monatlichen Raten.',
        ],
        'en-GB' => [
            'name'        => 'Payone Paysafe Pay Later Installment',
            'description' => 'Easily pay in monthly installments.',
        ],
    ];

    /** @var int */
    protected $position = 104;
}
