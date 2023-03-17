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

namespace Ecomprocessing\Genesis\Test\Unit\Model\Ipn;

use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order\Status\History;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Psr\Log\LoggerInterface as Logger;
use Ecomprocessing\Genesis\Helper\Data as DataHelper;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Sales\Model\Order;
use Genesis\API\Notification as Notification;

/**
 * Class AbstractIpnTest
 * @package Ecomprocessing\Genesis\Test\Unit\Model\Ipn
 */

abstract class AbstractIpnTest extends \Ecomprocessing\Genesis\Test\Unit\AbstractTestCase
{
    /**
     * @var \Ecomprocessing\Genesis\Model\Ipn\CheckoutIpn|\Ecomprocessing\Genesis\Model\Ipn\DirectIpn
     */
    protected $ipnInstance;

    /**
     * @var array $postParams
     */
    protected $postParams;

    /**
     * @var \stdClass $reconciliationObj
     */
    protected $reconciliationObj;

    /**
     * @var string $customerPwd
     */
    protected $customerPwd;

    /**
     * @var \Magento\Framework\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Sales\Model\OrderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderFactoryMock;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderSenderMock;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $creditmemoSenderMock;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerMock;

    /**
     * @var \Ecomprocessing\Genesis\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataHelperMock;

    /**
     * @var \Genesis\API\Notification|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $notificationMock;

    /**
     * @var \Ecomprocessing\Genesis\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configHelperMock;

    /**
     * Gets IPN model class name
     * @return string
     */
    abstract protected function getIpnClassName();

    /**
     * Creates reconciliation object
     * @return \stdClass
     */
    abstract protected function createReconciliationObj();

    /**
     * Get mock for data helper
     * @return \Ecomprocessing\Genesis\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    abstract protected function getDataHelperMock();

    /**
     * Get mock for payment
     * @return \Magento\Sales\Api\Data\OrderPaymentInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    abstract protected function getPaymentMock();

    /**
     * Gets IPN model instance
     * @return \Ecomprocessing\Genesis\Model\Ipn\CheckoutIpn|\Ecomprocessing\Genesis\Model\Ipn\DirectIpn
     */
    protected function getIpnInstance()
    {
        return $this->ipnInstance;
    }

    /**
     * Creates signature param for the IPN POST request
     * @param string $unique_id
     * @param string $customerPwd
     * @return string
     */
    protected static function createSignature($unique_id, $customerPwd)
    {
        return hash('sha1', $unique_id . $customerPwd);
    }

    /**
     * Get mock for context
     * @return \Magento\Framework\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getContextMock()
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock($this->contextMock);

        return $this->contextMock;
    }

    /**
     * Get mock for order factory
     * @return \Magento\Sales\Model\OrderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getOrderFactoryMock()
    {
        $this->orderFactoryMock = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->orderFactoryMock->expects(self::once())
            ->method('create')
                ->willReturn($this->getOrderMock());

        return $this->orderFactoryMock;
    }

    /**
     * Get mock for order sender
     * @return \Magento\Sales\Model\Order\Email\Sender\OrderSender|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getOrderSenderMock()
    {
        return $this->orderSenderMock = $this->getMockBuilder(OrderSender::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
    }

    /**
     * Get mock for credit memo sedner
     * @return \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCreditmemoSenderMock()
    {
        return $this->creditmemoSenderMock = $this->getMockBuilder(CreditmemoSender::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
    }

    /**
     * Get mock for logger
     * @return \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLoggerMock()
    {
        return $this->loggerMock = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMockForAbstractClass();
    }

    /**
     * Get mock for order
     * @return \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getOrderMock()
    {
        list($incrementId) = explode('_', $this->reconciliationObj->transaction_id);

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadByIncrementId','getId','getPayment', 'addStatusHistoryComment'])
            ->getMock();

        $orderMock->expects(self::atLeastOnce())
            ->method('getId')
                ->willReturn(1);

        $orderMock->expects(self::once())
            ->method('getPayment')
                ->willReturn($this->getPaymentMock());

        $orderMock->expects(self::once())
            ->method('loadByIncrementId', 'getId')
            ->with($incrementId)
                ->willReturn($orderMock);

        $orderMock->expects(self::once())
            ->method('addStatusHistoryComment')
                ->willReturn($this->getOrderStatusHistoryMock());

        return $orderMock;
    }

    /**
     * Get Mock for OrderStatusHistory
     *
     * @return \Magento\Sales\Model\Order\Status\History|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getOrderStatusHistoryMock()
    {
        $statusHistory = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->setMethods(['setIsCustomerNotified'])
            ->getMock();

        $statusHistory->expects(self::once())
            ->method('setIsCustomerNotified')
                ->willReturnSelf();

        return $statusHistory;
    }

    /**
     * Get notification function name based on transaction type
     * @param string $transaction_type
     * @return string
     */
    protected function getNotificationFunctionName($transaction_type)
    {
        $result=null;
        switch ($transaction_type) {
            case \Genesis\API\Constants\Transaction\Types::AUTHORIZE:
            case \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D:
                $result = 'registerAuthorizationNotification';
                break;
            case \Genesis\API\Constants\Transaction\Types::SALE:
            case \Genesis\API\Constants\Transaction\Types::SALE_3D:
                $result = 'registerCaptureNotification';
                break;
            default:
                break;
        }
        return $result;
    }

