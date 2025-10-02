<?php
namespace LM\Importmagold\Model;

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Filesystem\DirectoryList;
use LM\Importmagold\Block\Adminhtml\MexalCommand as Mexal; // non lo posso instanziare dinamicamente altrimenti min inluppo
use Magento\Framework\App\ResourceConnection;


class ImportProductMexalServiceCommand
{
    protected $importatoreProduct;
    protected $scopeConfig;
    //protected $mexal;
    //protected $messageManager;
	protected $bootstrap;
	protected $objectManager;
	protected $data_product = [];
	public $n = 0; // contatore cici
	public $ng = 0; // contatore cici
    protected $resource;
    protected $connection;

    public function __construct() {
        $this->bootstrap = Bootstrap::create(BP, $_SERVER);
        $this->objectManager = $this->bootstrap->getObjectManager();
        $this->importatoreProduct = new ImportatoreProductCommand();
        //$this->mexal = $mexal;
        $this->scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $this->resource = $this->objectManager->get(ResourceConnection::class);
        $this->connection = $this->resource->getConnection();
    }

	// quando passato $articoli_ricerca_rielaborati siamo nel caso in cui uso MexalCommand e getArrayRispostaArticoliRicercaRielaborati non fa piu la chiamata, ma trasforma solo array 
    public function importProductQtaPrz($output_terminale, $articoli_ricerca_rielaborati)
    {
		//if ( is_object($output_terminale) )
			//$this->importatoreProduct->output_terminale = $output_terminale;
        // Controlla se l'importazione è abilitata
        //$isEnabled = $this->scopeConfig->getValue('mexal_config/general/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) && $this->scopeConfig->getValue('mexal_config/general/enabled_sinc_product', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $isEnabled = true;
        if (!$isEnabled) {
        	return ['success' => false, 'message' => __('Abilitare prima sinc prodotti mexal')];
        }
		// Imposta il limite di tempo su infinito per evitare timeout
		//set_time_limit(900);

		// Imposta un limite di memoria più alto per gestire eventuali richieste pesanti
		//ini_set('memory_limit', '2G');

		//if ( is_null($articoli_ricerca_rielaborati) )
			//$articoli_ricerca_rielaborati = $this->mexal->getArrayRispostaArticoliRicercaRielaborati();
		//echo '<pre>'; print_r($articoli_ricerca_filtrati); echo '</pre>';
		//$productExisting = $this->importatoreProduct->getProductBySku('23012');
		//echo '<p>Prodotto sku:23012 qta:'.$this->importatoreProduct->getQtyByProduct($productExisting).' price:'.$productExisting->getPrice().' special_price:'.$productExisting->getSpecialPrice().'</p>';
			$output = '';
			$this->connection->beginTransaction(); // Inizia transazione per ridurre carico
		try {

			//$this->n = 0;
			$mostra_riepilogo = true;
			foreach ($articoli_ricerca_rielaborati as $data_mexal_articolo) {
				//sleep(3);
				$this->importatoreProduct->output['dettaglio_azioni'] = '';
				$this->importatoreProduct->output['sincProduct'] = '';
				$this->data_product = $this->convertiDataProductMexal($data_mexal_articolo);
				$cambiato = false;

				if ( is_object($output_terminale) )
					$output_terminale->writeln(print_r($this->data_product, true));

				//echo '<p>Cerco se ho gia quel prodotto su magento</p>';
				$productExisting = $this->importatoreProduct->getProductBySku($this->data_product['sku']);
				
				// per i log di sinc
				$tipoImport = is_object($productExisting) ? 'aggiornamento' : 'creazione';
				$logId = $this->logImportData($tipoImport, $this->data_product['sku'], $data_mexal_articolo, $this->data_product);
				
				if ( is_object($productExisting) ) {
					$productExisting = $productExisting->load($productExisting->getId());
					$output .= '<h2>Prodotto sku:'.$this->data_product['sku'].' ESISTENTE su magento.</h2>';
					$output .= '<p>VALORI ATTUALI Prodotto sku:'.$this->data_product['sku'].' qta:'.$this->importatoreProduct->getQtyByProduct($productExisting).' price:'.$productExisting->getPrice().' special_price:'.$productExisting->getSpecialPrice().'</p>';
					// aggiorno solo se e' esistente e se ha un attributo da cambiare
					$cambiato = false;
					//$cambiato = true; // forzo momentaneamente

					if ( array_key_exists('qta', $this->data_product) && $this->data_product['qta'] < 0 ) {
						$this->data_product['qta'] = 0; // magento ha difficoltàcon i numeri negativi
					}

					// name
					if ( array_key_exists('name', $this->data_product) && $productExisting->getName() != $this->data_product['name'] ) {
						$cambiato = true;
						$output .= "#AGG#Name old:".$productExisting->getName()." name new:".$this->data_product['name'];
					}


					// qta
					if ( array_key_exists('qta', $this->data_product) && $this->importatoreProduct->getQtyByProduct($productExisting) != $this->data_product['qta'] ) {
						$cambiato = true;
						$output .= "#AGG#Qta old:".$this->importatoreProduct->getQtyByProduct($productExisting)." qta new:".$this->data_product['qta'];
					}
					
					// min_sale_qty tanto è nella chiamata generale non qui
					/*
					if ( array_key_exists('min_sale_qty', $this->data_product) && $this->importatoreProduct->getMinSaleQtyByProduct($productExisting) != $this->data_product['min_sale_qty'] ) {
						$cambiato = true;
						$output .= "#AGG#Min sale qty old:".$this->importatoreProduct->getMinSaleQtyByProduct($productExisting)." min_sale_qty new:".$this->data_product['min_sale_qty'];
					}
					*/
					
					// price
					if ( array_key_exists('price', $this->data_product) && round($productExisting->getPrice(), 2) != round($this->data_product['price'], 2)
							&& ( //$productExisting->getPrice() != '' && 
							strlen($this->data_product['price']) )
					) {
						$cambiato = true;
						$output .= "#AGG#price old:".round($productExisting->getPrice(), 2)." price new:".round($this->data_product['price'], 2);
					}

					// special_price
					if ( array_key_exists('special_price', $this->data_product) && round($productExisting->getSpecialPrice(), 2) != round($this->data_product['special_price'], 2)
							&& ( $productExisting->getSpecialPrice() != '' && strlen($this->data_product['special_price']) )
					) {
						$cambiato = true;
						$output .= "#AGG#special_price old:".round($productExisting->getSpecialPrice(), 2)." special_price new:".round($this->data_product['special_price'], 2);
					}
					
					$cod_cat = '';
					if ( array_key_exists('codice_categoria', $this->data_product) && strlen(trim($this->data_product['codice_categoria'])) ) {
						$cod_cat = $this->data_product['codice_categoria'];
						// cerco a che ID corrisponde
						$id_cat_new = $this->importatoreProduct->getCategoryIdByCodeMexal( trim($this->data_product['codice_categoria']) );
						if ( !in_array($id_cat_new, $productExisting->getCategoryIds()) ) {
							$cambiato = true;
							$output .= "##codice_categoria cambiato";
						}
						
					}
					
					if ($mostra_riepilogo) {
						// per output piu completo
						$output .= "<p>RIEPILOGO:";
						
						if ( array_key_exists('qta', $this->data_product) )
							$output .= "##Qta old:".$this->importatoreProduct->getQtyByProduct($productExisting)." qta new:".$this->data_product['qta'];
						if ( array_key_exists('price', $this->data_product) )
							$output .= "##price old:".round($productExisting->getPrice(), 2)." price new:".round($this->data_product['price'], 2);
						if ( array_key_exists('special_price', $this->data_product) )
							$output .= "##special_price old:".round($productExisting->getSpecialPrice(), 2)." special_price new:".round($this->data_product['special_price'], 2);
						
						$output .= "</p>";

					}
					

					if ($cambiato) {
						$output .= '<h2>Prodotto sku:'.$this->data_product['sku'].' da aggiornare</h2>';
						if (is_object($output_terminale))
							$output_terminale->writeln("Prodotto sku:".$this->data_product['sku'].' da aggiornare');
						//$output .= '<pre>' . preg_replace('/\s+/', ' ', print_r($this->data_product, true)) . '</pre>';
						//$output .= '<pre>' . print_r($this->data_product, true) . '</pre>';

						try {
							// Tenta di creare o aggiornare il prodotto
							// Eventualmente pulisci il cache o liberati di risorse
							$this->importatoreProduct->creaAggiornaProdotto($this->data_product, $output_terminale);
							$this->updateLogEntry($logId, 1, 1);
							//$this->importatoreProduct->cleanUp();
							if (is_object($output_terminale))
								$output_terminale->writeln("Prodotto elaborato: {$this->data_product['sku']}");
						} catch (\Exception $e) {
							//$this->updateLogEntry($logId, 1, 0);
							$this->updateLogEntry($logId, 1, 0, $e->getMessage());
							// Logga l'errore e passa al prossimo prodotto
							if (is_object($output_terminale))
								$output_terminale->writeln("<error>Errore sul prodotto {$this->data_product['sku']}: {$e->getMessage()}</error>");
							// loggare l'errore in un file se necessario
							file_put_contents('error_log.txt', $e->getMessage() . PHP_EOL, FILE_APPEND);
						}						
						
						$output .= $this->importatoreProduct->output['dettaglio_azioni'];
						//$n += 1;
						//if ($n > 3)
							//break;
					} else {
						if (is_object($output_terminale))
							$output_terminale->writeln("Prodotto sku:".$this->data_product['sku'].' già aggiornato!');
					}
				} else {
					$output .= '<p>Prodotto sku:'.$this->data_product['sku'].' MANCANTE lo creo.</p>';
					if (is_object($output_terminale))
					$output_terminale->writeln("Prodotto sku:".$this->data_product['sku']." MANCANTE lo creo.");
					
					// in creazione se manca il name mi da errore quindi gli assegno lo sku
					if ( !array_key_exists('name', $this->data_product) )
						$this->data_product['name'] = $this->data_product['sku'];

					try {
							// Tenta di creare o aggiornare il prodotto
							$this->importatoreProduct->creaAggiornaProdotto($this->data_product);
							$this->updateLogEntry($logId, 1, 1);
							//$output_terminale->writeln("Prodotto elaborato: {$this->data_product['sku']}");
					} catch (\Exception $e) {
							// Logga l'errore e passa al prossimo prodotto
							//$output_terminale->writeln("<error>Errore sul prodotto {$this->data_product['sku']}: {$e->getMessage()}</error>");
							// loggare l'errore in un file se necessario
							$output .= "<error>Errore sul prodotto {$this->data_product['sku']}: {$e->getMessage()}</error>";
							file_put_contents('error_log.txt', $e->getMessage() . PHP_EOL, FILE_APPEND);
							//$this->updateLogEntry($logId, 1, 0);
							$this->updateLogEntry($logId, 1, 0, $e->getMessage());
					}						

					
				}
				$output = '';
				$this->n += 1;
				if (is_object($output_terminale)) {
					if ( strlen(trim($this->importatoreProduct->output['dettaglio_azioni'])) )
						$output_terminale->writeln($this->importatoreProduct->output['dettaglio_azioni']);
					$output_terminale->writeln("\n Numero ciclo articoli: {$this->n}");
					$output = "Totale articoli processati: {$this->n}"; // lo aggiorno sempre, esce dal ciclo foreach con il numero finale
				}
				// se reinstanzio la classe importatore dopo apre troppe connessione mysql
				//unset($this->importatoreProduct);
				//$this->importatoreProduct = new ImportatoreProductCommand();
			} // fine foreach
			$this->connection->commit(); // Conferma transazione
        } catch (\Exception $e) {
            $this->connection->rollBack(); // Annulla in caso di errore
            throw $e;
        }

        return ['success' => true, 'message' => __('Importazione prodotti Mexal completata con successo'), 'output' => $output];

    }

