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

namespace EComprocessing\Genesis\Model\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Observer Class (called just before the Response on the Front Site is sent)
 * Used to overwrite the Exception on the Checkout Page
 *
 * Class ControllerFrontSendResponseBefore
 * @package EComprocessing\Genesis\Model\Observer
 */
class ControllerFrontSendResponseBefore implements ObserverInterface
{
    /**
     * @var \EComprocessing\Genesis\Helper\Data
     */
    protected $_moduleHelper;

    /**
     * @var \Magento\Framework\Webapi\ErrorProcessor
     */
    protected $_errorProcessor;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * SalesOrderPaymentPlaceEnd constructor.
     * @param \EComprocessing\Genesis\Helper\Data $moduleHelper
     * @param \Magento\Framework\Webapi\ErrorProcessor $errorProcessor
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \EComprocessing\Genesis\Helper\Data $moduleHelper,
        \Magento\Framework\Webapi\ErrorProcessor $errorProcessor,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->_moduleHelper = $moduleHelper;
        $this->_errorProcessor = $errorProcessor;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $response = $observer->getEvent()->getData('response');

            if ($response && $this->getShouldOverrideCheckoutException($response)) {
                /** @var \Magento\Framework\Webapi\Rest\Response $response */

                $maskedException = $this->getModuleHelper()->createWebApiException(
                    $this->getEComprocessingLastCheckoutError(),
                    \Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST
                );

                $response->setException($maskedException);
                $this->clearEComprocessingLastCheckoutError();
            }
        // @codingStandardsIgnoreStart
        } catch (\Exception $e) {
            /**
             * Just hide any exception (if occurs) when trying to override exception message
             */
        }
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param $response
     * @return bool
     */
    protected function getShouldOverrideCheckoutException($response)
    {
        return
            ($this->getEComprocessingLastCheckoutError()) &&
            ($response instanceof \Magento\Framework\Webapi\Rest\Response) &&
            (method_exists($response, 'isException')) &&
            ($response->isException());
    }

    /**
     * Retrieves the last error message from the session, which has occurred on the checkout page
     *
     * @return mixed
     */
    protected function getEComprocessingLastCheckoutError()
    {
        return $this->getCheckoutSession()->getEComprocessingLastCheckoutError();
    }

    /**
     * Clears the last error from the session, which occurs on the checkout page
     *
     * @return void
     */
    protected function clearEComprocessingLastCheckoutError()
    {
        $this->getCheckoutSession()->setEComprocessingLastCheckoutError(null);
    }

    /**
     * @return \EComprocessing\Genesis\Helper\Data
     */
    protected function getModuleHelper()
    {
        return $this->_moduleHelper;
    }

    /**
     * @return \Magento\Framework\Webapi\ErrorProcessor
     */
    protected function getErrorProcessor()
    {
        return $this->_errorProcessor;
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }
}
