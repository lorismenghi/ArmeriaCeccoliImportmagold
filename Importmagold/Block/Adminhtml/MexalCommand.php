<?php
namespace LM\Importmagold\Block\Adminhtml;

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
use Magento\Framework\App\Bootstrap;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Filesystem\DirectoryList;
use LM\Importmagold\Model\ImportatoreProduct;
use LM\Importmagold\Model\ImportProductMexalServiceCommand as ImportProductMexalService;

class MexalCommand extends Mexal
{

	//protected $importatoreProduct;
	protected $importProductMexalService;
	protected $bootstrap;
	protected $objectManager;
	protected $data_product = [];


    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resourceConnection,
        Curl $curl,
        ImportProductMexalService $importProductMexalService,
        array $data = []
    ) {
        parent::__construct($context, $scopeConfig, $resourceConnection, $curl, $data);
        $this->bootstrap = Bootstrap::create(BP, $_SERVER);
        $this->objectManager = $this->bootstrap->getObjectManager();
        //$this->importatoreProduct = new ImportatoreProduct(); // non mi serve direttamente qui
        $this->importProductMexalService = $importProductMexalService;
    }

	// interpola 2 chiamate API in modo da avere sia il prezzo che la qta
	// ma non fa piu la chiamata principale al suo interno, ma la riceva in input
	public function getArrayRispostaArticoliRicercaRielaborati($output_terminale = null, $array_risposta_articoli_ricerca = null)
	{
		 // Ottieni la risposta completa degli articoli
		 $articoli = $array_risposta_articoli_ricerca;
		 // Ottieni la quantità per ciascun codice articolo
		 //$codiciQta = $this->getArrayCodiceQta(); // ma adesso la vogliamo prendere dalla chiamata ricerca
		 
		 $this->array_codice_gruppo_merceologico = $this->getArrayRispostaGruppiMerceologiciRielaborati();

		 // Definisci i campi utili che desideri mantenere
		 $articoliRielaborati = [];

		 foreach ($articoli as $articolo) {

			//if ( is_object($output_terminale) )
				//$output_terminale->writeln(print_r($articolo, true));

		     // Estrai i prezzi di listino e prezzo speciale
		     $prz_listino = null;
		     $prz_speciale = null;
		     foreach ($articolo['prz_listino'] as $prz) {
		         if ($prz[0] == 1) {
		             $prz_listino = $prz[1];
		         }
		         if ($prz[0] == 6) {
		             $prz_speciale = $prz[1];
		         }
		     }

		     // Estrai la quantità dall'array codiciQta
		     //$qta = isset($codiciQta[$articolo['codice']]) ? $codiciQta[$articolo['codice']] : 0;
		     $qta_ord_dimp = array_key_exists('qta_ord_dimp', $articolo) ? $articolo['qta_ord_dimp'] : 0;
		     $qta_ord_imp = array_key_exists('qta_ord_imp', $articolo) ? $articolo['qta_ord_imp'] : 0;
		     $qta = $articolo['qta_inventario'] + $articolo['qta_carico'] - $articolo['qta_scarico'] - $qta_ord_dimp - $qta_ord_imp;
			
			$nome_categoria = ''; $codice_categoria_parent = ''; $nome_categoria_parent = '';
			$cod_grp_merc = trim($articolo['cod_grp_merc']); //print_r($this->array_codice_gruppo_merceologico);
			//if ( is_object($output_terminale) )
				//$output_terminale->writeln("cod_grp_merc:$cod_grp_merc");
			if ( strlen($cod_grp_merc) && array_key_exists($cod_grp_merc, $this->array_codice_gruppo_merceologico) ) {
				$nome_categoria = $this->array_codice_gruppo_merceologico[$cod_grp_merc]['descrizione'];
				$codice_categoria_parent = $this->array_codice_gruppo_merceologico[$cod_grp_merc]['cod_grp_merc']; // nella chiamata categorie cod_grp_merc è ilcodice della parent
			}
			if ( strlen($codice_categoria_parent) && array_key_exists($codice_categoria_parent, $this->array_codice_gruppo_merceologico) ) {
				$nome_categoria_parent = $this->array_codice_gruppo_merceologico[$codice_categoria_parent]['descrizione'];
			}
			
		     // Aggiungi i campi filtrati all'array
		     $articoliRielaborati[] = [
		         'codice' => $articolo['codice'], // SKU del prodotto
		         //'descrizione' => $articolo['descrizione'] . $articolo['descrizione_agg'], // Nome prodotto
		         //'descrizione' => $articolo['descrizione'],
		         'name' => $articolo['descr_completa'],
		         'cod_grp_merc' => $cod_grp_merc, // Codice gruppo merceologico / categoria
		         'nome_categoria' => $nome_categoria,
		         'codice_categoria' => $cod_grp_merc, // nel prodotto cod_grp_merc è la categoria, non la parent
		         'codice_categoria_parent' => $codice_categoria_parent,
		         'nome_categoria_parent' => $nome_categoria_parent,
		         'prz_listino' => $prz_listino, // Prezzo di vendita (posizione 1)
		         'prz_speciale' => $prz_speciale, // Prezzo speciale (posizione 6, se presente)
		         'min_sale_qty' => (int) $articolo['qta_min_fatt'],
		         'qta_inventario' => $articolo['qta_inventario'],
		         'qta_carico' => $articolo['qta_carico'],
				 'qta_scarico' => $articolo['qta_scarico'],
				 'qta_ord_dimp' => $qta_ord_dimp, // non esistono piu
				 'qta_ord_imp' => $qta_ord_imp,
		         'qta' => $qta // Quantità per articolo
		     ];
		 }

		 return $articoliRielaborati;
	}

	// al suo interno fa ciclo chiamate e per ogni chiamata sinc articoli
	// se si dovesse interrompere cancellare il flag e lasciare il cacheFile
	public function getApiArticoliRicercaResponse($output_terminale = null)
	{
		$cacheFile = BP . '/var/tmp/articoli_ricerca_response_command.txt'; // File per il caching
		$flagFilePath = BP . '/var/tmp/articoli_ricerca_flag_command.txt'; // File flag per salvare lo stato
		
		// Verifica se il file esiste già (e quindi usa la cache)
		//if (file_exists($cacheFile) && !file_exists($flagFilePath)) {
		    //return file_get_contents($cacheFile);
		//}
		// incongruenza non dovrebbe succedere mai ma nel caso reimposta cancellando
		if (!file_exists($cacheFile) && file_exists($flagFilePath))
			unlink($flagFilePath);

		$next = ''; // mexal usa codice alfanumerico
		$hasNext = true;
		$array_response = ['dati'=>[]];
		
		// non carco piu in memoria la richiesta precedente
		// se esiste un file cache parziale, mi carico la risposta in memoria
		//if (file_exists($cacheFile)) {
		//	$response = file_get_contents($cacheFile);
		//	$array_response = json_decode($response, true);
		//}
		
		// Controlla se il file flag esiste e recupera il valore di next
		if (file_exists($flagFilePath)) {
		    $next = file_get_contents($flagFilePath);
		} else {
			file_put_contents($flagFilePath, $next);
		}

		try {
		    while ($hasNext) {
		        if ( is_object($output_terminale) )
		        	$output_terminale->writeln('<info>Inizio ciclo chiamate mexal con next:'.$next.'</info>');
		        $response = '';
		        
				// Verifica se il file esiste già (e quindi usa la cache)
				if (file_exists($cacheFile) && !file_exists($flagFilePath)) {
					$response = file_get_contents($cacheFile);
					if ( is_object($output_terminale) )
						$output_terminale->writeln('<info>Utilizzo la cache</info>');
				} else {
				    if ( is_object($output_terminale) )
				    	$output_terminale->writeln('<info>Inizio chiamata mexal con next:'.$next.'</info>');
		        	// Effettua la chiamata API utilizzando il metodo esistente
		        	$response = $this->getApiArticoliRicercaCall($next);
		        }

		        // Assumi che la risposta sia in formato JSON e decodificala
		        $data = json_decode($response, true);

		        if (isset($data['error'])) {
		            throw new \Exception("Errore nella risposta API: " . $data['error']);
		        }

		        // Aggiungi i dati al buffer
		        if (isset($data['dati'])) {
		            foreach ($data['dati'] as $item) {
		                //$buffer .= json_encode($item) . PHP_EOL;
		                array_push($array_response['dati'], $item);
		            }
		        }
		        
		        file_put_contents($cacheFile, json_encode($array_response, JSON_PRETTY_PRINT)); // se voglio sia formattato per essere letto anche manualmente
		        //file_put_contents($cacheFile, json_encode($array_response)); // crea json tutto appiccicato

		        // Controlla se esiste un ID_NEXT per proseguire con la paginazione
		        $hasNext = isset($data['next']) && $data['next'];
		        if ($hasNext) {
		            $next = $data['next'];
				    if ( is_object($output_terminale) )
				    	$output_terminale->writeln('<info>E stato trovato il next:'.$next.'</info>');
		            file_put_contents($flagFilePath, $next);
		        } else {
				    if ( is_object($output_terminale) )
				    	$output_terminale->writeln('<info>Cancella il file flag quando tutte le pagine sono state elaborate</info>');

		            // Cancella il file flag quando tutte le pagine sono state elaborate
		            if (file_exists($flagFilePath)) {
		                unlink($flagFilePath);
		            }
		        }
		        
		        // qui devo lanciare lettura del file salvato
		        $array_risposta = $this->getArrayRispostaArticoliRicerca($output_terminale, file_get_contents($cacheFile));
		        $articoli_ricerca_rielaborati = $this->getArrayRispostaArticoliRicercaRielaborati($output_terminale, $array_risposta);
		        // ora effettuo ciclo impostazione
				$this->importProductMexalService = new ImportProductMexalService();
				$output = $this->importProductMexalService->importProductQtaPrz($output_terminale, $articoli_ricerca_rielaborati);
		        
		    } // fine while
		    
		} finally {
		            // Cancella il file flag quando tutte le pagine sono state elaborate
		            if (file_exists($flagFilePath)) {
		                unlink($flagFilePath);
		            }
		}

		// Carica la risposta dal file
		return file_get_contents($cacheFile);
	}

    public function getApiGeneraleArticoliResponse($output_terminale = null)
    {
    	//error_log('INIZIO generaGeneraleArticoli');
		$cacheFile = BP . '/var/tmp/chiamata_generale_articoli_command.txt'; // File per il caching
		$flagFilePath = BP . '/var/tmp/chiamata_generale_articoli_flag_command.txt'; // File flag per salvare lo stato
		
		// Verifica se il file esiste già (e quindi usa la cache)
		//if (file_exists($cacheFile) && !file_exists($flagFilePath)) {
		    //return file_get_contents($cacheFile);
		//}
		// incongruenza non dovrebbe succedere mai ma nel caso reimposta cancellando
		if (!file_exists($cacheFile) && file_exists($flagFilePath))
			unlink($flagFilePath);

		$next = ''; // mexal usa codice alfanumerico
		$hasNext = true;
		$array_response = ['dati'=>[]];
		
		// non carco piu in memoria la richiesta precedente
		// se esiste un file cache parziale, mi carico la risposta in memoria
		//if (file_exists($cacheFile)) {
			//$response = file_get_contents($cacheFile);
			//$array_response = json_decode($response, true);
		//}
		
		// Controlla se il file flag esiste e recupera il valore di next
		if (file_exists($flagFilePath)) {
		    $next = file_get_contents($flagFilePath);
		} else {
			file_put_contents($flagFilePath, $next);
		}

		try {
		    while ($hasNext) {
		        if ( is_object($output_terminale) )
		        	$output_terminale->writeln('<info>Inizio ciclo chiamate mexal con next:'.$next.'</info>');		  
		        $response = '';
		    
				// Verifica se il file esiste già (e quindi usa la cache)
				if (file_exists($cacheFile) && !file_exists($flagFilePath)) {
					$response = file_get_contents($cacheFile);
					if ( is_object($output_terminale) )
						$output_terminale->writeln('<info>Utilizzo la cache</info>');
				} else {
				    if ( is_object($output_terminale) )
				    	$output_terminale->writeln('<info>Inizio chiamata mexal con next:'.$next.'</info>');
		        	// Effettua la chiamata API utilizzando il metodo esistente
		        	$response = $this->getApiGeneraleArticoliCall($next);
		        }

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
				    if ( is_object($output_terminale) )
				    	$output_terminale->writeln('<info>E stato trovato il next:'.$next.'</info>');
		            file_put_contents($flagFilePath, $next);
		        } else {
				    if ( is_object($output_terminale) )
				    	$output_terminale->writeln('<info>Cancella il file flag quando tutte le pagine sono state elaborate</info>');

		            // Cancella il file flag quando tutte le pagine sono state elaborate
		            if (file_exists($flagFilePath)) {
		                unlink($flagFilePath);
		            }
		        }
		        
		        // qui devo lanciare lettura del file salvato
		        $array_risposta = $this->getArrayRispostaGeneraleArticoli($output_terminale, file_get_contents($cacheFile));
		        $articoli_generale_rielaborati = $this->getArrayRispostaGeneraleArticoliRielaborati($output_terminale, $array_risposta);
		        // ora effettuo ciclo importazione
				$this->importProductMexalService = new ImportProductMexalService();
				$output = $this->importProductMexalService->importProductGenerale($output_terminale, $articoli_generale_rielaborati);

		        
		    } // while
		    
		} finally {
		            // Cancella il file flag quando tutte le pagine sono state elaborate
		            if (file_exists($flagFilePath)) {
		                unlink($flagFilePath);
		            }
		}

		// Carica la risposta dal file
		return file_get_contents($cacheFile);
    }


	public function clearCacheFiles($output_terminale = null)
	{
		if ( is_object($output_terminale) )
		    $output_terminale->writeln('<info>Inizio clearCacheFiles</info>');

		 // Path alla directory di cache
		 $cacheDirectory = BP . '/var/tmp/';

		 // Lista di file di cache che vuoi cancellare
		 $cacheFiles = [
		     'clienti_ricerca_response.txt',
		     'help_response.txt',
		     'progressivi_articoli_response.txt',
		     'articoli_ricerca_response_command.txt',
		     'gruppi_merceologici_response.txt',
		     'chiamata_generale_articoli_command.txt',
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

	public function getArrayRispostaGeneraleArticoliRielaborati($output_terminale = null, $array_risposta_generale_articoli = null)
	{
		 // Ottieni la risposta completa degli articoli
		 $articoli = $array_risposta_generale_articoli;
		 // Ottieni la quantità per ciascun codice articolo
		 //$codiciQta = $this->getArrayCodiceQta();

		 // Definisci i campi utili che desideri mantenere
		 $articoliRielaborati = [];

		 foreach ($articoli as $articolo) {
		 	$dati_campi_prodotto = $articolo['dati_campi'];
		 	$articolo_rielaborato = [];
		 	
		 	foreach ($dati_campi_prodotto as $dato) {
		 		// cerco lo sku
		 		if ( $dato[0] == 1 )
		 			$articolo_rielaborato['sku'] = $dato[1];

		 		// cerco lo status (ABILITATO)
		 		if ( $dato[0] == 2 )
		 			$articolo_rielaborato['status'] = $dato[1] == 'S' ? ImportatoreProduct::MAGENTO_STATUS_ENABLED : ImportatoreProduct::MAGENTO_STATUS_DISABLED;

		 		// cerco VENDITA che non uso al momento
		 		if ( $dato[0] == 3 )
		 			$articolo_rielaborato['VENDITA'] = $dato[1] == 'S' ? true : false;

		 		// cerco name (NOME)
		 		if ( $dato[0] == 4 )
		 			$articolo_rielaborato['name'] = $dato[1];

		 		// cerco short_description (DESCRIZIONE BREVE)
		 		if ( $dato[0] == 5 )
		 			$articolo_rielaborato['short_description'] = $dato[1];

		 		// cerco news_from_date (PERIODO VETRINA DA)
		 		if ( $dato[0] == 6 ) {
		 			$dataRicevuta = $dato[1];
		 			// Trasforma la stringa nel formato corretto per Magento
					$dataFormattata = \DateTime::createFromFormat('Ymd', $dataRicevuta)->format('Y-m-d H:i:s');
					$articolo_rielaborato['news_from_date'] = $dataFormattata;
				}

		 		// cerco news_from_date (PERIODO VETRINA A)
		 		if ( $dato[0] == 7 ) {
		 			$dataRicevuta = $dato[1];
		 			// Trasforma la stringa nel formato corretto per Magento
					$dataFormattata = \DateTime::createFromFormat('Ymd', $dataRicevuta)->format('Y-m-d H:i:s');
					$articolo_rielaborato['news_to_date'] = $dataFormattata;
				}

		 		// cerco weight (PESO)
		 		if ( $dato[0] == 8 ) {
					$articolo_rielaborato['weight'] = (int) $dato[1];
				}

		 		// campo min_qty: Qtà affinchè lo Status dell'Articolo diventi Esaurito nella sezione Inventario (Out-of-Stock Threshold)
		 		if ( $dato[0] == 9 ) {
					$articolo_rielaborato['min_qty'] = (int) $dato[1];
				}
				$articolo_rielaborato['min_sale_qty'] = 1; // la metto sempre a 1

		 		// cerco data_modifica (DATA MODIFICA)
		 		if ( $dato[0] == 10 ) {
		 			$dataRicevuta = $dato[1];
		 			// Trasforma la stringa nel formato corretto per Magento
					$dataFormattata = \DateTime::createFromFormat('Ymd', $dataRicevuta)->format('Y-m-d H:i:s');
					$articolo_rielaborato['data_modifica'] = $dataFormattata;
				}

		 		// cerco WEB
		 		if ( $dato[0] == 11 )
		 			$articolo_rielaborato['WEB'] = $dato[1] == 'S' ? true : false;

		 		// cerco genere (GENERE)
		 		if ( $dato[0] == 12 )
		 			$articolo_rielaborato['genere_mexal'] = trim($dato[1]);

		 		// cerco calibro (CALIBRO)
		 		if ( $dato[0] == 13 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreCalibri();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['calibro'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco altezza (ALTEZZA)
		 		if ( $dato[0] == 14 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreAltezza1();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['altezza'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco composizione_dies (COMPOSIZIONE)
		 		if ( $dato[0] == 15 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreCompdies();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['composizione_dies'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco piombo (PIOMBO)
		 		if ( $dato[0] == 16 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValorePiomboar();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['piombo'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco fondello (FONDELLO)
		 		if ( $dato[0] == 17 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreFondello();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['fondello'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco colpi (COLPI)
		 		if ( $dato[0] == 18 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreColpiarm();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['colpi'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco stelle (STELLE)
		 		if ( $dato[0] == 19 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreStellear();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['stelle'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco litri (LITRI)
		 		if ( $dato[0] == 20 )
		 			$articolo_rielaborato['litri'] = trim($dato[1]);

		 		// cerco diametro_palle (DIAMETRO PALLE)
		 		if ( $dato[0] == 21 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreDiametro();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['diametro_palle'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco profilo (PROFILO)
		 		if ( $dato[0] == 22 )
		 			$articolo_rielaborato['profilo'] = trim($dato[1]);

		 		// cerco nome_palla (NOME PALLA)
		 		if ( $dato[0] == 23 )
		 			$articolo_rielaborato['nome_palla'] = trim($dato[1]);

		 		// cerco taglia (TAGLIA)
		 		if ( $dato[0] == 24 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreTagabbig();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['taglia'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco color (COLORE)
		 		if ( $dato[0] == 25 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreColorear();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['color'] = $array_id_valore[$dataRicevuta];
				}
				
		 		// cerco anno (ANNO)
		 		if ( $dato[0] == 26 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreAnnoarme();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['anno'] = $array_id_valore[$dataRicevuta];
				}
				
		 		// cerco materiale (MATERIALE)
		 		if ( $dato[0] == 27 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreMaterial();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['materiale'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco Profilo palla (PROFILO PALLA)
		 		if ( $dato[0] == 28 )
		 			$articolo_rielaborato['profilo_palla'] = $dato[1];

		 		// cerco Grani (GRANI)
		 		if ( $dato[0] == 29 )
		 			$articolo_rielaborato['grani'] = $dato[1];

		 		// cerco MASTERSKU (MASTERSKU)
		 		if ( $dato[0] == 30 )
		 			$articolo_rielaborato['mastersku'] = $dato[1];

				
		 	}
			array_push($articoliRielaborati, $articolo_rielaborato);
		 }

		 return $articoliRielaborati;
	}


}

