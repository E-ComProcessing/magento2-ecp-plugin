<?php
/*
 * Copyright (C) 2018-2024 E-Comprocessing Ltd.
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
 * @copyright   2018-2024 E-Comprocessing Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace Ecomprocessing\Genesis\Test\Unit\Helper;

use Ecomprocessing\Genesis\Block\Frontend\Config as FrontendConfig;
use Ecomprocessing\Genesis\Helper\Data as EcomprocessingDataHelper;
use Ecomprocessing\Genesis\Model\ConfigFactory;
use Ecomprocessing\Genesis\Test\Unit\AbstractTestCase;
use Exception;
use Genesis\Api\Constants\Transaction\States as GenesisTransactionStates;
use Genesis\Api\Constants\Transaction\Types as GenesisTransactionTypes;
use Genesis\Api\Constants\i18n;
use Genesis\Config as GenesisConfig;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Webapi\Exception as WebApiException;
use Magento\Payment\Helper\Data as PaymentData;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use stdClass;

/**
 * Test Data class
 *
 * Class DataTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class DataTest extends AbstractTestCase
{
    /**
     * @var EcomprocessingDataHelper
     */
    protected $moduleHelper;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var Store|MockObject
     */
    protected $storeMock;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilderMock;

    /**
     * @var Resolver|MockObject
     */
    protected $localeResolverMock;

    /**
     * @var Transaction
     */
    protected $transactionMock;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepositoryMock;

    /**
     * @var EcomprocessingDataHelper
     */
    protected $dataInstance;

    /**
     * @var (PaymentData&MockObject)|MockObject
     */
    protected $paymentDataMock;

    /**
     * @var (ConfigFactory&MockObject)|MockObject
     */
    protected $configFactoryMock;

    /**
     * @var (Session&MockObject)|MockObject
     */
    protected $customerSessionMock;

    /**
     * @var (FrontendConfig&MockObject)|MockObject
     */
    protected $configMock;

    /**
     * @return Transaction|MockObject
     */
    protected function getPaymentTransactionMock()
    {
        return $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAdditionalInformation', 'save', 'setIsClosed', 'load', 'getId'])
            ->getMock();
    }

    /**
     * @return (PaymentData&MockObject)|MockObject
     */
    protected function getPaymentDataMock()
    {
        return $this->getMockBuilder(PaymentData::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return (ConfigFactory&MockObject)|MockObject
     */
    protected function getConfigFactoryMock()
    {
        return $this->getMockBuilder(ConfigFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return (Session&MockObject)|MockObject
     */
    protected function getCustomerSessionMock()
    {
        return $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return (FrontendConfig&MockObject)|MockObject
     */
    protected function getConfigMock()
    {
        return $this->getMockBuilder(FrontendConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return (TransactionRepositoryInterface&MockObject)|MockObject
     */
    protected function getPaymentTransactionRepositoryMock()
    {
        return $this->getMockBuilder(TransactionRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save', 'getList', 'get', 'delete', 'create'])
            ->getMock();
    }

    /**
     * @return (OrderRepositoryInterface&MockObject)|MockObject
     */
    protected function getOrderRepositoryMock()
    {
        return $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save', 'get', 'getList', 'delete'])
            ->getMock();
    }

    /**
     * @return Order|MockObject
     */
    protected function getOrderMock()
    {
        $orderConfigMock = $this->getMockBuilder(OrderConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStateDefaultStatus'])
            ->getMock();

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getConfig',
                    'setState',
                    'setStatus',
                    'getInvoiceCollection',
                    'registerCancellation',
                    'setCustomerNoteNotify',
                ]
            )
            ->getMock();

        $orderMock->expects(static::any())
            ->method('getConfig')
            ->willReturn($orderConfigMock);

        $orderMock->expects(static::atLeastOnce())
            ->method('setStatus')
            ->willReturn($orderMock);

        $orderMock->expects(static::atLeastOnce())
            ->method('setState')
            ->willReturn($orderMock);

        return $orderMock;
    }

    /**
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBasicMocks();
        $this->setUpContextMock();
        $this->setUpStoreManagerMock();
        $this->orderRepositoryMock       = $this->getOrderRepositoryMock();
        $this->transactionMock           = $this->getPaymentTransactionMock();
        $this->transactionRepositoryMock = $this->getPaymentTransactionRepositoryMock();
        $this->paymentDataMock           = $this->getPaymentDataMock();
        $this->configFactoryMock         = $this->getConfigFactoryMock();
        $this->customerSessionMock       = $this->getCustomerSessionMock();
        $this->configMock                = $this->getConfigMock();

        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock->method('create')
            ->with(Transaction::class)
            ->willReturn($this->transactionMock);

        $this->moduleHelper = $this->getMockBuilder(EcomprocessingDataHelper::class)
            ->setConstructorArgs([
                'transactionRepository'     => $this->transactionRepositoryMock,
                'objectManager'             => $this->objectManagerMock,
                'context'                   => $this->contextMock,
                'storeManager'              => $this->storeManagerMock,
                'localeResolver'            => $this->localeResolverMock,
                'orderRepository'           => $this->orderRepositoryMock,
                'paymentData'               => $this->paymentDataMock,
                'configFactory'             => $this->configFactoryMock,
                'customerSession'           => $this->customerSessionMock,
                'config'                    => $this->configMock,
            ])
            ->onlyMethods(['getPaymentTransaction', 'setTransactionAdditionalInfo'])
            ->getMock();
    }

    /**
     * Sets up basic mock objects used in other Context and StoreManager mocks.
     */
    protected function setUpBasicMocks()
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->getMock();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilderMock = $this->getMockBuilder(\Magento\Framework\Url::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUrl'])
            ->getMock();

        $this->localeResolverMock = $this->getMockBuilder(Resolver::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLocale'])
            ->getMock();
    }

    /**
     * Sets up Context mock
     */
    protected function setUpContextMock()
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getScopeConfig',
                    'getUrlBuilder'
                ]
            )
            ->getMock();

        $this->contextMock->expects(static::any())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfigMock);

        $this->contextMock->expects(static::any())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilderMock);
    }

    /**
     * Sets up StoreManager mock.
     */
    protected function setUpStoreManagerMock()
    {
        $this->storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManager::class)
            ->disableOriginalConstructor()
            ->addMethods(['getUrlBuilder'])
            ->onlyMethods(['getStore'])
            ->getMock();

        $this->storeManagerMock->expects(static::any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeManagerMock->expects(static::any())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilderMock);
    }

    /**
     * @covers EcomprocessingDataHelper::getNotificationUrl()
     */
    public function testGetNotificationUrl()
    {
        $data = [
            'routePath'  => 'ecomprocessing/ipn',
            'domainName' => 'magento2-domain-here.com',
            'urls'       => [
                [
                    'secure' => true,
                    'protocol' => 'https'
                ],
                [
                    'secure' => false,
                    'protocol' => 'http'
                ],

                [
                    'secure' => null,
                    'protocol' => 'https'
                ],
            ]
        ];

        $this->storeMock->expects(static::once())
            ->method('isCurrentlySecure')
            ->willReturn(true);

        $conditions = [];
        $returns    = [];

        foreach ($data['urls'] as $index => $notificationUrlData) {
            $conditions[$index] = [
                'ecomprocessing/ipn',
                [
                    '_store'  =>
                        $this->storeMock,
                    '_secure' =>
                        $notificationUrlData['secure'] === null
                            ? true
                            : $notificationUrlData['secure']
                ]
            ];
            $returns[$index] = "{$notificationUrlData['protocol']}://{$data['domainName']}/{$data['routePath']}/index/";
        }

        $this->urlBuilderMock->expects(static::exactly(count($data['urls'])))
            ->method('getUrl')
            ->withConsecutive(...$conditions)
            ->willReturnOnConsecutiveCalls($returns);

        foreach ($data['urls'] as $notificationUrlData) {
            $this->moduleHelper->getNotificationUrl(
                $notificationUrlData['secure']
            );
        }
    }

    /**
     * @covers EcomprocessingDataHelper::genTransactionId()
     */
    public function testGenTransactionId()
    {
        $orderId = 20;

        $transactionId = $this->moduleHelper->genTransactionId($orderId);

        $this->assertStringStartsWith("{$orderId}-", $transactionId);

        $anotherTransactionId = $this->moduleHelper->genTransactionId($orderId);

        $this->assertNotEquals($transactionId, $anotherTransactionId);
    }

    /**
     * @covers EcomprocessingDataHelper::getTransactionAdditionalInfoValue()
     */
    public function testGetTransactionAdditionalInfoValue()
    {
        $transactionMock = $this->getPaymentTransactionMock();

        $transactionMock->expects(static::exactly(3))
            ->method('getAdditionalInformation')
            ->with(Transaction::RAW_DETAILS)
            ->willReturn(
                [
                    EcomprocessingDataHelper::ADDITIONAL_INFO_KEY_REDIRECT_URL     =>
                        'https://example.com/redirect/url',
                    EcomprocessingDataHelper::ADDITIONAL_INFO_KEY_STATUS           =>
                        GenesisTransactionStates::PENDING_ASYNC,
                    EcomprocessingDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE =>
                        GenesisTransactionTypes::AUTHORIZE_3D
                ]
            );

        $this->assertEquals(
            GenesisTransactionStates::PENDING_ASYNC,
            $this->moduleHelper->getTransactionStatus(
                $transactionMock
            )
        );

        $this->assertEquals(
            GenesisTransactionTypes::AUTHORIZE_3D,
            $this->moduleHelper->getTransactionTypeByTransaction(
                $transactionMock
            )
        );

        $this->assertNull(
            $this->moduleHelper->getTransactionTerminalToken(
                $transactionMock
            )
        );
    }

    /**
     * @covers EcomprocessingDataHelper::getTransactionAdditionalInfoValue()
     */
    public function testGetPaymentAdditionalInfoValue()
    {
        /**
         * @var $paymentMock InfoInterface|MockObject
         */
        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTransactionAdditionalInfo'])
            ->getMock();

        $paymentMock->expects(static::exactly(2))
            ->method('getTransactionAdditionalInfo')
            ->willReturn(
                [
                    Transaction::RAW_DETAILS => [
                        EcomprocessingDataHelper::ADDITIONAL_INFO_KEY_STATUS           =>
                            GenesisTransactionStates::APPROVED,
                        EcomprocessingDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE =>
                            GenesisTransactionTypes::AUTHORIZE
                    ]
                ]
            );

        $this->assertEquals(
            GenesisTransactionTypes::AUTHORIZE,
            $this->moduleHelper->getPaymentAdditionalInfoValue(
                $paymentMock,
                EcomprocessingDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE
            )
        );

        $this->assertNull(
            $this->moduleHelper->getPaymentAdditionalInfoValue(
                $paymentMock,
                EcomprocessingDataHelper::ADDITIONAL_INFO_KEY_REDIRECT_URL
            )
        );
    }

    /**
     * @covers EcomprocessingDataHelper::setTokenByPaymentTransaction()
     *
     * @return void
     */
    public function testSetTokenByPaymentTransaction()
    {
        $declinedSaleTransactionMock = $this->getPaymentTransactionMock();

        $declinedSaleTransactionMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(Transaction::RAW_DETAILS)
            ->willReturn(
                [
                    EcomprocessingDataHelper::ADDITIONAL_INFO_KEY_STATUS           =>
                        GenesisTransactionStates::DECLINED,
                    EcomprocessingDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE =>
                        GenesisTransactionTypes::SALE,
                ]
            );

        $this->moduleHelper->setTokenByPaymentTransaction($declinedSaleTransactionMock);

        $this->assertNull(GenesisConfig::getToken());

        $gatewayTerminalToken = 'gateway_token_098f6bcd4621d373cade4e832627b4f6';

        $approvedSaleTransactionMock = $this->getPaymentTransactionMock();

        $approvedSaleTransactionMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(Transaction::RAW_DETAILS)
            ->willReturn(
                [
                    EcomprocessingDataHelper::ADDITIONAL_INFO_KEY_STATUS           =>
                        GenesisTransactionStates::APPROVED,
                    EcomprocessingDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE =>
                        GenesisTransactionTypes::SALE,
                    EcomprocessingDataHelper::ADDITIONAL_INFO_KEY_TERMINAL_TOKEN   =>
                        $gatewayTerminalToken
                ]
            );

        $this->moduleHelper->setTokenByPaymentTransaction($approvedSaleTransactionMock);

        $this->assertEquals(
            $gatewayTerminalToken,
            GenesisConfig::getToken()
        );
    }

    /**
     * @covers EcomprocessingDataHelper::maskException()
     */
    public function testMaskException()
    {
        $exceptionMessage = 'Exception Message';

        $this->expectException(WebApiException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->moduleHelper->maskException(
            new Exception(
                $exceptionMessage,
                WebApiException::HTTP_INTERNAL_ERROR
            )
        );
    }

    /**
     * @covers EcomprocessingDataHelper::getArrayFromGatewayResponse()
     */
    public function testGetArrayFromGatewayResponse()
    {
        $gatewayResponse = new stdClass();
        $gatewayResponse->status           = GenesisTransactionStates::APPROVED;
        $gatewayResponse->message          = 'Gateway Response Message Text';
        $gatewayResponse->transaction_type = GenesisTransactionTypes::PAYSAFECARD;

        $arrObj = $this->moduleHelper->getArrayFromGatewayResponse($gatewayResponse);

        $this->assertTrue(is_array($arrObj));

        $this->assertArrayHasKeys(
            [
                'status',
                'message',
                'transaction_type'
            ],
            $arrObj
        );
    }

    /**
     * @covers EcomprocessingDataHelper::setOrderState($order, GenesisTransactionStates::APPROVED)
     */
    public function testSetOrderStateProcessing()
    {
        $orderMock = $this->getOrderMock();

        /**
         * @var $orderConfigMock Order\Config|MockObject
         */
        $orderConfigMock = $orderMock->getConfig();

        $orderMock->expects(static::once())
            ->method('setState')
            ->willReturnSelf();

        $orderMock->expects(static::once())
            ->method('setStatus')
            ->willReturnSelf();

        $orderConfigMock->expects(static::once())
            ->method('getStateDefaultStatus')
            ->with(
                Order::STATE_PROCESSING
            )
            ->willReturn(
                Order::STATE_PROCESSING
            );

        $this->orderRepositoryMock->expects(static::once())
            ->method('save')
            ->with($orderMock);

        $this->moduleHelper->setOrderState(
            $orderMock,
            GenesisTransactionStates::APPROVED
        );
    }

    /**
     * @covers EcomprocessingDataHelper::setOrderState($order, GenesisTransactionStates::APPROVED)
     */
    public function testSetOrderStatePending()
    {
        $orderMock = $this->getOrderMock();

        /**
         * @var $orderConfigMock Order\Config|MockObject
         */
        $orderConfigMock = $orderMock->getConfig();

        $orderMock->expects(static::exactly(2))
            ->method('setState')
            ->willReturnSelf();

        $orderMock->expects(static::exactly(2))
            ->method('setStatus')
            ->willReturnSelf();

        $orderMock->expects(static::never())
            ->method('registerCancellation');

        $orderConfigMock->expects(static::exactly(2))
            ->method('getStateDefaultStatus')
            ->with(Order::STATE_PENDING_PAYMENT)
            ->willReturn(Order::STATE_PENDING_PAYMENT);

        $this->orderRepositoryMock->expects(static::exactly(2))
            ->method('save')
            ->with($orderMock);

        $this->moduleHelper->setOrderState(
            $orderMock,
            GenesisTransactionStates::PENDING
        );

        $this->moduleHelper->setOrderState(
            $orderMock,
            GenesisTransactionStates::PENDING_ASYNC
        );
    }

    /**
     * @covers EcomprocessingDataHelper::setOrderState($order, GenesisTransactionStates::APPROVED)
     */
    public function testSetOrderStateErrorOrDeclined()
    {
        $orderMock = $this->getOrderMock();

        /**
         * @var $orderConfigMock Order\Config|MockObject
         */
        $orderConfigMock = $orderMock->getConfig();

        $orderMock->expects(static::exactly(2))
            ->method('setState');

        $orderMock->expects(static::exactly(2))
            ->method('setStatus');

        $orderMock->expects(static::exactly(2))
            ->method('getInvoiceCollection')
            ->willReturn([]);

        $orderMock->expects(static::exactly(2))
            ->method('registerCancellation')
            ->willReturnSelf();

        $orderMock->expects(static::exactly(2))
            ->method('setCustomerNoteNotify')
            ->willReturnSelf();

        $orderConfigMock->expects(static::exactly(2))
            ->method('getStateDefaultStatus');

        $this->orderRepositoryMock->expects(static::exactly(2))
            ->method('save')
            ->with($orderMock);

        $this->moduleHelper->setOrderState(
            $orderMock,
            GenesisTransactionStates::ERROR
        );

        $this->moduleHelper->setOrderState(
            $orderMock,
            GenesisTransactionStates::DECLINED
        );
    }

    /**
     * @covers EcomprocessingDataHelper::setOrderState($order, GenesisTransactionStates::APPROVED)
     */
    public function testSetOrderStateOnPaymentTimeoutOrVoid()
    {
        $orderMock = $this->getOrderMock();

        /**
         * @var $orderConfigMock Order\Config|MockObject
         */
        $orderConfigMock = $orderMock->getConfig();

        $orderMock->expects(static::exactly(2))
            ->method('setState');

        $orderMock->expects(static::exactly(2))
            ->method('setStatus');

        $orderMock->expects(static::exactly(2))
            ->method('getInvoiceCollection')
            ->willReturn([]);

        $orderMock->expects(static::exactly(2))
            ->method('registerCancellation')
            ->willReturnSelf();

        $orderMock->expects(static::exactly(2))
            ->method('setCustomerNoteNotify')
            ->willReturnSelf();

        $orderConfigMock->expects(static::exactly(2))
            ->method('getStateDefaultStatus');

        $this->orderRepositoryMock->expects(static::exactly(2))
            ->method('save')
            ->with($orderMock);

        $this->moduleHelper->setOrderState(
            $orderMock,
            GenesisTransactionStates::TIMEOUT
        );

        $this->moduleHelper->setOrderState(
            $orderMock,
            GenesisTransactionStates::VOIDED
        );
    }

    /**
     * @covers EcomprocessingDataHelper::getGlobalAllowedCurrencyCodes()
     */
    public function testGetGlobalAllowedCurrencyCodes()
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(
                Currency::XML_PATH_CURRENCY_ALLOW
            )
            ->willReturn('USD,EUR,GBP');

        $globalAllowedCurrencyCodes = $this->moduleHelper->getGlobalAllowedCurrencyCodes();

        $this->assertTrue(is_array($globalAllowedCurrencyCodes));

        $this->assertArrayKeysCount(
            3,
            $globalAllowedCurrencyCodes
        );

        $this->assertArrayHasValues(
            [
                'USD',
                'EUR',
                'GBP'
            ],
            $globalAllowedCurrencyCodes
        );
    }

    /**
     * @covers EcomprocessingDataHelper::getGlobalAllowedCurrenciesOptions()
     */
    public function testGetGlobalAllowedCurrenciesOptions()
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(Currency::XML_PATH_CURRENCY_ALLOW)
            ->willReturn('USD,EUR,GBP');

        $allowedCurrenciesOptions = $this->moduleHelper->getGlobalAllowedCurrenciesOptions(
            [
                [
                    'value' => 'USD'
                ],
                [
                    'value' => 'GBP'
                ],
                [
                    'value' => 'AUD'
                ]
            ]
        );

        $this->assertTrue(is_array($allowedCurrenciesOptions));

        $this->assertArrayKeysCount(2, $allowedCurrenciesOptions);

        $this->assertEquals(
            [
                [
                    'value' => 'USD'
                ],
                [
                    'value' => 'GBP'
                ],
            ],
            $allowedCurrenciesOptions
        );
    }

    /**
     * @covers EcomprocessingDataHelper::getLocale()
     */
    public function testGetDefaultLocale()
    {
        $this->localeResolverMock->expects(static::once())
            ->method('getLocale')
            ->willReturn(
                Resolver::DEFAULT_LOCALE
            );

        $gatewayLocale = $this->moduleHelper->getLocale('de');

        $this->assertTrue(
            i18n::isValidLanguageCode($gatewayLocale)
        );

        $this->assertEquals(
            $gatewayLocale,
            substr(
                Resolver::DEFAULT_LOCALE,
                0,
                2
            )
        );
    }

    /**
     * @covers EcomprocessingDataHelper::getLocale()
     */
    public function testGetUnsupportedGatewayLocale()
    {
        $danishLocale = 'fa_AF';
        $defaultLocale = 'en';

        $this->localeResolverMock->expects(static::once())
            ->method('getLocale')
            ->willReturn(
                $danishLocale
            );

        $gatewayLocale = $this->moduleHelper->getLocale($defaultLocale);

        $this->assertTrue(i18n::isValidLanguageCode($gatewayLocale));

        $this->assertEquals(
            $gatewayLocale,
            $defaultLocale
        );
    }

    /**
     * @covers EcomprocessingDataHelper::canRefundTransaction()
     */
    public function testCanRefundCaptureTransaction()
    {
        $captureTransactionMock = $this->getPaymentTransactionMock();
        $captureTransactionMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(Transaction::RAW_DETAILS)
            ->willReturn(
                [
                    EcomprocessingDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE => GenesisTransactionTypes::CAPTURE
                ]
            );

        $this->assertTrue(
            $this->moduleHelper->canRefundTransaction(
                $captureTransactionMock
            )
        );
    }

    /**
     * @covers EcomprocessingDataHelper::canRefundTransaction()
     */
    public function testCanRefundPaySafeCardTransaction()
    {
        $captureTransactionMock = $this->getPaymentTransactionMock();
        $captureTransactionMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(Transaction::RAW_DETAILS)
            ->willReturn(
                [
                    EcomprocessingDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE =>
                        GenesisTransactionTypes::PAYSAFECARD
                ]
            );

        $this->assertFalse(
            $this->moduleHelper->canRefundTransaction(
                $captureTransactionMock
            )
        );
    }

    /**
     * @covers EcomprocessingDataHelper::canRefundTransaction()
     */
    public function testCanRefundSaleTransaction()
    {
        $captureTransactionMock = $this->getPaymentTransactionMock();
        $captureTransactionMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(Transaction::RAW_DETAILS)
            ->willReturn(
                [
                    EcomprocessingDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE => GenesisTransactionTypes::SALE
                ]
            );

        $this->assertTrue(
            $this->moduleHelper->canRefundTransaction(
                $captureTransactionMock
            )
        );
    }

    /**
     * @covers EcomprocessingDataHelper::getIsTransactionThreeDSecure()
     */
    public function testGetIsTransactionThreeDSecure()
    {
        $this->assertTrue(
            $this->moduleHelper->getIsTransactionThreeDSecure(
                GenesisTransactionTypes::AUTHORIZE_3D
            )
        );

        $this->assertFalse(
            $this->moduleHelper->getIsTransactionThreeDSecure(
                GenesisTransactionTypes::AUTHORIZE
            )
        );

        $this->assertTrue(
            $this->moduleHelper->getIsTransactionThreeDSecure(
                GenesisTransactionTypes::SALE_3D
            )
        );

        $this->assertFalse(
            $this->moduleHelper->getIsTransactionThreeDSecure(
                GenesisTransactionTypes::SALE
            )
        );
    }

    /**
     * @covers EcomprocessingDataHelper::getErrorMessageFromGatewayResponse()
     */
    public function testGetSuccessErrorMessageFromGatewayResponse()
    {
        $successfulGatewayResponseMessage     = 'Transaction successful!';
        $successfulGatewayResponseTechMessage = 'Transaction has been processed successfully!';

        $validGatewayResponseWithMessage = $this->getSampleGatewayResponse(
            GenesisTransactionStates::APPROVED,
            GenesisTransactionTypes::AUTHORIZE,
            $successfulGatewayResponseMessage,
            $successfulGatewayResponseTechMessage
        );

        $gatewayResponseMessage = $this->moduleHelper->getErrorMessageFromGatewayResponse(
            $validGatewayResponseWithMessage
        );

        $this->assertStringStartsWith(
            $successfulGatewayResponseMessage,
            $gatewayResponseMessage
        );

        $this->assertStringEndsWith(
            $successfulGatewayResponseTechMessage,
            $gatewayResponseMessage
        );
    }

    /**
     * @covers EcomprocessingDataHelper::getErrorMessageFromGatewayResponse()
     */
    public function testGetFailedErrorMessageFromGatewayResponse()
    {
        $validGatewayResponseWithMessage = $this->getSampleGatewayResponse(
            GenesisTransactionStates::DECLINED,
            GenesisTransactionTypes::SALE
        );

        $gatewayResponseMessage = $this->moduleHelper->getErrorMessageFromGatewayResponse(
            $validGatewayResponseWithMessage
        );

        $this->assertEquals(
            EcomprocessingDataHelper::GENESIS_GATEWAY_ERROR_MESSAGE_DEFAULT,
            $gatewayResponseMessage
        );
    }

    /**
     * @covers EcomprocessingDataHelper::getErrorMessageFromGatewayResponse()
     */
    public function testGetPendingAsyncSuccessErrorMessageFromGatewayResponse()
    {
        $successfulGatewayResponseMessage          = 'Transaction successful!';
        $successfulGatewayResponseTechnicalMessage = 'Transaction has been processed successfully!';

        $validGatewayResponseWithMessage = $this->getSampleGatewayResponse(
            GenesisTransactionStates::PENDING_ASYNC,
            GenesisTransactionTypes::REFUND,
            $successfulGatewayResponseMessage,
            $successfulGatewayResponseTechnicalMessage
        );

        $gatewayResponseMessage = $this->moduleHelper->getErrorMessageFromGatewayResponse(
            $validGatewayResponseWithMessage
        );

        $this->assertStringStartsWith(
            $successfulGatewayResponseMessage,
            $gatewayResponseMessage
        );

        $this->assertStringEndsWith(
            $successfulGatewayResponseTechnicalMessage,
            $gatewayResponseMessage
        );
    }

    /**
     * @covers EcomprocessingDataHelper::getReturnUrl
     */
    public function testGetReturnUrl()
    {
        $moduleCode          = 'ecomprocessing_checkout';
        $returnAction        = EcomprocessingDataHelper::ACTION_RETURN_SUCCESS;
        $expectedUrlIframe   = 'https://example.com/ecomprocessing/checkout/iframe/action/success';
        $expectedUrlRedirect = 'https://example.com/ecomprocessing/checkout/redirect/action/success';

        // Mocking the Config class to return true for isIframeProcessingEnabled
        $configMock = $this->createMock(FrontendConfig::class);
        $configMock->expects($this->exactly(2))
            ->method('isIframeProcessingEnabled')
            ->willReturnOnConsecutiveCalls(true, false);

        // Use reflection to set the protected property _config
        $reflectionClass = new ReflectionClass(EcomprocessingDataHelper::class);
        $configProperty  = $reflectionClass->getProperty('_config');
        $configProperty->setAccessible(true);
        $configProperty->setValue($this->moduleHelper, $configMock);

        // Mocking store
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        // Mocking getUrl with correct parameters
        $this->urlBuilderMock->expects($this->exactly(2))
            ->method('getUrl')
            ->withConsecutive(
                [
                    'ecomprocessing/checkout/iframe',
                    [
                        '_store'  => $this->storeMock,
                        '_secure' => null,
                        'action'  => $returnAction
                    ]
                ],
                [
                    'ecomprocessing/checkout/redirect',
                    [
                        '_store'  => $this->storeMock,
                        '_secure' => null,
                        'action'  => $returnAction
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls($expectedUrlIframe, $expectedUrlRedirect);

        // Test iframe URL generation
        $actualUrlIframe = $this->moduleHelper->getReturnUrl($moduleCode, $returnAction);
        $this->assertEquals($expectedUrlIframe, $actualUrlIframe);

        // Test redirect URL generation
        $actualUrlRedirect = $this->moduleHelper->getReturnUrl($moduleCode, $returnAction);
        $this->assertEquals($expectedUrlRedirect, $actualUrlRedirect);
    }

    /**
     * @covers EcomprocessingDataHelper::updateTransactionAdditionalInfo
     *
     * @return void
     *
     * @throws Exception
     */
    public function testUpdateTransactionAdditionalInfoTransactionFound()
    {
        $transactionId          = '123';
        $responseObject         = new stdClass();
        $shouldCloseTransaction = true;

        $this->transactionMock->method('load')
            ->with($transactionId, 'txn_id')
            ->willReturnSelf();

        $this->transactionMock->method('getId')
            ->willReturn(1);

        $this->moduleHelper->method('getPaymentTransaction')
            ->with($transactionId)
            ->willReturn($this->transactionMock);

        $this->moduleHelper->expects($this->once())
            ->method('setTransactionAdditionalInfo')
            ->with($this->transactionMock, $responseObject);

        $this->transactionMock->expects($this->once())
            ->method('setIsClosed')
            ->with(true);

        $this->transactionRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->transactionMock);

        $result = $this->moduleHelper->updateTransactionAdditionalInfo(
            $transactionId,
            $responseObject,
            $shouldCloseTransaction
        );

        $this->assertTrue($result);
    }

    /**
     * @covers EcomprocessingDataHelper::updateTransactionAdditionalInfo
     *
     * @return void
     *
     * @throws Exception
     */
    public function testUpdateTransactionAdditionalInfoTransactionNotFound()
    {
        $transactionId          = '123';
        $responseObject         = new stdClass();
        $shouldCloseTransaction = false;

        $this->moduleHelper = $this->getMockBuilder(EcomprocessingDataHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPaymentTransaction', 'setTransactionAdditionalInfo'])
            ->getMock();

        $this->moduleHelper->method('getPaymentTransaction')
            ->with($transactionId)
            ->willReturn(null);

        $this->moduleHelper->expects($this->never())
            ->method('setTransactionAdditionalInfo');

        $this->transactionRepositoryMock->expects($this->never())
            ->method('save');

        $result = $this->moduleHelper->updateTransactionAdditionalInfo(
            $transactionId,
            $responseObject,
            $shouldCloseTransaction
        );

        $this->assertFalse($result);
    }
}
