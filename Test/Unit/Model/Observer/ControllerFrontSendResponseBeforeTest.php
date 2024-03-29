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

namespace Ecomprocessing\Genesis\Test\Unit\Model\Observer;

use Ecomprocessing\Genesis\Model\Observer\ControllerFrontSendResponseBefore;

/**
 * Class ControllerFrontSendResponseBeforeTest
 * @covers \Ecomprocessing\Genesis\Model\Observer\ControllerFrontSendResponseBefore
 * @package Ecomprocessing\Genesis\Test\Unit\Model\Observer
 */
class ControllerFrontSendResponseBeforeTest extends \Ecomprocessing\Genesis\Test\Unit\Model\Observer\AbstractObserverTest
{
    /**
     * @var \Ecomprocessing\Genesis\Model\Observer\ControllerFrontSendResponseBefore|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $observerInstance;

    /**
     * @return string
     */
    protected function getObserverClassName()
    {
        return ControllerFrontSendResponseBefore::class;
    }

    /**
     * @covers \Ecomprocessing\Genesis\Model\Observer\ControllerFrontSendResponseBefore::execute()
     */
    public function testExecuteNullResponse()
    {
        $this->observerMock->expects(self::once())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->eventMock->expects(self::once())
            ->method('getData')
            ->with('response')
            ->willReturn(null);

        $this->dataHelperMock->expects(self::never())
            ->method('createWebApiException');

        $this->restResponseMock->expects(self::never())
            ->method('setException');

        $this->getObserverInstance()->execute($this->observerMock);
    }

    /**
     * @covers \Ecomprocessing\Genesis\Model\Observer\ControllerFrontSendResponseBefore::execute()
     */
    public function testExecuteDoNotOverrideCheckoutException()
    {
        $this->restResponseMock->expects(self::once())
            ->method('isException')
            ->willReturn(false);

        $this->eventMock->expects(self::once())
            ->method('getData')
            ->with('response')
            ->willReturn($this->restResponseMock);

        $this->observerMock->expects(self::once())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->checkoutSessionMock->expects(self::atLeastOnce())
            ->method('getEcomprocessingLastCheckoutError')
            ->willReturn('Sample Error Message');

        $this->dataHelperMock->expects(self::never())
            ->method('createWebApiException');

        $this->restResponseMock->expects(self::never())
            ->method('setException');

        $this->getObserverInstance()->execute($this->observerMock);
    }

    /**
     * @covers \Ecomprocessing\Genesis\Model\Observer\ControllerFrontSendResponseBefore::execute()
     */
    public function testExecuteOverrideCheckoutException()
    {
        $checkoutErrorMessage='Checkout Error Message';

        $this->restResponseMock->expects(self::once())
            ->method('isException')
            ->willReturn(true);

        $this->eventMock->expects(self::once())
            ->method('getData')
            ->with('response')
            ->willReturn($this->restResponseMock);

        $this->observerMock->expects(self::once())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->checkoutSessionMock->expects(self::atLeastOnce())
            ->method('getEcomprocessingLastCheckoutError')
            ->willReturn($checkoutErrorMessage);

        $this->dataHelperMock->expects(self::once())
            ->method('createWebApiException')
            ->with(
                $checkoutErrorMessage,
                \Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST
            )
            ->willReturn($this->webapiException);

        $this->restResponseMock->expects(self::once())
            ->method('setException')
            ->with($this->webapiException);

        $this->getObserverInstance()->execute($this->observerMock);
    }
}
