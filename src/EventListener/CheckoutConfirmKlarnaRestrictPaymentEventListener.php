<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentHandler\AbstractKlarnaPaymentHandler;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmKlarnaRestrictPaymentEventListener implements EventSubscriberInterface
{
    private const ALLOWED_COUNTRIES = ['AT', 'DK', 'FI', 'DE', 'NL', 'NO', 'SE', 'CH'];
    private const ALLOWED_B2B_COUNTRIES = ['FI', 'DE', 'NO', 'SE'];
    private const ALLOWED_CURRENCIES = ['EUR', 'DKK', 'NOK', 'SEK' . 'CHF'];


    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'hidePaymentMethod',
            AccountPaymentMethodPageLoadedEvent::class => 'hidePaymentMethod',
            AccountEditOrderPageLoadedEvent::class => 'hidePaymentMethod',
        ];
    }

    /**
     * @param CheckoutConfirmPageLoadedEvent|AccountPaymentMethodPageLoadedEvent|AccountEditOrderPageLoadedEvent $event
     * @return void
     */
    public function hidePaymentMethod(PageLoadedEvent $event)
    {
        $context = $event->getSalesChannelContext();
        $customer = $context->getCustomer();

        $billingAddress = $customer ? $customer->getActiveBillingAddress() : null;

        if ($this->isCurrencyAllowed($context->getCurrency()->getIsoCode()) &&
            (!$billingAddress || $this->isAddressAllowed($billingAddress))
        ) {
            return;
        }

        $event->getPage()->setPaymentMethods($this->removeKlarnaMethods($event->getPage()->getPaymentMethods()));
    }

    /**
     * @param CustomerAddressEntity|OrderAddressEntity $address
     */
    private function isAddressAllowed($address): bool
    {
        return in_array($address->getCountry()->getIso(), self::ALLOWED_COUNTRIES)
            && (!$address->getCompany() || in_array($address->getCountry()->getIso(), self::ALLOWED_B2B_COUNTRIES));
    }

    private function isCurrencyAllowed(string $currencyCode): bool
    {
        return in_array($currencyCode, self::ALLOWED_CURRENCIES);
    }

    private function removeKlarnaMethods(PaymentMethodCollection $paymentMethodCollection): PaymentMethodCollection
    {
        return $paymentMethodCollection->filter(static function (PaymentMethodEntity $paymentMethod) {
            return !is_subclass_of($paymentMethod->getHandlerIdentifier(), AbstractKlarnaPaymentHandler::class);
        });
    }
}
