<?php

namespace Venhancer\ExpressShipping\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ACTIVE        = 'carriers/venhancer_expressshipping/active';
    const XML_PATH_HANDLING_FEE  = 'carriers/venhancer_expressshipping/handling_fee';
    const XML_PATH_MAX_WEIGHT    = 'carriers/venhancer_expressshipping/max_weight';

    public function isActive($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getHandlingFee($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_HANDLING_FEE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getMaxWeight($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_MAX_WEIGHT, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
