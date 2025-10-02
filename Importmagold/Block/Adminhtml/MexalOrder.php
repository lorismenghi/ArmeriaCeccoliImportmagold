<?php
namespace LM\Importmagold\Block\Adminhtml;

use LM\Importmagold\Model\ImportatoreProduct;
use Magento\Store\Model\ScopeInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
//use Magento\Framework\App\Config\Storage\WriterInterface;
//use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;

class MexalOrder extends Mexal
{
    protected $orderRepository;
    protected $searchCriteriaBuilder;
    protected $sortOrderBuilder;
    protected $customerRepository;
	protected $transportBuilder;
	protected $storeManager;

   public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        //WriterInterface $configWriter,
        //Config $config,
        ResourceConnection $resourceConnection,
        Curl $curl,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        CustomerRepositoryInterface $customerRepository,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
		array $data = []
    ) {
        parent::__construct($context, $scopeConfig, $resourceConnection, $curl, $data);
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
		$this->customerRepository = $customerRepository;
		$this->transportBuilder = $transportBuilder;
		$this->storeManager = $storeManager;
    }

	public function getEmailsNotificaErrore()
	{
		$str_emails = $this->scopeConfig->getValue('mexal_config/general/email_errore', ScopeInterface::SCOPE_WEBSITE);
		$emails = array_map('trim', explode(',', $str_emails));
    	return $emails;
	}

	public function inviaNotificaErrore($emails, $errore)
	{
		// Assicurati che l'errore sia una stringa; se è un array, convertilo in JSON
		if (is_array($errore)) {
		    $errore = json_encode($errore, JSON_PRETTY_PRINT);
		}

		foreach ($emails as $email) {
		    $transport = $this->transportBuilder
		        ->setTemplateIdentifier('notifica_errore_template')
		        ->setTemplateOptions([
		            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
		            'store' => $this->storeManager->getStore()->getId(),
		        ])
		        ->setTemplateVars(['errore' => $errore])
		        ->setFrom('general')
		        ->addTo($email)
		        ->getTransport();
		    
		    $transport->sendMessage();
		}
	}

	// ultimo id Magento sincronizzato con Mexal
    public function getLastMagentoOrderId()
    {
    	// legge ma houn problema di cache
        //return (int) $this->scopeConfig->getValue('mexal_config/general/last_mag_order_id', ScopeInterface::SCOPE_WEBSITE);
		$connection = $this->resourceConnection->getConnection();
		$tableName = $connection->getTableName('core_config_data');

		$sql = "SELECT `value` FROM `$tableName` WHERE `scope` = :scope AND `scope_id` = :scope_id AND `path` = :path";
		$binds = [
		    'scope' => 'default',//ScopeInterface::SCOPE_WEBSITE,
		    'scope_id' => 0,
		    'path' => 'mexal_config/general/last_mag_order_id'
		];

		return (int) $connection->fetchOne($sql, $binds);        
        
	}

    public function getNextOrderId()
    {
    	$currentOrderId = $this->getLastMagentoOrderId();
        // Build the search criteria
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $currentOrderId, 'gt') // Get IDs greater than the current one
            ->create();

        // Sort by entity_id ascending to get the next one
        $sortOrder = $this->sortOrderBuilder
            ->setField('entity_id')
            ->setDirection('ASC') // Ascending order
            ->create();

        $searchCriteria->setSortOrders([$sortOrder]);

        // Fetch orders
        $ordersList = $this->orderRepository->getList($searchCriteria);
        $orders = $ordersList->getItems();

        // Check if we have a next order
        if (!empty($orders)) {
            $nextOrder = reset($orders); // Get the first result
            return $nextOrder->getEntityId(); // Return the next order's entity ID
        } else {
            return null; // No next order found
        }
    }

	public function getOrderById($orderId)
	{
		try {
		    // Retrieve the order by ID
		    return $this->orderRepository->get($orderId);
		} catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
		    // Return null if the order does not exist
		    return null;
		}
	}
	
	public function saveOrder($order)
	{
		return $this->orderRepository->save($order);
	}
	
	
	public function getNextOrderObject()
	{
		$order_id = $this->getNextOrderId();
		if ( is_null($order_id) )
			return null;
		else
			return $this->getOrderById($order_id);
	}

	public function getNextOrderCustomerInfo()
	{
		$order = $this->getNextOrderObject();
		if ( is_null($order) )
			return [];
		else
			return $this->getCustomerInfoOrGuestInfoByOrder($order);
	}

    // Metodo per ottenere le informazioni del cliente o dell'ospite
    public function getCustomerInfoOrGuestInfoByOrder($order)
    {
        $customerId = $order->getCustomerId();
        $billingAddress = $order->getBillingAddress();
        $tpNazionalita = $this->mapCountryToTpNazionalita($billingAddress->getCountryId());
        
        if ($customerId) {
            try {
                // Return customer object for registered users
                $customer = $this->customerRepository->getById($customerId);
                return [
                    //'is_guest' => false,
                    //'customer_id' => $customerId,
                    'cod_alternativo' => $customer->getId(),
                    'codice' => '', // il codice mexal non ce l'ho
                    'ragione_sociale' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                    'nome' => $customer->getFirstname(),
                    'cognome' => $customer->getLastname(),
                    'email' => $customer->getEmail(),
                    'tp_nazionalita' => $tpNazionalita,
                    'cod_listino' => 1, // valore di default
                    'valuta' => 1, // valore di default
                    'gest_privato' => 'S', // Soggetto privato
                    'gest_per_fisica' => 'S', // Persona fisica
                    'codice_fiscale' => $billingAddress->getTaxvat() ?? '',
                    'partita_iva' => $billingAddress->getVatId() ?? '',
                    'indirizzo' => is_array($billingAddress->getStreet()) ? implode(' ', $billingAddress->getStreet()) : $billingAddress->getStreet(),
                    'cap' => $billingAddress->getPostcode(),
                    'localita' => preg_replace('/[^a-zA-Z\s]/', '', $billingAddress->getCity()),
                    'provincia' => preg_replace('/[^a-zA-Z\s]/', '', $billingAddress->getRegionCode()),
                    'telefono' => $billingAddress->getTelephone()
                ];                
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // Customer ID exists but customer not found
                return $this->getGuestCustomerData($order, $billingAddress, $tpNazionalita);
            }
        } else {
            // Return guest information if no customer ID is associated
            return $this->getGuestCustomerData($order, $billingAddress, $tpNazionalita);
        }
    }

    private function getGuestCustomerData($order, $billingAddress, $tpNazionalita)
    {
        return [
            //'is_guest' => true,
            //'customer_id' => 0,
            'cod_alternativo' => '',
            'codice' => '', // il codice mexal non ce l'ho
            'ragione_sociale' => $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
            'nome' => $order->getCustomerName(),
            'cognome' => $order->getCustomerLastname(),
            'email' => $order->getCustomerEmail(),
            'tp_nazionalita' => $tpNazionalita,
            'cod_listino' => 1, // valore di default
            'valuta' => 1, // valore di default
            'gest_privato' => 'S', // Soggetto privato
            'gest_per_fisica' => 'S', // Persona fisica
            'codice_fiscale' => $billingAddress->getTaxvat() ?? '',
            'partita_iva' => $billingAddress->getVatId() ?? '',
            'indirizzo' => is_array($billingAddress->getStreet()) ? implode(' ', $billingAddress->getStreet()) : $billingAddress->getStreet(),
            'cap' => $billingAddress->getPostcode(),
            'localita' =>  preg_replace('/[^a-zA-Z\s]/', '', $billingAddress->getCity()),
            'provincia' => preg_replace('/[^a-zA-Z\s]/', '', $billingAddress->getRegionCode()),
            'telefono' => $billingAddress->getTelephone()
        ];
    }

    private function mapCountryToTpNazionalita($countryId)
    {
        switch ($countryId) {
            case 'IT':
                return 'I'; // Italia
            case 'SM':
                return 'R'; // San Marino
            case 'VA':
                return 'V'; // Vaticano
            case 'EU':
                return 'C'; // Unione Europea
            default:
                return 'E'; // Estero (No UE)
        }
    }

	public function createOrderJson($order, $codice_cliente_mexal)
	{
		// Format data_documento in the required format (YYYYMMDD)
		$dataDocumento = (new \DateTime($order->getCreatedAt()))->format('Ymd');
		
		// Ottieni l'ultimo numero di ordine da Mexal e incrementalo di 1
    	$numeroMexal = $this->getLastOrderNumberFromMexal() + 1;


		$orderJson = [
		    "sigla" => "OC", // Sigla del documento
		    "serie" => 1, // Serie dell'ordine (può essere dinamica se gestita diversamente)
		    //"numero" => $numeroMexal, //(int)$order->getIncrementId(), // Numero dell'ordine, utilizza l'increment ID di Magento
		    "numero" => 0,
		    "data_documento" => $dataDocumento,  // Data di creazione dell'ordine nel formato YYYYMMDD
		    "cod_conto" => $codice_cliente_mexal, // Codice Mexal del cliente
		    "id_riga" => [],
		    "tp_riga" => [],
		    "codice_articolo" => [],
		    "quantita" => [],
		    "pos_righe_lotto" => [],
		    "nr_righe_lotto" => [],
		    "id_lotto" => [],
		    "qta_lotto" => []
		];

		$itemIndex = 1;
		foreach ($order->getItems() as $item) {
		    $orderJson['id_riga'][] = [$itemIndex, $itemIndex];
		    $orderJson['tp_riga'][] = [$itemIndex, "R"];
		    $sku = $item->getSku();
		    //$sku = preg_replace('/[^A-Za-z0-9]/', '', $sku); // Rimuove tutti i caratteri non alfanumerici
		    $sku = strtoupper($sku); // mexal non accetta i minuscoli
		    $sku = substr($sku, 0, 16); // Limita il codice articolo a x caratteri
		    $orderJson['codice_articolo'][] = [$itemIndex, $sku];
		    $orderJson['quantita'][] = [$itemIndex, (float) $item->getQtyOrdered()];
			//$orderJson['cod_iva'][] = [$itemIndex, (string)(int)$item->getTaxPercent()]; // Aggiunta aliquota IVA
			$orderJson['cod_iva'][] = [$itemIndex, '23']; // Aggiunta aliquota IVA
		    // Se ho la gestione dei lotti, aggiungo qui i dettagli
		    $itemIndex++;
		}

		return json_encode($orderJson);
	}
