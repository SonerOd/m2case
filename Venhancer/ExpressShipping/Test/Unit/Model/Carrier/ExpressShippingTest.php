<?php

namespace Venhancer\ExpressShipping\Test\Unit\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Venhancer\ExpressShipping\Model\Carrier\ExpressShipping;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Error;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\StoreManagerInterface;

class ExpressShippingTest extends TestCase
{
    /** @var ExpressShipping */
    private $expressShipping;

    /** @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeConfigMock;

    /** @var Result */
    private $rateResult;

    /** @var Method */
    private $rateMethod;

    protected function setUp(): void
    {
        $storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $priceCurrencyMock = $this->createMock(PriceCurrencyInterface::class);
        $priceCurrencyMock->method('convert')->willReturnCallback(function($amount) {
            return $amount;
        });

        $this->rateMethod = new Method($priceCurrencyMock, []);

        $this->rateResult = new Result($storeManagerMock, $priceCurrencyMock);

        $rateErrorMock = $this->createMock(Error::class);

        $rateResultFactoryMock = $this->createMock(ResultFactory::class);
        $rateResultFactoryMock->method('create')->willReturn($this->rateResult);

        $rateMethodFactoryMock = $this->createMock(MethodFactory::class);
        $rateMethodFactoryMock->method('create')->willReturn($this->rateMethod);

        $rateErrorFactoryMock = $this->createMock(ErrorFactory::class);
        $rateErrorFactoryMock->method('create')->willReturn($rateErrorMock);

        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->scopeConfigMock->method('isSetFlag')
            ->willReturnMap([
                ['carriers/venhancer_expressshipping/active', ScopeInterface::SCOPE_STORE, null, true]
            ]);
        $this->scopeConfigMock->method('getValue')
            ->willReturnMap([
                ['carriers/venhancer_expressshipping/max_weight', ScopeInterface::SCOPE_STORE, null, '10'],
                ['carriers/venhancer_expressshipping/handling_fee', ScopeInterface::SCOPE_STORE, null, '0'],
                ['carriers/venhancer_expressshipping/specificerrmsg', ScopeInterface::SCOPE_STORE, null, '']
            ]);

        $loggerMock = $this->createMock(LoggerInterface::class);

        $this->expressShipping = new ExpressShipping(
            $this->scopeConfigMock,
            $rateErrorFactoryMock,
            $loggerMock,
            $rateResultFactoryMock,
            $rateMethodFactoryMock,
            []
        );
    }

    public function testCollectRatesForWeightLessThan5kg()
    {
        $request = new RateRequest();
        $request->setPackageWeight(4.5);

        $result = $this->expressShipping->collectRates($request);

        $this->assertNotFalse($result, 'collectRates should return a RateResult object');
        $this->assertCount(1, $result->getAllRates());
        $this->assertEquals(10, $result->getAllRates()[0]->getCost());
    }
}
