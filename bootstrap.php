<?php
// @codingStandardsIgnoreStart
require 'dev/tests/unit/framework/bootstrap.php';

try {
    require 'app/code/EComprocessing/Genesis/vendor/autoload.php';
} catch (Exception $e) {
    /**
     * This will throw LogicException:
     * Module 'EComprocessing_Genesis' from
     * '/magento/app/code/EComprocessing/Genesis' has been already defined in
     * '/magento/app/code/EComprocessing/Genesis'.
     *
     * Ignoring this exception isn't a problem for phpunit tests
     */
}
// @codingStandardsIgnoreEnd
