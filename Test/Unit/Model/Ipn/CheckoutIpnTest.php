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

namespace EComprocessing\Genesis\Test\Unit\Model\Ipn;

use EComprocessing\Genesis\Model\Ipn\CheckoutIpn;
use EComprocessing\Genesis\Helper\Data as DataHelper;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Class CheckoutIpnTest
 * @covers \EComprocessing\Genesis\Model\Ipn\Checkout
 * @package EComprocessing\Genesis\Test\Unit\Model\Ipn
 */

class CheckoutIpnTest extends \EComprocessing\Genesis\Test\Unit\Model\Ipn\AbstractIpnTest
{
    const UNIQUE_ID_NAME                    = 'wpf_unique_id';

    const TRANSACTION_ID                    = '12345678901234567890123456789012';
    const CUSTOMER_PWD                      = '1234567890123456789012345678901234567890';

    const RECONCILIATION_TRANSACTION_ID     = '123_456';
    const RECONCILIATION_TRANSACTION_TYPE   = \Genesis\API\Constants\Transaction\Types::AUTHORIZE;
    const RECONCILIATION_MESSAGE            = 'sample reconciliation message';
    const RECONCILIATION_AMOUNT             = 271.97;

    /**
     * @var customerPwd
     */
    protected $customerPwd;

    /**
     * Gets IPN model class name
     * @return string
     */
    protected function getIpnClassName()
    {
        return CheckoutIpn::class;
    }

    /**
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->setPostParams();

        parent::setUp();
    }

    /**
     * Set IPN POST params and customer's password for the gateway
     */
    protected function setPostParams()
    {
        $transactionId = self::TRANSACTION_ID;
        $customerPwd = self::CUSTOMER_PWD;

        $signature = self::createSignature($transactionId, $customerPwd);

        $this->postParams = [
            self::UNIQUE_ID_NAME => $transactionId,
            'signature' => $signature
        ];
        $this->customerPwd = $customerPwd;
    }

    /**
     * Creates reconciliation object
     * @return \stdClass
     */
    protected function createReconciliationObj()
    {
        $this->reconciliationObj = new \stdClass();
        $this->reconciliationObj->unique_id             = $this->postParams[self::UNIQUE_ID_NAME];
        $this->reconciliationObj->transaction_id        = self::RECONCILIATION_TRANSACTION_ID;
        $this->reconciliationObj->status                = \Genesis\API\Constants\Transaction\States::APPROVED;
        $this->reconciliationObj->message               = __('Module') . ' ' . self::RECONCILIATION_MESSAGE;
        $this->reconciliationObj->transaction_type      = self::RECONCILIATION_TRANSACTION_TYPE;
        $this->reconciliationObj->amount                = self::RECONCILIATION_AMOUNT;
        $this->reconciliationObj->payment_transaction   = $this->reconciliationObj;

        return $this->reconciliationObj;
    }

    /**
     * Get mock for data helper
     * @return \EComprocessing\Genesis\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDataHelperMock()
    {
        $this->dataHelperMock = $this->getMockBuilder(DataHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getMethodConfig',
                    'createNotificationObject',
                    'updateTransactionAdditionalInfo',
                    'setOrderState'
                ]
            )
            ->getMock();

        $this->dataHelperMock->expects(self::once())
            ->method('getMethodConfig')
            ->with(\EComprocessing\Genesis\Model\Method\Checkout::CODE)
            ->willReturn(
                $this->configHelperMock
            );

        $this->dataHelperMock->expects(self::once())
            ->method('createNotificationObject')
            ->with($this->postParams)
            ->willReturn($this->notificationMock);

        return $this->dataHelperMock;
    }

    /**
     * Get mock for payment
     * @return \Magento\Sales\Api\Data\OrderPaymentInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPaymentMock()
    {
        $paymentMock = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setLastTransId',
                'setTransactionId',
                'setParentTransactionId',
                'setShouldCloseParentTransaction',
                'setIsTransactionPending',
                'setIsTransactionClosed',
                'setPreparedMessage',
                'resetTransactionAdditionalInfo',
                'setTransactionAdditionalInfo',
                'registerAuthorizationNotification',
                'registerCaptureNotification',
                'save'
            ])
            ->getMockForAbstractClass();

        $paymentMock->expects(self::once())
            ->method('setLastTransId')
            ->with($this->reconciliationObj->unique_id)
            ->willReturnSelf();

        $paymentMock->expects(self::once())
            ->method('setTransactionId')
            ->with($this->reconciliationObj->unique_id)
            ->willReturnSelf();

        $paymentMock->expects(self::once())
            ->method('setParentTransactionId')
            ->with($this->reconciliationObj->unique_id)
            ->willReturnSelf();

        $paymentMock->expects(self::once())
            ->method('setShouldCloseParentTransaction')
            ->with(true)
            ->willReturnSelf();

        $paymentMock->expects(self::once())
            ->method('setIsTransactionPending')
            ->with(
                $this->getShouldSetCurrentTranPending($this->reconciliationObj)
            )
            ->willReturnSelf();

        $paymentMock->expects(self::once())
            ->method('setIsTransactionClosed')
            ->with(
                $this->getShouldCloseCurrentTransaction($this->reconciliationObj)
            )
            ->willReturnSelf();

        $paymentMock->expects(self::once())
            ->method('setPreparedMessage')
            ->with($this->reconciliationObj->message)
            ->willReturnSelf();

        $paymentMock->expects(self::once())
            ->method('resetTransactionAdditionalInfo')
            ->willReturnSelf();

        $paymentMock->expects(self::once())
            ->method('setTransactionAdditionalInfo')
            ->with('raw_details_info')
            ->willReturn(null);

        $paymentMock->expects(
            $this->getShouldExecuteAuthoirizeCaptureEvent($this->reconciliationObj->status)
        )->method(
            $this->getNotificationFunctionName(
                $this->reconciliationObj->transaction_type
            )
        )->with($this->reconciliationObj->amount)
            ->willReturn(null);

        return $paymentMock;
    }
}
