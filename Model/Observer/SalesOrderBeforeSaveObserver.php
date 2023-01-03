<?php
/*
 * Copyright (C) 2021 E-Comprocessing Ltd.
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
 * @copyright   2021 E-Comprocessing Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EComprocessing\Genesis\Model\Observer;

use Magento\Framework\Event\ObserverInterface;
use EComprocessing\Genesis\Model\Method\Checkout as GenesisCheckoutPaymentMethod;
use EComprocessing\Genesis\Model\Method\Direct as GenesisDirectPaymentMethod;

class SalesOrderBeforeSaveObserver implements ObserverInterface
{
    /**
     * @var \EComprocessing\Genesis\Model\Config
     */
    protected $_configHelper;

    /**
     * @param \EComprocessing\Genesis\Model\Config $configHelper
     * @codeCoverageIgnore
     */
    public function __construct(
        \EComprocessing\Genesis\Model\Config $configHelper
    ) {
        $this->_configHelper = $configHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        $methodCode = $order->getPayment()->getMethodInstance()->getCode();

        if (!$this->_configHelper->getPaymentConfirmationEmailEnabled($methodCode)) {
            return $this;
        }

        if (in_array($methodCode, [GenesisCheckoutPaymentMethod::CODE, GenesisDirectPaymentMethod::CODE])) {
            $order->setCanSendNewEmailFlag(false);
            $order->setSendEmail(false);
        }

        return $this;
    }
}