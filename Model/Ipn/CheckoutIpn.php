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

/**
 * Checkout Method IPN Handler Class
 * Class CheckoutIpn
 * @package Ecomprocessing\Genesis\Model\Ipn
 */
class CheckoutIpn extends \Ecomprocessing\Genesis\Model\Ipn\AbstractIpn
{
    /**
     * @return string
     */
    protected function getPaymentMethodCode()
    {
        return \Ecomprocessing\Genesis\Model\Method\Checkout::CODE;
    }

    /**
     * Update Pending Transactions and Order Status
     * @param \stdClass $responseObject
     * @throws \Exception
     */
    protected function processNotification($responseObject)
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
                ->resetTransactionAdditionalInfo(

                );

            $this->getModuleHelper()->setPaymentTransactionAdditionalInfo(
                $payment,
                $payment_transaction
            );

            if (\Genesis\API\Constants\Transaction\States::APPROVED == $payment_transaction->status) {
                $this->registerPaymentNotification(
                    $payment,
                    $payment_transaction
                );
            }

            $payment->save();
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
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface $payment
     * @param \stdClass $payment_transaction
     */
    protected function registerPaymentNotification(
        \Magento\Sales\Api\Data\OrderPaymentInterface $payment,
        \stdClass $payment_transaction
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
