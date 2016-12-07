<?php
/*
 * Copyright (C) 2016 E-ComProcessing
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
 * @author      E-ComProcessing
 * @copyright   2016 E-ComProcessing Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EComProcessing\Genesis\Controller\Direct;

/**
 * Front Controller for Direct Method
 * it redirects to the 3D-Secure Form when applicable
 * Class Index
 * @package EComProcessing\Genesis\Controller\Direct
 */
class Index extends \EComProcessing\Genesis\Controller\AbstractCheckoutAction
{
    /**
     * Redirect to the 3D-Secure Form or to the Final Checkout Success Page
     *
     * @return void
     */
    public function execute()
    {
        $order = $this->getOrder();

        if (isset($order)) {
            $redirectUrl = $this->getCheckoutSession()->getEcomProcessingCheckoutRedirectUrl();

            if (isset($redirectUrl)) {
                $this->getCheckoutSession()->setEcomProcessingCheckoutRedirectUrl(null);
                $this->getResponse()->setRedirect($redirectUrl);
            } else {
                $this->redirectToCheckoutOnePageSuccess();
            }
        }
    }
}
