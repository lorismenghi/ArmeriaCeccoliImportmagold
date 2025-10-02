<?php

namespace LM\Importmagold\Controller\Adminhtml\Mexal;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\ObjectManager;
use LM\Importmagold\Model\ImportProductMexalService;
use LM\Importmagold\Block\Adminhtml\Mexal;
use LM\Importmagold\Model\ImportatoreProduct;
use Magento\Framework\App\Bootstrap;

class Sincprzqta extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    
    //protected $importProductMexalService;
	protected $importatoreProduct;

    protected $scopeConfig;

    //protected $messageManager;
	protected $bootstrap;
	protected $objectManager;
	 
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->bootstrap = Bootstrap::create(BP, $_SERVER);
        $this->objectManager = $this->bootstrap->getObjectManager();
        $this->importatoreProduct = new ImportatoreProduct();
        $this->scopeConfig = $this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');

    }
    
    /**
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
		$resultPage = $this->resultPageFactory->create();
		$resultPage->getConfig()->getTitle()->prepend(__('Mexal'));

		// Recupera il block dal layout
		$block = $resultPage->getLayout()->getBlock('lm_importmagold_mexal');
		$importResult = [];
		
		//$articoli_ricerca = $block->getArrayRispostaArticoliRicerca();
		$articoli_ricerca_rielaborati = $block->getArrayRispostaArticoliRicercaRielaborati();
		$conto_articoli = count($articoli_ricerca_rielaborati);
		
		
		// Controlla se l'importazione è abilitata
		$isEnabled = $this->scopeConfig->getValue('mexal_config/general/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) && $this->scopeConfig->getValue('mexal_config/general/enabled_sinc_product', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if ($isEnabled) {
			//$importProductMexalService = new ImportProductMexalService($block);
			//$importResult = $importProductMexalService->importProductQtaPrz();
			$conto_articoli = 0;
			$importResult = $articoli_ricerca_rielaborati = [];
			$importResult['output'] = 'Disabilitato hardcoded usare solo solo console.';

		} else {
			$conto_articoli = 0;
			$importResult = $articoli_ricerca_rielaborati = [];
			$importResult['output'] = 'Import prodotti disabilitato! Abilitare da admin modulo.';
		}

		// Passa i dati al block tramite il layout
		//$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('articoli_ricerca', $articoli_ricerca);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('conto_articoli', $conto_articoli);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('articoli_ricerca_rielaborati', $articoli_ricerca_rielaborati);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('importResult', $importResult);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('output', $importResult['output']);

		return $resultPage;
    }
/*
	public function convertiDataProductMexal($data_mexal) {
		$this->data_product = []; // resetto
		if ( array_key_exists('codice', $data_mexal) && strlen(trim($data_mexal['codice'])) ) {
			$this->data_product['sku'] = trim($data_mexal['codice']);

			if ( array_key_exists('descrizione', $data_mexal) && strlen(trim($data_mexal['descrizione'])) ) {
				$this->data_product['name'] = trim($data_mexal['descrizione']);
			}
			if ( array_key_exists('prz_listino', $data_mexal) ) $this->data_product['price'] = (float) trim($data_mexal['prz_listino']);
			if ( array_key_exists('prz_speciale', $data_mexal) ) $this->data_product['special_price'] = (float) trim($data_mexal['prz_speciale']);
			if ( array_key_exists('qta', $data_mexal) && strlen(trim($data_mexal['qta'])) ) {
				$this->data_product['qta'] = (int) trim($data_mexal['qta']);
			}
		}
		return $this->data_product;
	}
*/
}
