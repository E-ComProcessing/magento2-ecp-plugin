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

namespace Ecomprocessing\Genesis\Controller\Ipn;

/**
 * Unified IPN controller for all supported Ecomprocessing Payment Methods
 * Class Index
 * @package Ecomprocessing\Genesis\Controller\Ipn
 */
class Index extends \Ecomprocessing\Genesis\Controller\AbstractAction
{
    /**
     * Get the name of the IPN Class, used to handle the posted Notification
     * It is separated per Payment Method
     *
     * @return null|string
     */
    protected function getIpnClassName()
    {
        switch (true) {
            case $this->isPostRequestExists('wpf_unique_id'):
                $className = 'CheckoutIpn';
                break;
            default:
                $className = null;
        }

        return $className;
    }

    /**
     * Instantiate IPN model and pass IPN request to it
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            return;
        }

        try {
            $postValues = $this->getPostRequest();

            $ipnClassName = $this->getIpnClassName();

            if (!isset($ipnClassName)) {
                $this->getResponse()->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN);

                return;
            }

            $ipn = $this->getObjectManager()->create(
                "Ecomprocessing\\Genesis\\Model\\Ipn\\{$ipnClassName}",
                ['data' => $postValues]
            );

            $responseBody = $ipn->handleGenesisNotification();
            $this
                ->getResponse()
                    ->setHeader('Content-type', 'application/xml')
                    ->setBody($responseBody)
                    ->setHttpResponseCode(\Magento\Framework\Webapi\Response::HTTP_OK)
                    ->sendResponse();
        } catch (\Exception $e) {
            $this->getLogger()->critical($e);
            $this->getResponse()->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_INTERNAL_ERROR);
        }
    }
}
