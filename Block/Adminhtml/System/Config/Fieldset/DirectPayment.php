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

namespace EComProcessing\Genesis\Block\Adminhtml\System\Config\Fieldset;

/**
 * Renderer for E-Comprocessing Direct Panel in System Configuration
 *
 * Class DirectPayment
 * @package EComProcessing\Genesis\Block\Adminhtml\System\Config\Fieldset
 */
class DirectPayment extends \EComProcessing\Genesis\Block\Adminhtml\System\Config\Fieldset\Base\Payment
{
    /**
     * Retrieves the Module Panel Css Class
     * @return string
     */
    protected function getBlockHeadCssClass()
    {
        return "EComProcessingDirect";
    }
}
