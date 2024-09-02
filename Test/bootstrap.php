<?php
// @codingStandardsIgnoreStart
require 'dev/tests/unit/framework/bootstrap.php';

try {
    require 'app/code/Ecomprocessing/Genesis/vendor/autoload.php';
} catch (Exception $e) {
    /**
     * This will throw LogicException:
     * Module 'Ecomprocessing_Genesis' from
     * '/magento/app/code/Ecomprocessing/Genesis' has been already defined in
     * '/magento/app/code/Ecomprocessing/Genesis'.
     *
     * Ignoring this exception isn't a problem for phpunit tests
     */
}
// @codingStandardsIgnoreEnd
