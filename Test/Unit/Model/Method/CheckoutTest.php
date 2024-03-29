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

namespace Ecomprocessing\Genesis\Test\Unit\Model\Method;

use Ecomprocessing\Genesis\Helper\Data;
use Ecomprocessing\Genesis\Model\Method\Checkout as CheckoutPaymentMethod;
use Genesis\API\Constants\Payment\Methods;
use Genesis\API\Constants\Payment\Methods as GenesisPaymentMethods;
use Genesis\API\Constants\Transaction\Types as GenesisTransactionTypes;

/**
 * Class CheckoutTest
 * @covers \Ecomprocessing\Genesis\Model\Method\Checkout
 * @package Ecomprocessing\Genesis\Test\Unit\Model\Method
 */
class CheckoutTest extends \Ecomprocessing\Genesis\Test\Unit\Model\Method\AbstractMethodTest
{
    protected function getPaymentMethodClassName()
    {
        return CheckoutPaymentMethod::class;
    }

    /**
     * @covers CheckoutPaymentMethod::getConfigPaymentAction()
     */
    public function testGetConfigPaymentAction()
    {
        $this->assertEquals(
            \Magento\Payment\Model\Method\AbstractMethod::ACTION_ORDER,
            $this->getPaymentMethodInstance()->getConfigPaymentAction()
        );
    }

    /**
     * @covers CheckoutPaymentMethod::getCheckoutTransactionTypes()
     */
    public function testGetCheckoutTransactionTypes()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with("payment/{$this->getPaymentMethodCode()}/transaction_types", 'store', null)
            ->willReturn(
                implode(
                    ',',
                    [
                        GenesisTransactionTypes::ALIPAY,
                        GenesisTransactionTypes::EZEEWALLET,
                        GenesisTransactionTypes::IDEBIT_PAYIN,
                        GenesisTransactionTypes::INPAY,
                        GenesisTransactionTypes::INSTA_DEBIT_PAYIN,
                        GenesisTransactionTypes::P24,
                        GenesisTransactionTypes::PAYPAL_EXPRESS,
                        GenesisTransactionTypes::TRUSTLY_SALE,
                        GenesisTransactionTypes::WECHAT,
                        GenesisTransactionTypes::ONLINE_BANKING_PAYIN,
                        GenesisTransactionTypes::SDD_SALE,
                        GenesisTransactionTypes::CITADEL_PAYIN,
                        GenesisTransactionTypes::AUTHORIZE,
                        GenesisTransactionTypes::AUTHORIZE_3D,
                        GenesisTransactionTypes::SALE,
                        GenesisTransactionTypes::SALE_3D,
                        GenesisTransactionTypes::ABNIDEAL,
                        GenesisTransactionTypes::CASHU,
                        GenesisTransactionTypes::EZEEWALLET,
                        GenesisTransactionTypes::NETELLER,
                        GenesisTransactionTypes::POLI,
                        GenesisTransactionTypes::WEBMONEY,
                        GenesisTransactionTypes::PAYBYVOUCHER_SALE,
                        GenesisTransactionTypes::PAYSAFECARD,
                        GenesisTransactionTypes::SOFORT,
                        Methods::EPS . Data::PPRO_TRANSACTION_SUFFIX,
                        Methods::GIRO_PAY . Data::PPRO_TRANSACTION_SUFFIX,
                        Methods::PRZELEWY24 . Data::PPRO_TRANSACTION_SUFFIX,
                        Methods::SAFETY_PAY . Data::PPRO_TRANSACTION_SUFFIX,
                        Methods::BCMC . Data::PPRO_TRANSACTION_SUFFIX,
                        Methods::MYBANK . Data::PPRO_TRANSACTION_SUFFIX,
                        Methods::IDEAL . Data::PPRO_TRANSACTION_SUFFIX
                    ]
                )
            );

        $transactionTypes = $this->getPaymentMethodInstance()->getCheckoutTransactionTypes();

