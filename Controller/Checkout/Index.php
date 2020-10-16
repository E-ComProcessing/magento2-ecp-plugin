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

namespace EComProcessing\Genesis\Controller\Checkout;

/**
 * Front Controller for Checkout Method
 * it does a redirect to the WPF
 * Class Index
 * @package EComProcessing\Genesis\Controller\Checkout
 */
class Index extends \EComProcessing\Genesis\Controller\AbstractCheckoutAction
{
    /**
     * Redirect to Genesis WPF
     *
     * @return void
     */
    public function execute()
    {

        $order = $this->getOrder();

        if (isset($order)) {
            $redirectUrl = $this->getCheckoutSession()->getEcomProcessingCheckoutRedirectUrl();

            if (isset($redirectUrl)) {
                $this->getResponse()->setRedirect($redirectUrl);
            } else {
                $this->redirectToCheckoutFragmentPayment();
            }
        }
    }
}