/*
	public function getNextOrderNumberForMexal()
	{
		$currentOrderId = $this->getLastMagentoOrderId();
		$nextOrderId = $this->getNextOrderId();

		// Controlla se esiste un prossimo ordine
		if ($nextOrderId && $nextOrderId > $currentOrderId) {
		    // Salva il nuovo ID come l'ultimo ordine gestito
		    return $nextOrderId;
		}

		// Se non ci sono nuovi ordini, ritorna l'ultimo numero utilizzato
		return $currentOrderId;
	}
*/
	public function saveLastMagentoOrderId($orderId)
	{
		// Salva il nuovo ID dell'ordine come l'ultimo ordine gestito
		//$this->configWriter->save('mexal_config/general/last_mag_order_id', $orderId, ScopeInterface::SCOPE_WEBSITE);
		//$this->config->saveConfig('mexal_config/general/last_mag_order_id', $orderId, ScopeInterface::SCOPE_STORE, 0);
		// Esegui una query diretta per aggiornare il valore nel database
		$connection = $this->resourceConnection->getConnection();
		$tableName = $connection->getTableName('core_config_data');

		$sql = "UPDATE `$tableName` SET `value`= '$orderId' WHERE `path` = 'mexal_config/general/last_mag_order_id'";
		$connection->query($sql);
		
	}

	// codifica errore ordine già inviato o qualsiasi altro errore quindi non serve a un cazzo
	// 6001 - errore gestionale [Documento OC1/172 gia' esistente]
	// 6001 - errore gestionale [Codice articolo non trovato in archivio articoli (riga 2)]
	public static function startsWith6001($string)
	{
		return strpos($string, '6001 ') === 0;
	}

}