        $this->assertEquals(
            [
                GenesisTransactionTypes::AUTHORIZE,
                GenesisTransactionTypes::AUTHORIZE_3D,
                GenesisTransactionTypes::SALE,
                GenesisTransactionTypes::SALE_3D,
                GenesisTransactionTypes::ABNIDEAL,
                GenesisTransactionTypes::ALIPAY,
                GenesisTransactionTypes::CASHU,
                GenesisTransactionTypes::CITADEL_PAYIN,
                GenesisTransactionTypes::EZEEWALLET,
                GenesisTransactionTypes::EZEEWALLET,
                GenesisTransactionTypes::IDEBIT_PAYIN,
                GenesisTransactionTypes::INPAY,
                GenesisTransactionTypes::INSTA_DEBIT_PAYIN,
                GenesisTransactionTypes::NETELLER,
                GenesisTransactionTypes::ONLINE_BANKING_PAYIN,
                GenesisTransactionTypes::P24,
                GenesisTransactionTypes::PAYBYVOUCHER_SALE,
                GenesisTransactionTypes::PAYPAL_EXPRESS,
                GenesisTransactionTypes::PAYSAFECARD,
                GenesisTransactionTypes::POLI,
                GenesisTransactionTypes::SDD_SALE,
                GenesisTransactionTypes::PPRO => [
                    'name' => GenesisTransactionTypes::PPRO,
                    'parameters' => [
                        [
                            'payment_method' => GenesisPaymentMethods::BCMC,
                        ],
                        [
                            'payment_method' => GenesisPaymentMethods::EPS
                        ],
                        [
                            'payment_method' => GenesisPaymentMethods::GIRO_PAY,
                        ],
                        [
                            'payment_method' => GenesisPaymentMethods::IDEAL,
                        ],
                        [
                            'payment_method' => GenesisPaymentMethods::MYBANK,
                        ],
                        [
                            'payment_method' => GenesisPaymentMethods::PRZELEWY24,
                        ],
                        [
                            'payment_method' => GenesisPaymentMethods::SAFETY_PAY,
                        ]
                    ]
                ],
                GenesisTransactionTypes::SOFORT,
                GenesisTransactionTypes::TRUSTLY_SALE,
                GenesisTransactionTypes::WEBMONEY,
                GenesisTransactionTypes::WECHAT,
            ],
            $transactionTypes
        );
    }

    /**
     * @covers CheckoutPaymentMethod::order()
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSuccessfulOrder()
    {
        $orderId = $this->getGeneratedOrderId();

        $this->scopeConfigMock->expects(static::exactly(4))
            ->method('getValue')
            ->will($this->returnCallback([$this, 'configCallback']));

        $orderMock = $this->getOrderMock();

        $this->paymentMock->expects(static::once())
            ->method('getOrder')
            ->willReturn($orderMock);

        $orderMock->expects(self::once())
            ->method('getIncrementId')
            ->willReturn($orderId);

        $this->dataHelperMock->expects(self::once())
            ->method('genTransactionId')
            ->with($orderId)
            ->willReturn(
                sprintf(
                    '%s-%s',
                    $orderId,
                    sha1(uniqid())
                )
            );

        $orderMock->expects(self::never())
            ->method('getRemoteIp');

        $this->dataHelperMock->expects(self::once())
            ->method('buildOrderUsage')
            ->willReturn('Magento2 Payment Transaction');

        $this->dataHelperMock->expects(self::once())
            ->method('buildOrderDescriptionText')
            ->with($orderMock)
            ->willReturn('1 x Product Name');

        $this->dataHelperMock->expects(self::once())
            ->method('getLocale')
            ->willReturn('en');

        /**
         * @var $quote \Magento\Quote\Model\Quote|\PHPUnit_Framework_MockObject_MockObject
         */
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerEmail'])
            ->getMock();

        $quote->expects(self::once())
            ->method('getCustomerEmail')
            ->willReturn(
                $this->getFakerObject()->email
            );

        $this->checkoutSessionMock->expects(self::any())
            ->method('getQuote')
            ->willReturn($quote);

        $orderMock->expects(static::once())
            ->method('getBaseCurrencyCode')
            ->willReturn('USD');

        $orderMock->expects(static::once())
            ->method('getBillingAddress')
            ->willReturn(
                $this->getOrderAddressMock()
            );

        $orderMock->expects(static::once())
            ->method('getShippingAddress')
            ->willReturn(
                $this->getOrderAddressMock()
            );

        $this->dataHelperMock->expects(self::once())
            ->method('getNotificationUrl')
            ->with(
                $this->getPaymentMethodInstance()->getCode()
            )
            ->willReturn(
                static::SAMPLE_NOTIFICATION_URL
            );

        $this->dataHelperMock->expects(self::exactly(3))
            ->method('getReturnUrl')
            ->withConsecutive(
                [
                    $this->getPaymentMethodInstance()->getCode(),
                    \Ecomprocessing\Genesis\Helper\Data::ACTION_RETURN_SUCCESS
                ],
                [
                    $this->getPaymentMethodInstance()->getCode(),
                    \Ecomprocessing\Genesis\Helper\Data::ACTION_RETURN_CANCEL
                ],
                [
                    $this->getPaymentMethodInstance()->getCode(),
                    \Ecomprocessing\Genesis\Helper\Data::ACTION_RETURN_FAILURE
                ]
            )
            ->willReturnOnConsecutiveCalls(
                static::SAMPLE_RETURN_SUCCESS_URL,
                static::SAMPLE_RETURN_CANCEL_URL,
                static::SAMPLE_RETURN_FAILURE_URL
            );

        $gatewayResponse = $this->getSampleGatewayResponse(
            \Genesis\API\Constants\Transaction\States::NEW_STATUS,
            null,
            null,
            null,
            [
                'redirect_url' => static::SAMPLE_REDIRECT_URL
            ]
        );

        $this->dataHelperMock->expects(static::once())
            ->method('getGatewayResponseObject')
            ->willReturn(
                $gatewayResponse
            );

        $this->dataHelperMock->expects(self::once())
            ->method('executeGatewayRequest')
            ->with(
                $this->isInstanceOf(\Genesis\Genesis::class)
            )
            ->willReturnArgument(0);

        $this->paymentMock->expects(self::once())
            ->method('setTransactionId')
            ->with(
                $gatewayResponse->unique_id
            )
            ->willReturnSelf();

        $this->paymentMock->expects(static::once())
            ->method('setIsTransactionClosed')
            ->with(false)
            ->willReturnSelf();

        $this->paymentMock->expects(static::once())
            ->method('setIsTransactionPending')
            ->with(true)
            ->willReturnSelf();

        $this->paymentMock->expects(self::once())
            ->method('setTransactionAdditionalInfo')
            ->with(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                $this->dataHelperMock->getArrayFromGatewayResponse(
                    $gatewayResponse
                )
            )
            ->willReturnSelf();

        $this->checkoutSessionMock->expects(self::once())
            ->method('setEcomprocessingCheckoutRedirectUrl')
            ->with(
                $gatewayResponse->redirect_url
            )
            ->willReturnSelf();

        $this->getPaymentMethodInstance()->order(
            $this->paymentMock,
            self::ORDER_AMOUNT
        );
    }

    /**
     * Scope Config Method Settings values
     *
     * @param ...$args
     * @return string
     */
    public function configCallback(...$args)
    {
        if ($args[0] === "payment/{$this->getPaymentMethodCode()}/transaction_types") {
            return implode(
                ',',
                [
                    GenesisTransactionTypes::AUTHORIZE,
                    GenesisTransactionTypes::SOFORT,
                ]
            );
        }

        if ($args[0] === "payment/{$this->getPaymentMethodCode()}/sca_exemption") {
            return 'low_value';
        }

        if ($args[0] === "payment/{$this->getPaymentMethodCode()}/sca_exemption_code") {
            return '100';
        }

        return '';
    }
}