	public function convertiDataProductMexal($data_mexal) {
		$this->data_product = [];
		$this->data_product = $data_mexal;
		
		// ora abbiamo un attributo in piu mastersku
		
		if ( array_key_exists('codice', $data_mexal) && strlen(trim($data_mexal['codice'])) ) { // questo blocco e' per i dati che vengono da importProductQtaPrz
			$this->data_product['sku'] = trim($data_mexal['codice']);

			if ( array_key_exists('descrizione', $data_mexal) && strlen(trim($data_mexal['descrizione'])) ) {
				//$this->data_product['name'] = trim($data_mexal['descrizione']);
				$this->data_product['nome_mexal'] = trim($data_mexal['descrizione']);
			}
			if ( array_key_exists('prz_listino', $data_mexal) ) $this->data_product['price'] = strlen(trim($data_mexal['prz_listino'])) ? trim($data_mexal['prz_listino']) : null;
			if ( array_key_exists('prz_speciale', $data_mexal) ) $this->data_product['special_price'] = strlen(trim($data_mexal['prz_speciale'])) ? trim($data_mexal['prz_speciale']) : null;
			if ( array_key_exists('min_sale_qty', $data_mexal) && strlen(trim($data_mexal['min_sale_qty'])) ) {
				$this->data_product['min_sale_qty'] = (int) trim($data_mexal['min_sale_qty']);
			}
			if ( array_key_exists('min_qty', $data_mexal) && strlen(trim($data_mexal['min_qty'])) ) {
				$this->data_product['min_qty'] = (int) trim($data_mexal['min_qty']);
			}
			if ( array_key_exists('qta', $data_mexal) && strlen(trim($data_mexal['qta'])) ) {
				$this->data_product['qta'] = (int) trim($data_mexal['qta']);
			}
			if ( array_key_exists('qta', $data_mexal) && strlen(trim($data_mexal['qta'])) ) {
				$this->data_product['qta'] = (int) trim($data_mexal['qta']);
			}
			if ( array_key_exists('qta_inventario', $data_mexal) && strlen(trim($data_mexal['qta_inventario'])) ) {
				$this->data_product['qta_inventario'] = (int) trim($data_mexal['qta_inventario']);
			}
			if ( array_key_exists('qta_carico', $data_mexal) && strlen(trim($data_mexal['qta_carico'])) ) {
				$this->data_product['qta_carico'] = (int) trim($data_mexal['qta_carico']);
			}
			if ( array_key_exists('qta_scarico', $data_mexal) && strlen(trim($data_mexal['qta_scarico'])) ) {
				$this->data_product['qta_scarico'] = (int) trim($data_mexal['qta_scarico']);
			}
		}
		if ( array_key_exists('sku', $data_mexal) && strlen(trim($data_mexal['sku'])) ) { // questo blocco e' per i dati che vengono da importProductGenerale
			$this->data_product['sku'] = trim($data_mexal['sku']);

			if ( array_key_exists('status', $data_mexal) && array_key_exists('VENDITA', $data_mexal) && array_key_exists('WEB', $data_mexal) ) {
				if ( $data_mexal['VENDITA'] && $data_mexal['WEB'] ) // questi sono booleani
					$this->data_product['status'] = $data_mexal['status']; // questo e' 1 o 2 che sono gli stati di magento
				else
					$this->data_product['status'] = $this->importatoreProduct::MAGENTO_STATUS_DISABLED;
			}

		}
		
		// devo aggiungere la categoria
		if ( array_key_exists('nome_categoria', $data_mexal) && strlen(trim($data_mexal['nome_categoria'])) ) {
			$this->data_product['nome_categoria_mexal'] = trim($data_mexal['nome_categoria']);
			if ( array_key_exists('nome_categoria_parent', $data_mexal) && strlen(trim($data_mexal['nome_categoria_parent'])) ) {
				$this->data_product['nome_categoria_parent_mexal'] = trim($data_mexal['nome_categoria_parent']);
			}
		}

		if ( array_key_exists('mastersku', $data_mexal) && strlen(trim($data_mexal['mastersku'])) ) {
			$this->data_product['mastersku'] = trim($data_mexal['mastersku']);
		}


		return $this->data_product;
	}

