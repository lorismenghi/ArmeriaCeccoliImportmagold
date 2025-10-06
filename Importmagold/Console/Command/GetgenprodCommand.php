<?php
// crea un mega file troppo grosso non uso piu
namespace LM\Importmagold\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use LM\Importmagold\Model\ImportProductMexalServiceCommand;
use Magento\Framework\App\State;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use LM\Importmagold\Block\Adminhtml\MexalCommand as Mexal;

class GetgenprodCommand extends Command
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    
    protected $importProductMexalService;

    public function __construct(
        State $state,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->state = $state;
        $this->scopeConfig = $scopeConfig;
        
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
        
        $this->importProductMexalService = new ImportProductMexalServiceCommand();
        
        parent::__construct();
    }

    /**
     * Configura il comando
     */
    protected function configure()
    {
        $this->setName('lm:importmagold:getgenprod')
            ->setDescription('Scarica e sinc da Mexal attributi prodotto vari per il modulo Importmagold.');
    }

    /**
     * Esegue il comando
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $objectManager = ObjectManager::getInstance();

        // Controlla se la sincronizzazione è abilitata
        $isEnabled = $this->scopeConfig->getValue(
            'mexal_config/general/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) && $this->scopeConfig->getValue(
            'mexal_config/general/enabled_sinc_product_terminale',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($isEnabled) {
            $output->writeln('<info>Inizio chiamate dei prodotti a Mexal...</info>');

            $mexal = $objectManager->get(Mexal::class);
            //$mexal->clearCacheFiles();
            //$generale_articoli_rielaborati = $mexal->getArrayRispostaGeneraleArticoliRielaborati($output);

			$primo_giro = true;
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
			//$isResuming = file_exists($cacheFile) && file_exists($flagFilePath);
			
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
				    $output->writeln('<info>Inizio chiamata mexal con next:'.$next.'</info>');
				    
					// Verifica se il file esiste già (e quindi usa la cache)
					if (file_exists($cacheFile) && !file_exists($flagFilePath)) {
						$response = file_get_contents($cacheFile);
						$output->writeln('<info>Utilizzo la cache</info>');
					} elseif (file_exists($cacheFile) && file_exists($flagFilePath) && $primo_giro ) {
						// riprende da dove si era interrotto (nel caso sia stato stoppato)
						$response = file_get_contents($cacheFile);
						$output->writeln('<info>Utilizzo la cache</info>');
					} else {
						$output->writeln('<info>faccio chiamata mexal con next:'.$next.'</info>');
						// Effettua la chiamata API utilizzando il metodo esistente
						$response = $mexal->getApiGeneraleArticoliCall($next);
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
				    
				    file_put_contents($cacheFile, json_encode($array_response, JSON_PRETTY_PRINT)); // se voglio sia formattato per essere letto anche manualmente
				    //file_put_contents($cacheFile, json_encode($array_response)); // crea json tutto appiccicato

				    // Controlla se esiste un ID_NEXT per proseguire con la paginazione
				    $hasNext = isset($data['next']) && $data['next'];
				    if ($hasNext) {
				        $next = $data['next'];
				        $output->writeln('<info>E stato trovato il next:'.$next.'</info>');
				        file_put_contents($flagFilePath, $next);
				    } else {
				    	$output->writeln('<info>Cancella il file flag quando tutte le pagine sono state elaborate</info>');
				    	
				        // Cancella il file flag quando tutte le pagine sono state elaborate
				        if (file_exists($flagFilePath)) {
				            unlink($flagFilePath);
				        }
				    }
				    
				    // qui devo lanciare lettura del file salvato
				    $array_risposta = $mexal->getArrayRispostaGeneraleArticoli($output, file_get_contents($cacheFile));
				    $generale_articoli_rielaborati = $mexal->getArrayRispostaGeneraleArticoliRielaborati($output, $array_risposta);
				    // ora effettuo ciclo importazione
				    $n = $this->importProductMexalService->ng;
					$this->importProductMexalService = new ImportProductMexalServiceCommand();
					$this->importProductMexalService->ng = $n;
					$this->importProductMexalService->importProductGenerale($output, $generale_articoli_rielaborati);
					$primo_giro = false;
					$array_response = ['dati'=>[]];
					//$isResuming = false;
				    
				} // fine while
				
			} finally {
				        // Cancella il file flag quando tutte le pagine sono state elaborate
				        if (file_exists($flagFilePath)) {
				            unlink($flagFilePath);
				        }
			}
			
            $output->writeln('<info>Chiamate Mexal utimate con successo.</info>');
            $output->writeln('<comment>Scaricati un totale di '.count($generale_articoli_rielaborati).' articoli</comment>');
        } else {
            $output->writeln('<error>Sincronizzazione disabilitata. Abilita il modulo nelle configurazioni di sistema.</error>');
        }

        return 0; // Exit code
    }
}

