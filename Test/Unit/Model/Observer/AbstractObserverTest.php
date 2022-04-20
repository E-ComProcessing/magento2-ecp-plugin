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

namespace EComprocessing\Genesis\Test\Unit\Model\Observer;

use EComprocessing\Genesis\Helper\Data as DataHelper;
use Magento\Checkout\Model\Session;
use Magento\Framework\Webapi\ErrorProcessor;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Framework\Webapi\Rest\Response as RestResponse;
use Magento\Framework\Webapi\Exception as WebapiException;

/**
 * Class AbstractObserverTest
 * @package EComprocessing\Genesis\Test\Unit\Model\Observer
 */
abstract class AbstractObserverTest extends \EComprocessing\Genesis\Test\Unit\AbstractTestCase
{
    /**
     * @var \EComprocessing\Genesis\Model\Observer\ControllerFrontSendResponseBefore|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $observerInstance;

    /**
     * @var \EComprocessing\Genesis\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataHelperMock;

    /**
     * @var \Magento\Framework\Webapi\ErrorProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $errorProcessorMock;

    /**
     * @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutSessionMock;

    /**
     * @var \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $observerMock;

    /**
     * @var \Magento\Framework\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventMock;

    /**
     * @var \Magento\Framework\Webapi\Rest\Response|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $restResponseMock;

    /**
     * @var \Magento\Framework\Webapi\Exception|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $webapiException;

    abstract protected function getObserverClassName();

    /**
     * Gets observer's instance
     * @return \EComprocessing\Genesis\Model\Observer\ControllerFrontSendResponseBefore|
     * \EComprocessing\Genesis\Model\Observer\SalesOrderPaymentPlaceEnd
     */
    protected function getObserverInstance()
    {
        return $this->observerInstance;
    }

    /**
     * Get mock for data helper
     * @return \EComprocessing\Genesis\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDataHelperMock()
    {
        return $this->dataHelperMock = $this->getMockBuilder(DataHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['createWebApiException'])
            ->getMock();
    }

    /**
     * Get mock for error processor
     * @return \Magento\Framework\Webapi\ErrorProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getErrorProcessorMock()
    {
        return $this->errorProcessorMock = $this->getMockBuilder(ErrorProcessor::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
    }

    /**
     * Get mock for checkout session
     * @return \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCheckoutSessionMock()
    {
        return $this->checkoutSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEComprocessingLastCheckoutError','setEComprocessingLastCheckoutError'])
            ->getMock();
    }

    /**
     * Get mock for event observer
     * @return \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getObserverMock()
    {
        return $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvent'])
            ->getMock();
    }

    /**
     * Get mock for event
     * @return \Magento\Framework\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEventMock()
    {
        return $this->eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();
    }

    /**
     * Get mock for Webapi REST response
     * @return \Magento\Framework\Webapi\Rest\Response|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRestResponseMock()
    {
        return $this->restResponseMock = $this->getMockBuilder(RestResponse::class)
            ->disableOriginalConstructor()
            ->setMethods(['setException','isException'])
            ->getMock();
    }

    /**
     * Get mock for Webapi exception
     * @return \Magento\Framework\Webapi\Exception|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWebapiException()
    {
        return $this->webapiException = $this->getMockBuilder(WebapiException::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->getDataHelperMock();
        $this->getErrorProcessorMock();
        $this->getCheckoutSessionMock();
        $this->getObserverMock();
        $this->getEventMock();
        $this->getRestResponseMock();
        $this->getWebapiException();

        $this->observerMock->expects(self::once())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->observerInstance = $this->getObjectManagerHelper()->getObject(
            $this->getObserverClassName(),
            [
                'moduleHelper' => $this->dataHelperMock,
                'errorProcessor' => $this->errorProcessorMock,
                'checkoutSession' => $this->checkoutSessionMock
            ]
        );

        $this->assertInstanceOf(
            $this->getObserverClassName(),
            $this->getObserverInstance()
        );
    }
}
