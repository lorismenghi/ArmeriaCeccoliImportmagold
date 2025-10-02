<?php

namespace LM\Importmagold\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\HTTP\Client\Curl;
//use Magento\Framework\App\Config\Storage\WriterInterface;
//use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\ResourceConnection;

class MexalBase extends Template
{
    protected $scopeConfig;
    //protected $configWriter;
    //protected $config;
    protected $resourceConnection;

    protected $curl;
    
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        //WriterInterface $configWriter,
        //Config $config,
        ResourceConnection $resourceConnection,
        Curl $curl,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        //$this->configWriter = $configWriter;
        //$this->config = $config;
        $this->resourceConnection = $resourceConnection;
        $this->curl = $curl;
        parent::__construct($context, $data);
    }

    public function getApiHelpResponse()
    {
		$cacheFile = BP . '/var/tmp/help_response.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_help/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_help/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_help/url', ScopeInterface::SCOPE_STORE);
        $queryKey = $this->scopeConfig->getValue('mexal_config/chiamata_help/query_key_1', ScopeInterface::SCOPE_STORE);
        $queryValue = $this->scopeConfig->getValue('mexal_config/chiamata_help/query_value_1', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url . '?' . $queryKey . '=' . $queryValue;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

		// Salva la risposta nel file di cache
		file_put_contents($cacheFile, $response);

        return $response;
    }

    public function getApiGruppiMerceologiciResponse()
    {
		$cacheFile = BP . '/var/tmp/gruppi_merceologici_response.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_gruppi_merceologici/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_gruppi_merceologici/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_gruppi_merceologici/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

		// Salva la risposta nel file di cache
		file_put_contents($cacheFile, $response);

        return $response;
    }

    public function getApiProgressiviArticoliResponse()
    {
		$cacheFile = BP . '/var/tmp/progressivi_articoli_response.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_progressivi_articoli/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_progressivi_articoli/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_progressivi_articoli/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Recupera la risposta
        $response = $this->curl->getBody();

		// Salva la risposta nel file di cache
		file_put_contents($cacheFile, $response);

        return $response;
    }


	public function getApiArticoliRicercaResponse($output_terminale = null)
	{
		$cacheFile = BP . '/var/tmp/articoli_ricerca_response.txt'; // File per il caching
		$flagFilePath = BP . '/var/tmp/articoli_ricerca_flag.txt'; // File flag per salvare lo stato
		
		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile) && !file_exists($flagFilePath)) {
		    return file_get_contents($cacheFile);
		}
		// incongruenza non dovrebbe succedere mai ma nel caso reimposta cancellando
		if (!file_exists($cacheFile) && file_exists($flagFilePath))
			unlink($flagFilePath);

		$next = ''; // mexal usa codice alfanumerico
		$hasNext = true;
		$array_response = ['dati'=>[]];
		// se esiste un file cache parziale, mi carico la risposta in memoria
		if (file_exists($cacheFile)) {
			$response = file_get_contents($cacheFile);
			$array_response = json_decode($response, true);
		}
		
		// Controlla se il file flag esiste e recupera il valore di next
		if (file_exists($flagFilePath)) {
		    $next = file_get_contents($flagFilePath);
		} else {
			file_put_contents($flagFilePath, $next);
		}

		try {
		    while ($hasNext) {
		        // Effettua la chiamata API utilizzando il metodo esistente
		        if ( is_object($output_terminale) )
		        	$output_terminale->writeln('<info>Inizio chiamata mexal con next:'.$next.'</info>');
		        $response = $this->getApiArticoliRicercaCall($next);

		        // Assumi che la risposta sia in formato JSON e decodificala
		        $data = json_decode($response, true);

		        if (isset($data['error'])) {
		            //throw new \Exception("Errore nella risposta API: " . $data['error']);
		        }

		        // Aggiungi i dati al buffer
		        if (isset($data['dati'])) {
		            foreach ($data['dati'] as $item) {
		                //$buffer .= json_encode($item) . PHP_EOL;
		                array_push($array_response['dati'], $item);
		            }
		        }
		        
		        //file_put_contents($cacheFile, json_encode($array_response, JSON_PRETTY_PRINT)); // se voglio sia formattato per essere letto anche manualmente
		        file_put_contents($cacheFile, json_encode($array_response)); // crea json tutto appiccicato

		        // Controlla se esiste un ID_NEXT per proseguire con la paginazione
		        $hasNext = isset($data['next']) && $data['next'];
		        if ($hasNext) {
		            $next = $data['next'];
		            file_put_contents($flagFilePath, $next);
		        } else {
		            // Cancella il file flag quando tutte le pagine sono state elaborate
		            if (file_exists($flagFilePath)) {
		                unlink($flagFilePath);
		            }
		        }
		    }
		    
		} finally {
		            // Cancella il file flag quando tutte le pagine sono state elaborate
		            if (file_exists($flagFilePath)) {
		                unlink($flagFilePath);
		            }
		}

		// Carica la risposta dal file
		return file_get_contents($cacheFile);
	}


	 // fa chiamata per ottenere QTA E PREZZO 
    public function getApiArticoliRicercaCall($next = '')
    {
        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_articoli_ricerca/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_articoli_ricerca/header_value_1', ScopeInterface::SCOPE_STORE);
        $numero_giorni = $this->scopeConfig->getValue('mexal_config/chiamata_articoli_ricerca/numero_giorni', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_articoli_ricerca/url', ScopeInterface::SCOPE_STORE);

		 // Calcola la data attuale e sottrai il numero di giorni
		 $currentDate = new \DateTime();
		 $currentDate->modify('-' . $numero_giorni . ' days');
		 $formattedDate = $currentDate->format('Ymd'); // Formattata come "YYYYMMDD"

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;
        
		//error_log('Inizio chiamata API per articoli ricerca');

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader($headerKey1, $headerValue1);
		$this->curl->addHeader('Content-Type', 'application/json'); // Aggiungi l'header Content-Type
		 
		 // Corpo della richiesta (JSON)
		 if ( strlen($next) ) {
			 $body = json_encode([
				 'filtri' => [
				     [
				         'campo' => 'dt_mod',
				         'condizione' => '>=',
				         'valore' => $formattedDate
				     ]
				 ],
				 'next' => "$next"
			 ]);
		 } else {
			 $body = json_encode([
				 'filtri' => [
				     [
				         'campo' => 'dt_mod',
				         'condizione' => '>=',
				         'valore' => $formattedDate
				     ]
				 ]
			 ]);		 
		 }
    
        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

		 // Imposta l'opzione per la richiesta POST con il corpo JSON
		 $this->curl->post($fullUrl, $body);

        // Recupera la risposta
        $response = $this->curl->getBody();

        return $response;
    }


    public function getApiClientiRicercaResponse($reset = false, $data = [])
    {
		$cacheFile = BP . '/var/tmp/clienti_ricerca_response.txt'; // File per il caching
		
		if ( file_exists($cacheFile) && $reset )
			unlink($cacheFile);

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_clienti_ricerca/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_clienti_ricerca/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_clienti_ricerca/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader($headerKey1, $headerValue1);
        $this->curl->addHeader('Content-Type', 'application/json');

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti
		
		// tanto sono pochi i clienti e quindi li prendo tutti
		// Corpo della richiesta (JSON)
		$data_filtri = [ 'filtri' => [] ]; 
		if ( array_key_exists('email', $data) && strlen(trim($data['email'])) )
			$data_filtri['filtri'][] = ["campo" => "email", "condizione" => "=", "valore" => trim($data['email'])];
		//print_r($data_filtri);
		$body = json_encode($data_filtri);

        // Esegui la chiamata API
        $this->curl->post($fullUrl, $body);

        // Recupera la risposta
        $response = $this->curl->getBody();

		// Salva la risposta nel file di cache
		file_put_contents($cacheFile, $response);

        return $response;
    }

    public function getApiGeneraleArticoliResponse($output_terminale = null)
    {
    	//error_log('INIZIO generaGeneraleArticoli');
		$cacheFile = BP . '/var/tmp/chiamata_generale_articoli.txt'; // File per il caching
		$flagFilePath = BP . '/var/tmp/chiamata_generale_articoli_flag.txt'; // File flag per salvare lo stato
		
		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile) && !file_exists($flagFilePath)) {
		    return file_get_contents($cacheFile);
		}
		// incongruenza non dovrebbe succedere mai ma nel caso reimposta cancellando
		if (!file_exists($cacheFile) && file_exists($flagFilePath))
			unlink($flagFilePath);

		$next = ''; // mexal usa codice alfanumerico
		$hasNext = true;
		$array_response = ['dati'=>[]];
		// se esiste un file cache parziale, mi carico la risposta in memoria
		if (file_exists($cacheFile)) {
			$response = file_get_contents($cacheFile);
			$array_response = json_decode($response, true);
		}
		
		// Controlla se il file flag esiste e recupera il valore di next
		if (file_exists($flagFilePath)) {
		    $next = file_get_contents($flagFilePath);
		} else {
			file_put_contents($flagFilePath, $next);
		}

		try {
		    while ($hasNext) {
		        if ( is_object($output_terminale) )
		        	$output_terminale->writeln('<info>Inizio chiamata mexal con next:'.$next.'</info>');
				// Effettua la chiamata API utilizzando il metodo esistente
		        $response = $this->getApiGeneraleArticoliCall($next);

		        // Assumi che la risposta sia in formato JSON e decodificala
		        $data = json_decode($response, true);

		        if (isset($data['error'])) {
		            //throw new \Exception("Errore nella risposta API: " . $data['error']);
		        }

		        // Aggiungi i dati al buffer
		        if (isset($data['dati'])) {
		            foreach ($data['dati'] as $item) {
		                //$buffer .= json_encode($item) . PHP_EOL;
		                array_push($array_response['dati'], $item);
		            }
		        }
		        
		        //file_put_contents($cacheFile, json_encode($array_response, JSON_PRETTY_PRINT)); // se voglio sia formattato per essere letto anche manualmente
		        file_put_contents($cacheFile, json_encode($array_response)); // crea json tutto appiccicato

		        // Controlla se esiste un ID_NEXT per proseguire con la paginazione
		        $hasNext = isset($data['next']) && $data['next'];
		        if ($hasNext) {
		            $next = $data['next'];
		            file_put_contents($flagFilePath, $next);
		        } else {
		            // Cancella il file flag quando tutte le pagine sono state elaborate
		            if (file_exists($flagFilePath)) {
		                unlink($flagFilePath);
		            }
		        }
		    }
		    
		} finally {
		            // Cancella il file flag quando tutte le pagine sono state elaborate
		            if (file_exists($flagFilePath)) {
		                unlink($flagFilePath);
		            }
		}

		// Carica la risposta dal file
		return file_get_contents($cacheFile);
    }


    public function getApiGeneraleArticoliCall($next = '')
    {
        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_articoli/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_articoli/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_articoli/url', ScopeInterface::SCOPE_STORE);
        $numero_giorni = (int) $this->scopeConfig->getValue('mexal_config/chiamata_generale_articoli/numero_giorni', ScopeInterface::SCOPE_STORE);
        $sku = trim($this->scopeConfig->getValue('mexal_config/chiamata_generale_articoli/sku', ScopeInterface::SCOPE_STORE)?? '');


		 // Calcola la data attuale meno il numero di giorni
		 $dataModifica = new \DateTime();
		 $dataModifica->modify('-' . $numero_giorni . ' days');
		 $dataModificaString = $dataModifica->format('Ymd'); // Formattata come "YYYYMMDD"


        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

		if ( strlen($sku) ) {
		 // Corpo della richiesta (JSON)
		 $body = json_encode([
		     "filtri" => [
		         [
		             "campo" => "dati_campi",
		             "indice1" => 1, // sku
		             "condizione" => "=",
		             "valore" => "$sku"
		         ]
		     ]
		 ]);
		} else {
		
		 if ( strlen($next) ) {
			 // Corpo della richiesta (JSON)
			 $body = json_encode([
				 "filtri" => [
				 	/*
				     [
				         "campo" => "dati_campi",
				         "indice1" => 11, // che sia web
				         "condizione" => "=",
				         "valore" => "S" // true
				     ],
				     */
				     [
				         "campo" => "data_ult_mod",
				         "condizione" => ">=",
				         "valore" => $dataModificaString
				     ]
				 ],
				 'next' => "$next"
			 ]);
		 } else {
			 // Corpo della richiesta (JSON)
			 $body = json_encode([
				 "filtri" => [
				 	/*
				     [
				         "campo" => "dati_campi",
				         "indice1" => 11, // che sia web
				         "condizione" => "=",
				         "valore" => "S" // true
				     ],
				     */
				     [
				         "campo" => "data_ult_mod",
				         "condizione" => ">=",
				         "valore" => $dataModificaString
				     ]
				 ]
			 ]);
		 }
		 
		}
		//error_log('ESEGUO API POST generaGeneraleArticoli');
        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->post($fullUrl, $body);

        // Recupera la risposta
        $response = $this->curl->getBody();

        return $response;
    }

    public function OLD_getApiGeneraleCalibriResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_calibri.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_calibri/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_calibri/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_calibri/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

		// Salva la risposta nel file di cache
		file_put_contents($cacheFile, $response);

        return $response;
    }

    public function getApiGeneraleCalibriResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_calibri.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

		$next = ''; // mexal usa codice alfanumerico
		$hasNext = true;
		$array_response = ['dati'=>[]];

		try {
		    while ($hasNext) {
				// Effettua la chiamata API utilizzando il metodo esistente
		        $response = $this->getApiGeneraleCalibriCall($next);

		        // Assumi che la risposta sia in formato JSON e decodificala
		        $data = json_decode($response, true);

		        if (isset($data['error'])) {
		            //throw new \Exception("Errore nella risposta API: " . $data['error']);
		        }

		        // Aggiungi i dati al buffer
		        if (isset($data['dati'])) {
		            foreach ($data['dati'] as $item) {
		                //$buffer .= json_encode($item) . PHP_EOL;
		                array_push($array_response['dati'], $item);
		            }
		        }

		        // Controlla se esiste un ID_NEXT per proseguire con la paginazione
		        $hasNext = isset($data['next']) && $data['next'];
		        if ($hasNext) {
		            $next = $data['next'];
		            //file_put_contents($flagFilePath, $next);
		        }
		    }
		    
		} finally {
		    file_put_contents($cacheFile, json_encode($array_response, JSON_PRETTY_PRINT)); // se voglio sia formattato per essere letto anche manualmente
		    //file_put_contents($cacheFile, json_encode($array_response)); // crea json tutto appiccicato
		}

		// Carica la risposta dal file
		return file_get_contents($cacheFile);
    }

    public function getApiGeneraleCalibriCall($next = '')
    {
        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_calibri/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_calibri/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_calibri/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;
        if (!empty($next)) {
            $fullUrl .= '?next=' . urlencode($next);
        }

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

        return $response;
    }


    public function OLD_getApiGeneraleAltezza1Response()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_altezza1.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_altezza1/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_altezza1/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_altezza1/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

		// Salva la risposta nel file di cache
		file_put_contents($cacheFile, $response);

        return $response;
    }

    public function getApiGeneraleAltezza1Call($next = '')
    {
        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_altezza1/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_altezza1/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_altezza1/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;
        if (!empty($next)) {
            $fullUrl .= '?next=' . urlencode($next);
        }

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

        return $response;
    }

    public function getApiGeneraleAltezza1Response()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_altezza1.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

		$next = ''; // mexal usa codice alfanumerico
		$hasNext = true;
		$array_response = ['dati'=>[]];

		try {
		    while ($hasNext) {
				// Effettua la chiamata API utilizzando il metodo esistente
		        $response = $this->getApiGeneraleAltezza1Call($next);

		        // Assumi che la risposta sia in formato JSON e decodificala
		        $data = json_decode($response, true);

		        if (isset($data['error'])) {
		            //throw new \Exception("Errore nella risposta API: " . $data['error']);
		        }

		        // Aggiungi i dati al buffer
		        if (isset($data['dati'])) {
		            foreach ($data['dati'] as $item) {
		                //$buffer .= json_encode($item) . PHP_EOL;
		                array_push($array_response['dati'], $item);
		            }
		        }

		        // Controlla se esiste un ID_NEXT per proseguire con la paginazione
		        $hasNext = isset($data['next']) && $data['next'];
		        if ($hasNext) {
		            $next = $data['next'];
		            //file_put_contents($flagFilePath, $next);
		        }
		    }
		    
		} finally {
		    file_put_contents($cacheFile, json_encode($array_response, JSON_PRETTY_PRINT)); // se voglio sia formattato per essere letto anche manualmente
		    //file_put_contents($cacheFile, json_encode($array_response)); // crea json tutto appiccicato
		}

		// Carica la risposta dal file
		return file_get_contents($cacheFile);
    }

    public function OLD_getApiGeneraleCompdiesResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_compdies.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_compdies/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_compdies/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_compdies/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

		// Salva la risposta nel file di cache
		file_put_contents($cacheFile, $response);

        return $response;
    }

    public function getApiGeneraleCompdiesCall($next = '')
    {
        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_compdies/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_compdies/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_compdies/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;
        if (!empty($next)) {
            $fullUrl .= '?next=' . urlencode($next);
        }

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

        return $response;
    }

    public function getApiGeneraleCompdiesResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_compdies.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

		$next = ''; // mexal usa codice alfanumerico
		$hasNext = true;
		$array_response = ['dati'=>[]];

		try {
		    while ($hasNext) {
				// Effettua la chiamata API utilizzando il metodo esistente
		        $response = $this->getApiGeneraleCompdiesCall($next);

		        // Assumi che la risposta sia in formato JSON e decodificala
		        $data = json_decode($response, true);

		        if (isset($data['error'])) {
		            //throw new \Exception("Errore nella risposta API: " . $data['error']);
		        }

		        // Aggiungi i dati al buffer
		        if (isset($data['dati'])) {
		            foreach ($data['dati'] as $item) {
		                //$buffer .= json_encode($item) . PHP_EOL;
		                array_push($array_response['dati'], $item);
		            }
		        }

		        // Controlla se esiste un ID_NEXT per proseguire con la paginazione
		        $hasNext = isset($data['next']) && $data['next'];
		        if ($hasNext) {
		            $next = $data['next'];
		            //file_put_contents($flagFilePath, $next);
		        }
		    }
		    
		} finally {
		    file_put_contents($cacheFile, json_encode($array_response, JSON_PRETTY_PRINT)); // se voglio sia formattato per essere letto anche manualmente
		    //file_put_contents($cacheFile, json_encode($array_response)); // crea json tutto appiccicato
		}

		// Carica la risposta dal file
		return file_get_contents($cacheFile);
    }

    public function getApiGeneralePiomboarResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_piomboar.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_piomboar/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_piomboar/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_piomboar/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

		// Salva la risposta nel file di cache
		file_put_contents($cacheFile, $response);

        return $response;
    }

    public function getApiGeneraleFondelloResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_fondello.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_fondello/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_fondello/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_fondello/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

		// Salva la risposta nel file di cache
		file_put_contents($cacheFile, $response);

        return $response;
    }

    public function OLD_getApiGeneraleColpiarmResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_colpiarm.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_colpiarm/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_colpiarm/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_colpiarm/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

		// Salva la risposta nel file di cache
		file_put_contents($cacheFile, $response);

        return $response;
    }

    public function getApiGeneraleColpiarmCall($next = '')
    {
        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_colpiarm/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_colpiarm/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_colpiarm/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;
        if (!empty($next)) {
            $fullUrl .= '?next=' . urlencode($next);
        }

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

        return $response;
    }

    public function getApiGeneraleColpiarmResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_colpiarm.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

		$next = ''; // mexal usa codice alfanumerico
		$hasNext = true;
		$array_response = ['dati'=>[]];

		try {
		    while ($hasNext) {
				// Effettua la chiamata API utilizzando il metodo esistente
		        $response = $this->getApiGeneraleColpiarmCall($next);

		        // Assumi che la risposta sia in formato JSON e decodificala
		        $data = json_decode($response, true);

		        if (isset($data['error'])) {
		            //throw new \Exception("Errore nella risposta API: " . $data['error']);
		        }

		        // Aggiungi i dati al buffer
		        if (isset($data['dati'])) {
		            foreach ($data['dati'] as $item) {
		                //$buffer .= json_encode($item) . PHP_EOL;
		                array_push($array_response['dati'], $item);
		            }
		        }

		        // Controlla se esiste un ID_NEXT per proseguire con la paginazione
		        $hasNext = isset($data['next']) && $data['next'];
		        if ($hasNext) {
		            $next = $data['next'];
		            //file_put_contents($flagFilePath, $next);
		        }
		    }
		    
		} finally {
		    file_put_contents($cacheFile, json_encode($array_response, JSON_PRETTY_PRINT)); // se voglio sia formattato per essere letto anche manualmente
		    //file_put_contents($cacheFile, json_encode($array_response)); // crea json tutto appiccicato
		}

		// Carica la risposta dal file
		return file_get_contents($cacheFile);
    }

    public function getApiGeneraleStellearResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_stellear.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_stellear/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_stellear/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_stellear/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

		// Salva la risposta nel file di cache
		file_put_contents($cacheFile, $response);

        return $response;
    }

    public function OLD_getApiGeneraleDiametroResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_diametro.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_diametro/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_diametro/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_diametro/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

		// Salva la risposta nel file di cache
		file_put_contents($cacheFile, $response);

        return $response;
    }

    public function getApiGeneraleDiametroCall($next = '')
    {
        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_diametro/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_diametro/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_diametro/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;
        if (!empty($next)) {
            $fullUrl .= '?next=' . urlencode($next);
        }

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

        return $response;
    }

    public function getApiGeneraleDiametroResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_diametro.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

		$next = ''; // mexal usa codice alfanumerico
		$hasNext = true;
		$array_response = ['dati'=>[]];

		try {
		    while ($hasNext) {
				// Effettua la chiamata API utilizzando il metodo esistente
		        $response = $this->getApiGeneraleDiametroCall($next);

		        // Assumi che la risposta sia in formato JSON e decodificala
		        $data = json_decode($response, true);

		        if (isset($data['error'])) {
		            //throw new \Exception("Errore nella risposta API: " . $data['error']);
		        }

		        // Aggiungi i dati al buffer
		        if (isset($data['dati'])) {
		            foreach ($data['dati'] as $item) {
		                //$buffer .= json_encode($item) . PHP_EOL;
		                array_push($array_response['dati'], $item);
		            }
		        }

		        // Controlla se esiste un ID_NEXT per proseguire con la paginazione
		        $hasNext = isset($data['next']) && $data['next'];
		        if ($hasNext) {
		            $next = $data['next'];
		            //file_put_contents($flagFilePath, $next);
		        }
		    }
		    
		} finally {
		    file_put_contents($cacheFile, json_encode($array_response, JSON_PRETTY_PRINT)); // se voglio sia formattato per essere letto anche manualmente
		    //file_put_contents($cacheFile, json_encode($array_response)); // crea json tutto appiccicato
		}

		// Carica la risposta dal file
		return file_get_contents($cacheFile);
    }

    public function OLD_getApiGeneraleTagabbigResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_tagabbig.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_tagabbig/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_tagabbig/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_tagabbig/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

		// Salva la risposta nel file di cache
		file_put_contents($cacheFile, $response);

        return $response;
    }

    public function getApiGeneraleTagabbigCall($next = '')
    {
        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_tagabbig/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_tagabbig/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_tagabbig/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;
        if (!empty($next)) {
            $fullUrl .= '?next=' . urlencode($next);
        }

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

        return $response;
    }

    public function getApiGeneraleTagabbigResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_tagabbig.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

		$next = ''; // mexal usa codice alfanumerico
		$hasNext = true;
		$array_response = ['dati'=>[]];

		try {
		    while ($hasNext) {
				// Effettua la chiamata API utilizzando il metodo esistente
		        $response = $this->getApiGeneraleTagabbigCall($next);

		        // Assumi che la risposta sia in formato JSON e decodificala
		        $data = json_decode($response, true);

		        if (isset($data['error'])) {
		            //throw new \Exception("Errore nella risposta API: " . $data['error']);
		        }

		        // Aggiungi i dati al buffer
		        if (isset($data['dati'])) {
		            foreach ($data['dati'] as $item) {
		                //$buffer .= json_encode($item) . PHP_EOL;
		                array_push($array_response['dati'], $item);
		            }
		        }

		        // Controlla se esiste un ID_NEXT per proseguire con la paginazione
		        $hasNext = isset($data['next']) && $data['next'];
		        if ($hasNext) {
		            $next = $data['next'];
		            //file_put_contents($flagFilePath, $next);
		        }
		    }
		    
		} finally {
		    file_put_contents($cacheFile, json_encode($array_response, JSON_PRETTY_PRINT)); // se voglio sia formattato per essere letto anche manualmente
		    //file_put_contents($cacheFile, json_encode($array_response)); // crea json tutto appiccicato
		}

		// Carica la risposta dal file
		return file_get_contents($cacheFile);
    }

    public function OLD_getApiGeneraleColorearResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_colorear.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_colorear/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_colorear/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_colorear/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

		// Salva la risposta nel file di cache
		file_put_contents($cacheFile, $response);

        return $response;
    }

    public function getApiGeneraleColorearCall($next = '')
    {
        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_colorear/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_colorear/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_colorear/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;
        if (!empty($next)) {
            $fullUrl .= '?next=' . urlencode($next);
        }

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

        return $response;
    }

    public function getApiGeneraleColorearResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_colorear.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

		$next = ''; // mexal usa codice alfanumerico
		$hasNext = true;
		$array_response = ['dati'=>[]];

		try {
		    while ($hasNext) {
				// Effettua la chiamata API utilizzando il metodo esistente
		        $response = $this->getApiGeneraleColorearCall($next);

		        // Assumi che la risposta sia in formato JSON e decodificala
		        $data = json_decode($response, true);

		        if (isset($data['error'])) {
		            //throw new \Exception("Errore nella risposta API: " . $data['error']);
		        }

		        // Aggiungi i dati al buffer
		        if (isset($data['dati'])) {
		            foreach ($data['dati'] as $item) {
		                //$buffer .= json_encode($item) . PHP_EOL;
		                array_push($array_response['dati'], $item);
		            }
		        }

		        // Controlla se esiste un ID_NEXT per proseguire con la paginazione
		        $hasNext = isset($data['next']) && $data['next'];
		        if ($hasNext) {
		            $next = $data['next'];
		            //file_put_contents($flagFilePath, $next);
		        }
		    }
		    
		} finally {
		    file_put_contents($cacheFile, json_encode($array_response, JSON_PRETTY_PRINT)); // se voglio sia formattato per essere letto anche manualmente
		    //file_put_contents($cacheFile, json_encode($array_response)); // crea json tutto appiccicato
		}

		// Carica la risposta dal file
		return file_get_contents($cacheFile);
    }

    public function getApiGeneraleAnnoarmeResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_annoarme.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_annoarme/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_annoarme/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_annoarme/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

		// Salva la risposta nel file di cache
		file_put_contents($cacheFile, $response);

        return $response;
    }

    public function OLD_getApiGeneraleMaterialResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_material.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_material/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_material/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_material/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

		// Salva la risposta nel file di cache
		file_put_contents($cacheFile, $response);

        return $response;
    }

    public function getApiGeneraleMaterialCall($next = '')
    {
        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_material/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_generale_material/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_generale_material/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;
        if (!empty($next)) {
            $fullUrl .= '?next=' . urlencode($next);
        }

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

        // Esegui la chiamata API
        $this->curl->get($fullUrl);

        // Recupera la risposta
        $response = $this->curl->getBody();

        return $response;
    }

    public function getApiGeneraleMaterialResponse()
    {
		$cacheFile = BP . '/var/tmp/chiamata_generale_material.txt'; // File per il caching

		// Verifica se il file esiste già (e quindi usa la cache)
		if (file_exists($cacheFile)) {
		     // Carica la risposta dal file
		     return file_get_contents($cacheFile);
		}

		$next = ''; // mexal usa codice alfanumerico
		$hasNext = true;
		$array_response = ['dati'=>[]];

		try {
		    while ($hasNext) {
				// Effettua la chiamata API utilizzando il metodo esistente
		        $response = $this->getApiGeneraleMaterialCall($next);

		        // Assumi che la risposta sia in formato JSON e decodificala
		        $data = json_decode($response, true);

		        if (isset($data['error'])) {
		            //throw new \Exception("Errore nella risposta API: " . $data['error']);
		        }

		        // Aggiungi i dati al buffer
		        if (isset($data['dati'])) {
		            foreach ($data['dati'] as $item) {
		                //$buffer .= json_encode($item) . PHP_EOL;
		                array_push($array_response['dati'], $item);
		            }
		        }

		        // Controlla se esiste un ID_NEXT per proseguire con la paginazione
		        $hasNext = isset($data['next']) && $data['next'];
		        if ($hasNext) {
		            $next = $data['next'];
		            //file_put_contents($flagFilePath, $next);
		        }
		    }
		    
		} finally {
		    file_put_contents($cacheFile, json_encode($array_response, JSON_PRETTY_PRINT)); // se voglio sia formattato per essere letto anche manualmente
		    //file_put_contents($cacheFile, json_encode($array_response)); // crea json tutto appiccicato
		}

		// Carica la risposta dal file
		return file_get_contents($cacheFile);
    }



	public function clearCacheFiles()
	{
		 // Path alla directory di cache
		 $cacheDirectory = BP . '/var/tmp/';

		 // Lista di file di cache che vuoi cancellare
		 $cacheFiles = [
		     'clienti_ricerca_response.txt',
		     'help_response.txt',
		     'progressivi_articoli_response.txt',
		     'articoli_ricerca_response.txt',
		     'gruppi_merceologici_response.txt',
		     'chiamata_generale_articoli.txt',
		     'chiamata_generale_calibri.txt',
		     'chiamata_generale_altezza1.txt',
		     'chiamata_generale_compdies.txt',
		     'chiamata_generale_piomboar.txt',
		     'chiamata_generale_fondello.txt',
		     'chiamata_generale_colpiarm.txt',
		     'chiamata_generale_stellear.txt',
		     'chiamata_generale_diametro.txt',
		     'chiamata_generale_tagabbig.txt',
		     'chiamata_generale_colorear.txt',
		     'chiamata_generale_annoarme.txt',
		     'chiamata_generale_material.txt'
		 ];

		 // Array per memorizzare i file cancellati
		 $deletedFiles = [];

		 // Itera attraverso i file e cancella se esistono
		 foreach ($cacheFiles as $file) {
		     $filePath = $cacheDirectory . $file;

		     if (file_exists($filePath)) {
		         unlink($filePath); // Cancella il file
		         $deletedFiles[] = $file; // Aggiungi il nome del file all'array
		     }
		 }

		 // Se non sono stati cancellati file
		 if (empty($deletedFiles)) {
		     return __('No cache files were deleted.');
		 }

		 // Restituisce l'elenco dei file cancellati
		 return __('Deleted cache files: ') . implode(', ', $deletedFiles);
	}

    // Metodo per inviare l'anagrafica del cliente a Mexal
    public function sendCustomerToMexal($customerData)
    {
        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_clienti_invio/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_clienti_invio/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_clienti_invio/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

		$jsonPayload = json_encode($customerData);
		
		//error_log('ESEGUO API POST generaGeneraleArticoli');

        // Esegui la chiamata API
        $this->curl->post($fullUrl, $jsonPayload);

        // Recupera la risposta
        $response = $this->curl->getBody();

        return $response;
    }

	public function getLastOrderNumberFromMexal()
	{

        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_ordine_invio/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_ordine_invio/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_ordine_invio/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

		$this->curl->get($fullUrl);
		$responseBody = $this->curl->getBody();
		$responseStatus = $this->curl->getStatus();

		if ($responseStatus === 200) {
		    $data = json_decode($responseBody, true);
		    $data = array_key_exists('dati', $data) ? $data['dati'] : [];
		    if ( count($data) ) {
				$lastOrder = end($data); // Prendi l'ultimo ordine
				return (int)$lastOrder['numero']; // Ottieni il numero e fai il cast a intero
		    } else {
		    	return 0;
		    }
		} else {
		    throw new \Exception("Errore durante il recupero del numero d'ordine da Mexal");
		}
	}

    // Metodo per inviare ordine a Mexal
    public function sendOrderToMexal($orderJson)
    {
        // Recupera i valori di configurazione
        $headerKey0 = 'Authorization';
        $headerValue0 = $this->scopeConfig->getValue('mexal_config/general/api_key_authorization', ScopeInterface::SCOPE_STORE);
        $headerKey1 = $this->scopeConfig->getValue('mexal_config/chiamata_ordine_invio/header_key_1', ScopeInterface::SCOPE_STORE);
        $headerValue1 = $this->scopeConfig->getValue('mexal_config/chiamata_ordine_invio/header_value_1', ScopeInterface::SCOPE_STORE);
        $base_api_url = $this->scopeConfig->getValue('mexal_config/general/base_api_url', ScopeInterface::SCOPE_STORE);
        $url = $this->scopeConfig->getValue('mexal_config/chiamata_ordine_invio/url', ScopeInterface::SCOPE_STORE);

        // Aggiungi la query string all'URL
        $fullUrl = $base_api_url . $url;

        // Imposta l'header della richiesta
        $this->curl->addHeader($headerKey0, $headerValue0);
        $this->curl->addHeader('Content-Type', 'application/json');
        //$this->curl->addHeader('Coordinate-Gestionale', 'Azienda=ARM Anno=2023 Magazzino=1');
        $this->curl->addHeader($headerKey1, $headerValue1);

        // Disabilita la verifica SSL (per ambienti di sviluppo)
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);

		// Imposta un timeout lungo per evitare che la richiesta scada
		$this->curl->setOption(CURLOPT_TIMEOUT, 600); // Timeout di 10 minuti

		$jsonPayload = $orderJson; //json_encode($orderData);
		//echo "###############$jsonPayload###############";		
		//error_log('ESEGUO API POST generaGeneraleArticoli');
		//echo "###############$fullUrl###############";
        // Esegui la chiamata API
        $this->curl->post($fullUrl, $jsonPayload);
		// Recupera la risposta
		$responseBody = $this->curl->getBody();
		$responseStatus = $this->curl->getStatus();

        // Recupera la risposta
        //$response = $this->curl->getBody();
        //return $response;
        // Analizza la risposta

		// Analizza la risposta
		if ($responseStatus === 201) {
			// Successo
			return [
				'success' => true,
				'message' => 'Ordine inviato con successo',
				'data' => json_decode($responseBody, true)
			];
		} else {
			// Gestione degli errori
			return [
				'success' => false,
				'message' => 'Errore durante l\'invio dell\'ordine: ' . $responseBody,
				'data' => json_decode($responseBody, true)
			];
		}
	}


}

