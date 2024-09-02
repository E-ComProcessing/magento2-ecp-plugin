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

namespace Ecomprocessing\Genesis\Model\Ipn;

use Ecomprocessing\Genesis\Helper\Data;
use Ecomprocessing\Genesis\Model\Method\Checkout;
use Exception;
use Genesis\Api\Constants\Transaction\States;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * Checkout Method IPN Handler Class
 *
 * Class CheckoutIpn
 */
class CheckoutIpn extends AbstractIpn
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param Context                               $context
     * @param OrderFactory                          $orderFactory
     * @param OrderSender                           $orderSender
     * @param CreditmemoSender                      $creditMemoSender
     * @param LoggerInterface                       $logger
     * @param Data                                  $moduleHelper
     * @param OrderRepositoryInterface              $orderRepository
     * @param OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     * @param array                                 $data
     *
     * @throws NoSuchEntityException
     */
    public function __construct(
        Context                               $context,
        OrderFactory                          $orderFactory,
        OrderSender                           $orderSender,
        CreditmemoSender                      $creditMemoSender,
        LoggerInterface                       $logger,
        Data                                  $moduleHelper,
        OrderRepositoryInterface              $orderRepository,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository,
        array                                 $data = []
    ) {
        parent::__construct(
            $context,
            $orderFactory,
            $orderSender,
            $creditMemoSender,
            $logger,
            $moduleHelper,
            $orderStatusHistoryRepository,
            $data
        );

        $this->orderRepository = $orderRepository;
    }

    /**
     * Return the code of the payment method
     *
     * @return string
     */
    protected function getPaymentMethodCode()
    {
        return Checkout::CODE;
    }

    /**
     * Update Pending Transactions and Order Status
     *
     * @param stdClass $responseObject
     *
     * @throws Exception
     */
    public function processNotification($responseObject)
    {
        $recordedToCommentHistory = false;
        $payment                  = $this->getPayment();

        $this->getModuleHelper()->updateTransactionAdditionalInfo(
            $responseObject->unique_id,
            $responseObject,
            true
        );

        if (isset($responseObject->payment_transaction)) {
            $addToCommentHistory = $recordedToCommentHistory = true;
            $payment_transaction = $this->getModuleHelper()->populatePaymentTransaction(
                $responseObject,
                $payment->getEntityId()
            );

            $this->createIpnComment(
                $this->getTransactionMessage($payment_transaction),
                $addToCommentHistory
            );

            $payment
                ->setLastTransId(
                    $payment_transaction->unique_id
                )
                ->setTransactionId(
                    $payment_transaction->unique_id
                )
                ->setParentTransactionId(
                    $responseObject->unique_id
                )
                ->setIsTransactionPending(
                    $this->getShouldSetCurrentTranPending(
                        $payment_transaction
                    )
                )
                ->setShouldCloseParentTransaction(
                    true
                )
                ->setIsTransactionClosed(
                    $this->getShouldCloseCurrentTransaction(
                        $payment_transaction
                    )
                )
                ->setPreparedMessage(
                    __('Module') . ' ' . $this->getConfigHelper()->getCheckoutTitle()
                )
                ->resetTransactionAdditionalInfo();

            $this->getModuleHelper()->setPaymentTransactionAdditionalInfo(
                $payment,
                $payment_transaction
            );

            if (States::APPROVED == $payment_transaction->status) {
                $this->registerPaymentNotification(
                    $payment,
                    $payment_transaction
                );
            }

            $this->orderRepository->save($payment->getOrder());
        }

        if (!$recordedToCommentHistory) {
            $this->createIpnComment(
                $this->getTransactionMessage($responseObject),
                true
            );
        }

        $this->getModuleHelper()->setOrderState(
            $this->getOrder(),
            isset($payment_transaction)
                ? $payment_transaction->status
                : $responseObject->status
        );
    }

    /**
     * Register the payment notification
     *
     * @param OrderPaymentInterface $payment
     * @param stdClass              $payment_transaction
     *
     * @throws NoSuchEntityException
     */
    protected function registerPaymentNotification(
        OrderPaymentInterface $payment,
        stdClass              $payment_transaction
    ) {
        $transactionType = $payment_transaction->transaction_type;

        if ($this->getModuleHelper()->getShouldCreateAuthNotification($transactionType)) {
            $payment->registerAuthorizationNotification(
                $payment_transaction->amount
            );

            return;
        }

        if ($this->getModuleHelper()->getShouldCreateCaptureNotification($transactionType)) {
            $payment->registerCaptureNotification(
                $payment_transaction->amount
            );
        }
    }
}
