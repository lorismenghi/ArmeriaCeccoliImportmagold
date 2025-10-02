<?php

namespace LM\Importmagold\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Template;

class Report extends Template
{
		protected $scopeConfig;
		
    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
    		$this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

		// clienti
    public function getCustomerCollection()
    {
        return $this->getData('collection');
    }

    public function getCustomerAggiornataCount()
    {
        return $this->getData('aggiornataCount');
    }

    public function isImportCustomerEnabled()
    {
        return $this->scopeConfig->getValue('import_config/customer/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }


		// prodotti
    public function getProdottiCollection()
    {
        return $this->getData('prodotti_collection');
    }

    public function getProdottiAggiornataCount()
    {
        return $this->getData('prodottiAggiornataCount');
    }

    public function isImportProductEnabled()
    {
        return $this->scopeConfig->getValue('import_config/product/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

}

