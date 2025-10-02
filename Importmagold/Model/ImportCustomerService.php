<?php
namespace LM\Importmagold\Model;

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Filesystem\DirectoryList;

class ImportCustomerService
{
    protected $importatoreCustomer;
    protected $scopeConfig;
    //protected $messageManager;
		protected $bootstrap;
		protected $objectManager;

    public function __construct() {
        $this->bootstrap = Bootstrap::create(BP, $_SERVER);
        $this->objectManager = $this->bootstrap->getObjectManager();
        $this->importatoreCustomer = new ImportatoreCustomer();
        $this->scopeConfig = $this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
        //$this->messageManager = $this->objectManager->get('Magento\Framework\Message\ManagerInterface');
    }

    public function importCustomer()
    {
        // Controlla se l'importazione è abilitata
        $isEnabled = $this->scopeConfig->getValue('import_config/customer/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (!$isEnabled) {
        	return ['success' => false, 'message' => __('Abilitare prima import clienti')];
        }

        try {
        //$n_max = 10;
        	//for ($i = 1; $i <= $n_max; $i++) {
        		$this->importatoreCustomer = new ImportatoreCustomer();
            $next_customer_id = $this->importatoreCustomer->getNextCustomerImportId();
            $url = ImportatoreCustomer::URL_GET_DATA_CUSTOMER . $next_customer_id . '&var=' . time();
            $result = file_get_contents($url);
            $result = json_decode($result);

            if (is_object($result) && property_exists($result, 'entity_id') && $result->entity_id) {
                $this->importatoreCustomer->creaAggiornaCliente($result);

                if ($this->importatoreCustomer->output['sincClienti'] == '#OK IMPORT ULTIMATO CON SUCCESSO#') {
                    $this->importatoreCustomer->output['dettaglio_azioni'] .= ' #avanzare import# ';
                    $this->importatoreCustomer->registraImportCustomer();
                } else {
                    $this->importatoreCustomer->output['dettaglio_azioni'] .= ' #rifare import# ';
                }

                $this->importatoreCustomer->output['data_customer'] = $this->importatoreCustomer->data_customer;
            } else {
            	return ['success' => true, 'message' => __('Sincronizzazione clienti ultimata!')];
            }
			//}
            return ['success' => true, 'message' => __('Ciente sincronizzato correttamente')];
            //return ['success' => true, 'message' => __($n_max.' clienti sincronizzati correttamente')];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => __('Errore durante l\'importazione del cliente: ' . $e->getMessage())];
        }
    }
}

