<?php
namespace LM\Importmagold\Cron;

use LM\Importmagold\Model\ImportProductMexalService;
use LM\Importmagold\Block\Adminhtml\MexalOrder as Mexal;
use LM\Importmagold\Model\ImportatoreProduct;
use Magento\Framework\App\Bootstrap;


class InvioOrdineCron
{
	protected $mexal;
	protected $importatoreProduct;
    protected $scopeConfig;
	protected $bootstrap;
	protected $objectManager;
	
    public function __construct(
        Mexal $mexal
    ) {
        $this->mexal = $mexal;
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

		$block = $this->mexal; //$resultPage->getLayout()->getBlock('lm_importmagold_mexal');
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

		
		return true;
    }


}