    /**
     * @param \stdClass $responseObject
     * @return bool
     */
    protected function getShouldSetCurrentTranPending($responseObject)
    {
        return
            $responseObject->status != \Genesis\API\Constants\Transaction\States::APPROVED;
    }

    /**
     * Get if Authorize or Capture event should be executed
     *
     * @param $status
     *
     * @return \PHPUnit\Framework\MockObject\Matcher\InvokedCount
     */
    protected function getShouldExecuteAuthoirizeCaptureEvent($status)
    {
        if (\Genesis\API\Constants\Transaction\States::APPROVED == $status) {
            return self::once();
        }

        return self::never();
    }

    /**
     * @param \stdClass $responseObject
     * @return bool
     */
    protected function getShouldCloseCurrentTransaction($responseObject)
    {
        $voidableTransactions = [
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE,
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D
        ];

        return !in_array($responseObject->transaction_type, $voidableTransactions);
    }

    /**
     * mock function replacing Ecomprocessing\Genesis\Model\Config::initGatewayClient
     */
    protected function initGatewayClientMock()
    {
        \Genesis\Config::setPassword(
            $this->customerPwd
        );
    }

    /**
     * Get mock for notification
     * @return \Genesis\API\Notification|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getNotificationMock()
    {
        $reconciliationObj = $this->createReconciliationObj();

        $this->notificationMock = $this->createMock(
            Notification::class,
            [
                'isAuthentic',
                'initReconciliation',
                'getReconciliationObject'
            ],
            [
                $this->postParams
            ]
        );

        $this->notificationMock->expects(self::once())
            ->method('isAuthentic')
                ->willReturn(true);

        $this->notificationMock->expects(self::once())
            ->method('initReconciliation')
                ->willReturn(new \stdClass());

        $this->notificationMock->expects(self::once())
            ->method('getReconciliationObject')
                ->willReturn($reconciliationObj);

        return $this->notificationMock;
    }

    /**
     * Get mock for model config
     * @return \Ecomprocessing\Genesis\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigHelperMock()
    {
        $this->configHelperMock = $this->getMockBuilder('Ecomprocessing\Genesis\Model\Config')
            ->disableOriginalConstructor()
            ->setMethods(['initGatewayClient', 'initReconciliation', 'getCheckoutTitle'])
            ->getMock();

        $this->configHelperMock->expects(self::once())
            ->method('initGatewayClient')
                ->willReturn($this->initGatewayClientMock());

        $this->configHelperMock->expects(self::never())
            ->method('initReconciliation')
                ->willReturn($this->getNotificationMock());

        $this->configHelperMock->expects(self::atLeastOnce())
            ->method('getCheckoutTitle')
                ->willReturn('sample reconciliation message');

        return $this->configHelperMock;
    }

    /**
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->ipnInstance = $this->getObjectManagerHelper()->getObject(
            $this->getIpnClassName(),
            $this->createParams()
        );

        $this->assertInstanceOf(
            $this->getIpnClassName(),
            $this->getIpnInstance()
        );
    }

    /**
     * @covers \Ecomprocessing\Genesis\Model\Ipn\AbstractIpn::handleGenesisNotification()
     */
    public function testGenesisNotification()
    {
        $this->ipnInstance->handleGenesisNotification();
    }

    /**
     * Creates constructor parameters
     * @return array
     */
    public function createParams()
    {
        $this->getConfigHelperMock();

        $this->getContextMock();
        $this->getOrderFactoryMock();
        $this->getOrderSenderMock();
        $this->getCreditmemoSenderMock();
        $this->getLoggerMock();
        $this->getDataHelperMock();

        $this->assertInstanceOf(
            Context::class,
            $this->contextMock
        );

        $this->assertInstanceOf(
            OrderFactory::class,
            $this->orderFactoryMock
        );

        $this->assertInstanceOf(
            OrderSender::class,
            $this->orderSenderMock
        );

        $this->assertInstanceOf(
            CreditmemoSender::class,
            $this->creditmemoSenderMock
        );

        $this->assertInstanceOf(
            Logger::class,
            $this->loggerMock
        );

        $this->assertInstanceOf(
            DataHelper::class,
            $this->dataHelperMock
        );

        $constructorParams = [
            'context'           => $this->contextMock,
            'orderFactory'      => $this->orderFactoryMock,
            'orderSender'       => $this->orderSenderMock,
            'creditmemoSender'  => $this->creditmemoSenderMock,
            'logger'            => $this->loggerMock,
            'moduleHelper'      => $this->dataHelperMock,
            'data'              => $this->postParams
        ];

        return $constructorParams;
    }
}
