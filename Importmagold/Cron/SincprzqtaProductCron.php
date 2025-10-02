<?php
namespace LM\Importmagold\Cron;

use LM\Importmagold\Model\ImportProductMexalService;
use LM\Importmagold\Block\Adminhtml\Mexal;
use LM\Importmagold\Model\ImportatoreProductCommand as ImportatoreProduct;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ObjectManager;

class SincprzqtaProductCron
{
	protected $mexal;
	protected $importatoreProduct;
    protected $scopeConfig;
	protected $bootstrap;
	protected $objectManager;
	
    public function __construct() {
    	$objectManager = ObjectManager::getInstance();
        $this->mexal = $objectManager->get(Mexal::class);
        $this->bootstrap = Bootstrap::create(BP, $_SERVER);
        $this->objectManager = $this->bootstrap->getObjectManager();
        $this->importatoreProduct = new ImportatoreProduct();
        $this->scopeConfig = $this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
	}

    public function execute()
    {
		// Imposta il limite di tempo su infinito per evitare timeout
		set_time_limit(900);

		// Imposta un limite di memoria più alto per gestire eventuali richieste pesanti
		ini_set('memory_limit', '2G');

		$articoli_ricerca_rielaborati = $this->mexal->getArrayRispostaArticoliRicercaRielaborati();
		$conto_articoli = count($articoli_ricerca_rielaborati);
		
		// Controlla se l'importazione è abilitata
		$isEnabled = $this->scopeConfig->getValue('mexal_config/general/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) && $this->scopeConfig->getValue('mexal_config/general/enabled_sinc_product', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if ($isEnabled) {
			$importProductMexalService = new ImportProductMexalService($this->mexal);
			$importResult = $importProductMexalService->importProductQtaPrz();
		}
		return true;
    }


}

