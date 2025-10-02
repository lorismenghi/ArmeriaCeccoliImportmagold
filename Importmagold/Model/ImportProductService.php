<?php
namespace LM\Importmagold\Model;

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Filesystem\DirectoryList;

class ImportProductService
{
    protected $importatoreProduct;
    protected $scopeConfig;
    //protected $messageManager;
		protected $bootstrap;
		protected $objectManager;

    public function __construct() {
        $this->bootstrap = Bootstrap::create(BP, $_SERVER);
        $this->objectManager = $this->bootstrap->getObjectManager();
        $this->importatoreProduct = new ImportatoreProduct();
        $this->scopeConfig = $this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
        //$this->messageManager = $this->objectManager->get('Magento\Framework\Message\ManagerInterface');
    }

    public function importProduct()
    {
        // Controlla se l'importazione è abilitata
        $isEnabled = $this->scopeConfig->getValue('import_config/product/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (!$isEnabled) {
        	return ['success' => false, 'message' => __('Abilitare prima import prodotti')];
        }

        try {
            $next_product_id = $this->importatoreProduct->getNextProductImportId(); //$this->importatoreProduct->output['dettaglio_azioni'] .= " #next_product_id:$next_product_id# ";
            //$next_product_id = $this->importatoreProduct->getNextProductImportId(true); // cosi prende quelli con id_new 0
            //$next_product_id = 12915;
            //$next_product_id = 5711; // forzo per i test
            $url = ImportatoreProduct::URL_GET_DATA_PRODUCT . $next_product_id . '&var=' . time();
            $result = file_get_contents($url);

            //$this->importatoreProduct->output['dettaglio_azioni'] .= " # $result # ";
            //return ['success' => true, 'message' => "URL:$url ".$this->importatoreProduct->output['dettaglio_azioni']];
            $result = json_decode($result);

            if (is_object($result) && property_exists($result, 'entity_id') && $result->entity_id) {
            	
                $this->importatoreProduct->creaAggiornaProdottoSoloFoto($result);

                if (true || $this->importatoreProduct->output['sincProduct'] == '#OK IMPORT ULTIMATO CON SUCCESSO#') {

                    $this->importatoreProduct->output['dettaglio_azioni'] .= ' #avanzare import# ';
                    $this->importatoreProduct->registraImportProduct();
                } else {
                    $this->importatoreProduct->output['dettaglio_azioni'] .= ' #rifare import# ';
                }

                $this->importatoreProduct->output['data_product'] = $this->importatoreProduct->data_product;
            } else {
            	return ['success' => true, 'message' => __('Sincronizzazione prodotti ultimata!'.$this->importatoreProduct->output['dettaglio_azioni'])];
            }
						//return ['success' => true, 'message' => __('Prodotto '.$result->sku.' sincronizzato correttamente.')];
            return ['success' => true, 'message' => __('Prodotto sincronizzato correttamente. Dettaglio azioni:'.$this->importatoreProduct->output['dettaglio_azioni'])];
        } catch (\Exception $e) {
        		//$this->importatoreProduct->registraImportProduct();
            return ['success' => false, 'message' => __('Errore durante l\'importazione del prodotto: ' . $e->getMessage())];
        }
    }
}

