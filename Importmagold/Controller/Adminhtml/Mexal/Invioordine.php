<?php

namespace LM\Importmagold\Controller\Adminhtml\Mexal;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\ObjectManager;
use LM\Importmagold\Model\ImportProductMexalService;
use LM\Importmagold\Block\Adminhtml\MexalOrder as Mexal;
use LM\Importmagold\Model\ImportatoreProduct;
use Magento\Framework\App\Bootstrap;


class Invioordine extends Action
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
        //$this->bootstrap = Bootstrap::create(BP, $_SERVER);
        //$this->objectManager = $this->bootstrap->getObjectManager();
        //$this->importatoreProduct = new ImportatoreProduct();
        //$this->scopeConfig = $this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
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
		$last_mag_order_id = $block->getLastMagentoOrderId();
		$next_mag_order_id = $block->getNextOrderId();
		$customer_info = $block->getNextOrderCustomerInfo();
		//$clienti_ricerca_filtrati = $block->getArrayClientiRicercaFiltrati($customer_info, true, true);
		$clienti_ricerca_filtrati = $block->getArrayRispostaClientiRicerca(true, $customer_info);
		$cliente_mexal_2 = $cliente_mexal = [];
		$return_insert_cliente = '';
		$return_insert_order = null;
		$orderJson = '';
		$messaggio_fine = '';
		$codice = '';
		
		if ( !is_null($next_mag_order_id) ) {
		
			if ( count($clienti_ricerca_filtrati) ) {
				// cliente gia' presente su mexal
				$cliente_mexal = current($clienti_ricerca_filtrati); // prendo solo il primo che corrisponde
				$codice = $cliente_mexal['codice'];
			} else {
				// cliente da inserire
				$return_insert_cliente = $block->sendCustomerToMexal($customer_info);
				// ricontrollo se e' riuscito a inserirlo
				$clienti_ricerca_filtrati_2 = $block->getArrayRispostaClientiRicerca(true, $customer_info);
				if ( count($clienti_ricerca_filtrati_2) ) {
					$cliente_mexal_2 = current($clienti_ricerca_filtrati_2);
					$codice = $cliente_mexal_2['codice'];
				}
			}
			// ora sia che il customer era gia esistente o e' stato inserito, in $codice ho il codice cliente di mexal e sono in grado di inserire un nuovo ordine in mexal
			if ( strlen($codice) ) {
				$order = $block->getNextOrderObject();
				$orderJson = $block->createOrderJson($order, $codice);
				$return_insert_order = $block->sendOrderToMexal($orderJson);
				
				if ( $return_insert_order['success'] ) {
					$block->saveLastMagentoOrderId($next_mag_order_id);
					//$order->setState('processing'); // Imposta lo stato dell'ordine (es. 'processing', 'complete', 'canceled', ecc.)
					//$order->setStatus('preso_in_carico');
					$order->setState(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW, true);
					$order->setStatus(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
					$order->addStatusToHistory($order->getStatus(), 'Order processed successfully with reference');

					$block->saveOrder($order);
					
					
				} elseif ( !$return_insert_order['success'] && Mexal::startsWith6001($return_insert_order['data']['error']['response-detail']) ) {
					//$block->saveLastMagentoOrderId($next_mag_order_id);
					//$messaggio_fine .= "Ordine già inserito, aggiorno il puntatore a next_mag_order_id:$next_mag_order_id. ";
					$emails = $block->getEmailsNotificaErrore();
					// invio array completo: $return_insert_order['data']['error']
					$return_insert_order['data']['dati'] = json_decode($orderJson, true);
					// codice errore ricevuto a tutte le email
					$emails = $block->inviaNotificaErrore($emails, $return_insert_order['data']);
					//$block->saveLastMagentoOrderId($next_mag_order_id);
				}
			}
		
		} // fine !is_null $next_mag_order_id
		else {
			$messaggio_fine .= 'NON CI SONO ORDINI DA INVIARE! E\' gia stato tutto inviato!';
		}

		// Passa i dati al block tramite il layout
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('last_mag_order_id', $last_mag_order_id);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('next_mag_order_id', $next_mag_order_id);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('customer_info', $customer_info);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('cliente_mexal', $cliente_mexal);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('cliente_mexal_2', $cliente_mexal_2);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('return_insert_cliente', $return_insert_cliente);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('orderJson', $orderJson);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('return_insert_order', $return_insert_order);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('messaggio_fine', $messaggio_fine);

		return $resultPage;
    }
}
