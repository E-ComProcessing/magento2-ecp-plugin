<?php
/*
 * Copyright (C) 2018 E-Comprocessing Ltd.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      E-Comprocessing
 * @copyright   2018 E-Comprocessing Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace Ecomprocessing\Genesis\Test\Unit\Model\Config\Source\Method\Checkout;

use Ecomprocessing\Genesis\Helper\Checkout;
use Ecomprocessing\Genesis\Helper\Data;
use Genesis\API\Constants\Transaction\Names;
use Genesis\API\Constants\Transaction\Parameters\Mobile\ApplePay\PaymentTypes as ApplePaymentTypes;
use Genesis\API\Constants\Transaction\Parameters\Mobile\GooglePay\PaymentTypes as GooglePaymentTypes;
use Genesis\API\Constants\Transaction\Parameters\Wallets\PayPal\PaymentTypes as PayPalPaymentTypes;
use \Genesis\API\Constants\Transaction\Types as GenesisTransactionTypes;
use \Genesis\API\Constants\Payment\Methods as GenesisPaymentMethods;

/**
 * Class TransactionTypeTest
 *
 * @covers \Ecomprocessing\Genesis\Model\Config\Source\Method\Checkout\TransactionType
 * @package Ecomprocessing\Genesis\Test\Unit\Model\Config\Source\Method\Checkout
 */
class TransactionTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers \Ecomprocessing\Genesis\Model\Config\Source\Method\Checkout\TransactionType::toOptionArray()
     */
    public function testToOptionArray()
    {
        $data        = [];
        $sourceModel = new \Ecomprocessing\Genesis\Model\Config\Source\Method\Checkout\TransactionType();

        $transactionTypes = GenesisTransactionTypes::getWPFTransactionTypes();
        $excludedTypes    = Checkout::getRecurringTransactionTypes();

        // Exclude PPRO transaction. This is not standalone transaction type
        array_push($excludedTypes, GenesisTransactionTypes::PPRO);
        // Exclude GooglePay transaction. In this way Google Pay Payment types will be introduced
        array_push($excludedTypes, GenesisTransactionTypes::GOOGLE_PAY);
        // Exclude PayPal transaction. In this way PayPal Payment types will be introduced
        array_push($excludedTypes, GenesisTransactionTypes::PAY_PAL);
        // Exclude Apple Pay transaction.
        array_push($excludedTypes, GenesisTransactionTypes::APPLE_PAY);

        // Exclude Transaction Types
        $transactionTypes = array_diff($transactionTypes, $excludedTypes);

        // Add PPRO types
        $pproTypes = array_map(
            function ($type) {
                return $type . Data::PPRO_TRANSACTION_SUFFIX;
            },
            GenesisPaymentMethods::getMethods()
        );

        // Add Google Payment types
        $googlePayTypes = array_map(
            function ($type) {
                return Data::GOOGLE_PAY_TRANSACTION_PREFIX . $type;
            },
            [
                GooglePaymentTypes::AUTHORIZE,
                GooglePaymentTypes::SALE
            ]
        );

        // Add PayPal Payment types
        $payPalTypes = array_map(
            function ($type) {
                return Data::PAYPAL_TRANSACTION_PREFIX . $type;
            },
            [
                PayPalPaymentTypes::AUTHORIZE,
                PayPalPaymentTypes::SALE,
                PayPalPaymentTypes::EXPRESS
            ]
        );

        // Add Apple Pay types
        $applePayTypes = array_map(
            function ($type) {
                return Data::APPLE_PAY_TRANSACTION_PREFIX . $type;
            },
            [
                ApplePaymentTypes::AUTHORIZE,
                ApplePaymentTypes::SALE
            ]
        );

        $transactionTypes = array_merge(
            $transactionTypes,
            $pproTypes,
            $googlePayTypes,
            $payPalTypes,
            $applePayTypes
        );
        asort($transactionTypes);

        foreach ($transactionTypes as $type) {
            $name = Names::getName($type);
            if (!GenesisTransactionTypes::isValidTransactionType($type)) {
                $name = strtoupper($type);
            }

            array_push(
                $data,
                [
                    'value' => $type,
                    'label' => __($name)
                ]
            );
        }

        $this->assertEquals(
            $data,
            $sourceModel->toOptionArray()
        );
    }
}