    public function importProductGenerale($output_terminale, $articoli_generale_rielaborati)
    {
        // Controlla se l'importazione è abilitata
        //$isEnabled = $this->scopeConfig->getValue('mexal_config/general/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) && $this->scopeConfig->getValue('mexal_config/general/enabled_sinc_product', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $isEnabled = true;
        if (!$isEnabled) {
        	return ['success' => false, 'message' => __('Abilitare prima sinc prodotti mexal')];
        }
		//$articoli_generale_rielaborati = $this->mexal->getArrayRispostaGeneraleArticoliRielaborati();
		$output = '';
		//$n = 0;
		$mostra_riepilogo = true;
		
		$this->connection->beginTransaction(); // Inizia transazione per ridurre
		
		try {
		
		foreach ($articoli_generale_rielaborati as $data_mexal_articolo) {
				$this->data_product = $this->convertiDataProductMexal($data_mexal_articolo);
				// aggiorno solo se e' esistente e se ha un attributo da cambiare
				$cambiato = false;
				//echo '<p>Cerco se ho gia quel prodotto su magento</p>';
				$productExisting = $this->importatoreProduct->getProductBySku($this->data_product['sku']);
				
				// per i log di sinc
				$tipoImport = is_object($productExisting) ? 'aggiornamento' : 'creazione';
				$logId = $this->logImportData($tipoImport, $this->data_product['sku'], $data_mexal_articolo, $this->data_product);
				
				if ( is_object($productExisting) ) {
					$this->data_product['attribute_set_id'] =  $productExisting->getAttributeSetId();
					//$output .= '<p>Prodotto sku:'.$this->data_product['sku'].' ESISTENTE su magento.</p>';
					$output .= '<p>Prodotto sku:'.$this->data_product['sku'].' qta:'.$this->importatoreProduct->getQtyByProduct($productExisting).' price:'.$productExisting->getPrice().' special_price:'.$productExisting->getSpecialPrice().' status: '.$productExisting->getStatus().'</p>';
					
					// status
					// io qui non setto il prezzo, se mi arriva da aggiornare un prodotto che non ha prezzo devo settarlo comunque disabled
					if ($productExisting->getFinalPrice() <= 0) {
						$this->data_product['status'] = $this->importatoreProduct::MAGENTO_STATUS_DISABLED;
					} else {
						$this->data_product['status'] = isset($this->data_product['status'])
							? $this->data_product['status']
							: $this->importatoreProduct::MAGENTO_STATUS_ENABLED;
					}					
					if ( array_key_exists('status', $this->data_product) && $productExisting->getStatus() != $this->data_product['status'] ) {
						$cambiato = true;
						//$output .= "##status old:".$productExisting->getStatus()." status new:".$this->data_product['status'];
					}
					
					// data_modifica
					$data_old = strtotime($productExisting->getDataModifica());
					$data_new = strtotime($this->data_product['data_modifica']);
					if ( $data_old < $data_new ) {
						$cambiato = true;
					} else {
						$cambiato = true;
					}

				} else {
					//$output .= '<p>Prodotto sku:'.$this->data_product['sku'].' MANCANTE su magento, non faccio nulla.</p>';
					$this->data_product['status'] = $this->importatoreProduct::MAGENTO_STATUS_DISABLED;
					$cambiato = true;
				}

				if ($cambiato) {
					if ( is_object($productExisting) ) {
						$output .= '<p>INIZIO AGGIORNAMENTO Prodotto sku:'.$this->data_product['sku'].' con status: '.$this->data_product['status'].'</p>';
						if (is_object($output_terminale)) {
							$output_terminale->writeln('INIZIO AGGIORNAMENTO Prodotto sku:'.$this->data_product['sku'].' con status: '.$this->data_product['status']);
							$output_terminale->writeln(print_r($this->data_product, true));
						}
						//echo '<pre>'; print_r($this->data_product); echo '</pre>';
					$output_terminale->writeln(print_r($this->data_product, true));	try {
							$this->importatoreProduct->creaAggiornaProdotto($this->data_product, $output_terminale);
							$this->updateLogEntry($logId, 1, 1);
						} catch (\Exception $e) {
							// Gestione dell'errore
							$output_terminale->writeln('<error>Errore durante l\'importazione/aggiornamento del prodotto: ' . $e->getMessage() . '</error>');
							//$this->updateLogEntry($logId, 1, 0);
							$this->updateLogEntry($logId, 1, 0, $e->getMessage());
						}
						
						
					} else {
						$output .= '<p>INIZIO CREAZIONE Prodotto sku:'.$this->data_product['sku'].' con status: '.$this->data_product['status'].'</p>';
						if (is_object($output_terminale)) {
							$output_terminale->writeln('INIZIO CREAZIONE Prodotto sku:'.$this->data_product['sku'].' con status: '.$this->data_product['status']);
							$output_terminale->writeln(print_r($this->data_product, true));
							
							// in creazione se manca il name mi da errore quindi gli assegno lo sku
							if ( !array_key_exists('name', $this->data_product) )
								$this->data_product['name'] = $this->data_product['sku'];
							
						}
						//echo '<pre>'; print_r($this->data_product); echo '</pre>';
						try {
							$this->importatoreProduct->creaAggiornaProdotto($this->data_product, $output_terminale);
							$this->updateLogEntry($logId, 1, 1);
						} catch (\Exception $e) {
							// Gestione dell'errore
							$output_terminale->writeln('<error>Errore durante l\'importazione/aggiornamento del prodotto: ' . $e->getMessage() . '</error>');
							//$this->updateLogEntry($logId, 1, 0);
							$this->updateLogEntry($logId, 1, 0, $e->getMessage());
						}
						
						
					}
					
					//$n += 1;
					//if ($n > 3)
						//break;
				} else {
					$output .= '<p>Prodotto sku:'.$this->data_product['sku'].' non necessita di aggiornamento</p>';
					if (is_object($output_terminale))
						$output_terminale->writeln('Prodotto sku:'.$this->data_product['sku'].' non necessita di aggiornamento');
				}

				$output = '';
				$this->ng += 1;
				if (is_object($output_terminale)) {
					if ( strlen(trim($this->importatoreProduct->output['dettaglio_azioni'])) )
						$output_terminale->writeln($this->importatoreProduct->output['dettaglio_azioni']);
					$output_terminale->writeln("\n Numero ciclo articoli: {$this->ng}");
					$output = "Totale articoli processati: {$this->ng}"; // lo aggiorno sempre, esce dal ciclo foreach con il numero finale
				}

				
		} // fine foreach articoli
		$this->connection->commit(); // Conferma transazione
        } catch (\Exception $e) {
            $this->connection->rollBack(); // Annulla in caso di errore
            throw $e;
        }

        return ['success' => true, 'message' => __('Importazione prodotti Mexal completata con successo'), 'output' => $output.' '.$this->importatoreProduct->output['dettaglio_azioni']];

    }

    private function logImportData($tipoImport, $sku, $datiRicevuti, $datiConvertiti)
    {
        $this->connection->insert('log_import_mexal', [
            'tipo_import' => $tipoImport,
            'sku' => $sku,
            'dati_ricevuti' => json_encode($datiRicevuti),
            'dati_convertiti' => json_encode($datiConvertiti),
            'created_at' => date('Y-m-d H:i:s'),
            'eseguito' => 0,
            'successo' => 0
        ]);
        return $this->connection->lastInsertId('log_import_mexal');
    }
    
	public function updateLogEntry($logId, $eseguito, $successo, $errore = null)
	{
		$data = [
		    'eseguito' => $eseguito,
		    'successo' => $successo,
		];
		if (!is_null($errore)) {
		    $data['errore'] = substr($errore, 0, 5000); // evita di superare limiti se scegli VARCHAR
		}

		$this->connection->update(
		    'log_import_mexal',
		    $data,
		    ['id = ?' => $logId]
		);
	}


}

