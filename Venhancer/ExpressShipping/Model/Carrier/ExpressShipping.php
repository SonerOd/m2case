<?php

namespace Venhancer\ExpressShipping\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory as RateErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method as RateMethod;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory as RateMethodFactory;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Psr\Log\LoggerInterface;

class ExpressShipping extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'venhancer_expressshipping';
    protected $_isFixed = false;

    /**
     * @var ResultFactory
     */
    protected $rateResultFactory;

    /**
     * @var RateMethodFactory
     */
    protected $rateMethodFactory;

    /**
     * @var RateErrorFactory
     */
    protected $rateErrorFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RateErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        RateMethodFactory $rateMethodFactory,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->rateErrorFactory = $rateErrorFactory;
    }

    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        /** @var Result $result */
        $result = $this->rateResultFactory->create();

        $maxWeight = (float)$this->getConfigData('max_weight');
        $handlingFee = (float)$this->getConfigData('handling_fee');
        $errorMessage = $this->getConfigData('specificerrmsg');

        $orderWeight = $request->getPackageWeight();
        if ($orderWeight > $maxWeight) {
            $error = $this->rateErrorFactory->create();
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($errorMessage ?: __('ExpressShipping is not available for this order.'));
            $result->append($error);
            return $result;
        }

        $baseCost = 10.0;
        if ($orderWeight > 5) {
            $extraWeight = $orderWeight - 5;
            $baseCost += 2 * ceil($extraWeight);
        }

        $cost = $baseCost + $handlingFee;

        /** @var RateMethod $method */
        $method = $this->rateMethodFactory->create();
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('name'));
        $method->setPrice($cost);
        $method->setCost($cost);
        $result->append($method);

        return $result;
    }

    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    public function isTrackingAvailable()
    {
        return false;
    }
}
