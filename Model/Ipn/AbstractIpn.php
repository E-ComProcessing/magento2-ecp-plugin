<?php
/*
 * Copyright (C) 2016 E-Comprocessing
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
 * @copyright   2016 E-Comprocessing Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EComProcessing\Genesis\Model\Ipn;

/**
 * Base IPN Handler Class
 *
 * Class AbstractIpn
 * @package EComProcessing\Genesis\Model\Ipn
 */
abstract class AbstractIpn
{

    /**
     * @var \Magento\Framework\App\Action\Context
     */
    private $_context;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;
    /**
     * @var \EComProcessing\Genesis\Helper\Data
     */
    private $_moduleHelper;
    /**
     * @var \EComProcessing\Genesis\Model\Config
     */
    private $_configHelper;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $_orderFactory;
    /**
     * @var array
     */
    private $_ipnRequest;
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender
     */
    protected $_creditMemoSender;

    /**
     * Get Payment Solution Code (used to create an instance of the Config Object)
     * @return string
     */
    abstract protected function getPaymentMethodCode();

    /**
     * Update / Create Transactions; Updates Order Status
     * @param \stdClass $responseObject
     * @return void
     */
    abstract protected function processNotification($responseObject);

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender $creditMemoSender
     * @param \Psr\Log\LoggerInterface $logger
     * @param \EComProcessing\Genesis\Helper\Data $moduleHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender $creditMemoSender,
        \Psr\Log\LoggerInterface $logger,
        \EComProcessing\Genesis\Helper\Data $moduleHelper,
        array $data = []
    ) {
        $this->_context = $context;
        $this->_orderFactory = $orderFactory;
        $this->_orderSender = $orderSender;
        $this->_creditMemoSender = $creditMemoSender;
        $this->_logger = $logger;
        $this->_moduleHelper = $moduleHelper;
        $this->_configHelper =
            $this->_moduleHelper->getMethodConfig(
                $this->getPaymentMethodCode()
            );
        $this->_ipnRequest = $data;
    }

    /**
     * Get IPN Post Request Params or Param Value
     * @param string|null $key
     * @return array|string|null
     */
    protected function getIpnRequestData($key = null)
    {
        if ($key == null) {
            return $this->_ipnRequest;
        }

        return isset($this->_ipnRequest->{$key}) ? $this->_ipnRequest->{$key} : null;
    }

    /**
     *
     * @return null|string (null => failed; responseText => success)
     * @throws \Exception
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handleGenesisNotification()
    {
        $this->_configHelper->initGatewayClient();

        $notification = new \Genesis\API\Notification(
            $this->getIpnRequestData()
        );

        if ($notification->isAuthentic()) {
            $notification->initReconciliation();
        }

        $responseObject = $notification->getReconciliationObject();

        if (!isset($responseObject->unique_id)) {
            return null;
        } else {
            $this->setOrderByReconcile($responseObject);

            try {
                $this->processNotification($responseObject);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $comment = $this->createIpnComment(__('Note: %1', $e->getMessage()), true);
                $comment->save();
                throw $e;
            }

            return $notification->generateResponse();
        }
    }

    /**
     * Load order
     *
     * @return \Magento\Sales\Model\Order
     * @throws \Exception
     */
    protected function getOrder()
    {
        if (!isset($this->_order) || empty($this->_order->getId())) {
            throw new \Exception('IPN-Order is not set to an instance of an object');
        }

        return $this->_order;
    }

    /**
     * Get an Instance of the Magento Payment Object
     * @return \Magento\Sales\Api\Data\OrderPaymentInterface|mixed|null
     * @throws \Exception
     */
    protected function getPayment()
    {
        return $this->getOrder()->getPayment();
    }

    /**
     * Initializes the Order Object from the transaction in the Reconcile response object
     * @param $responseObject
     * @throws \Exception
     */
    private function setOrderByReconcile($responseObject)
    {
        $transaction_id = $responseObject->transaction_id;
        list($incrementId, $hash) = explode('_', $transaction_id);

        $this->_order = $this->getOrderFactory()->create()->loadByIncrementId(
            intval($incrementId)
        );

        if (!$this->_order->getId()) {
            throw new \Exception(sprintf('Wrong order ID: "%s".', $incrementId));
        }
    }

    /**
     * Generate an "IPN" comment with additional explanation.
     * Returns the generated comment or order status history object
     *
     * @param string|null $message
     * @param bool $addToHistory
     * @return string|\Magento\Sales\Model\Order\Status\History
     */
    protected function createIpnComment($message = null, $addToHistory = false)
    {
        if ($addToHistory && !empty($message)) {
            $message = $this->getOrder()->addStatusHistoryComment($message);
            $message->setIsCustomerNotified(null);
        }
        return $message;
    }

    /**
     * Get an instance of the Module Config Helper Object
     * @return \EComProcessing\Genesis\Model\Config
     */
    protected function getConfigHelper()
    {
        return $this->_configHelper;
    }

    /**
     * Get an instance of the Magento Action Context Object
     * @return \Magento\Framework\App\Action\Context
     */
    protected function getContext()
    {
        return $this->_context;
    }

    /**
     * Get an instance of the Magento Logger Interface
     * @return \Psr\Log\LoggerInterface
     */
    protected function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Get an Instance of the Module Helper Object
     * @return \EComProcessing\Genesis\Helper\Data
     */
    protected function getModuleHelper()
    {
        return $this->_moduleHelper;
    }

    /**
     * Get an Instance of the magento Order Factory Object
     * @return \Magento\Sales\Model\OrderFactory
     */
    protected function getOrderFactory()
    {
        return $this->_orderFactory;
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
     * @param \stdClass $responseObject
     * @return bool
     */
    protected function getShouldCloseCurrentTransaction($responseObject)
    {
        $voidableTransactions = [
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE,
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D
        ];

        /*
         *  It the last transaction is closed, it cannot be voided
         */
        return !in_array($responseObject->transaction_type, $voidableTransactions);
    }
}
