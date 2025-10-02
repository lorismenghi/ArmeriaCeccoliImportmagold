<?php
namespace LM\Importmagold\Block\Adminhtml\System\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Data\Form\Element\AbstractElement;
use LM\Importmagold\Block\Adminhtml\MexalOrder;
use Magento\Backend\Block\Template\Context;

class LastOrderId extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var MexalOrder
     */
    private $mexalOrder;

    /**
     * Costruttore
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param MexalOrder $mexalOrder
     * @param array $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        MexalOrder $mexalOrder,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->mexalOrder = $mexalOrder;
        parent::__construct($context, $data);
    }

    /**
     * Restituisce l'elemento HTML con il valore aggiornato
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        //$lastOrderId = $this->getLastOrderId(); // questo ha problemi di cache
       
       // Utilizza il metodo getLastMagentoOrderId per ottenere il valore
        $lastOrderId = $this->mexalOrder->getLastMagentoOrderId();
		
		$element->setValue($lastOrderId);
        return parent::_getElementHtml($element);
    }

    /**
     * Recupera l'ID dell'ultimo ordine dalla configurazione di admin, ma ha problemi di cache
     *
     * @return string|null
     */
    protected function getLastOrderId()
    {
        // Recupera il valore della configurazione salvata per last_mag_order_id
        return $this->scopeConfig->getValue(
            'mexal_config/general/last_mag_order_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
