<?php

namespace LM\Importmagold\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use LM\Importmagold\Model\ImportProductMexalService;
use Magento\Framework\App\State;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use LM\Importmagold\Block\Adminhtml\MexalCommand as Mexal;

class ClearprzqtaCommand extends Command
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        State $state,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->state = $state;
        $this->scopeConfig = $scopeConfig;
        parent::__construct();
    }

    /**
     * Configura il comando
     */
    protected function configure()
    {
        $this->setName('lm:importmagold:clearprzqta')
            ->setDescription('Scarica da Mexal prezzi e quantità per il modulo Importmagold preparando il file di import.');
    }

    /**
     * Esegue il comando
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode('adminhtml'); // Imposta l'area di esecuzione per evitare errori
        } catch (\Exception $e) {
            // L'area potrebbe essere già impostata
        }

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
            $output->writeln('<info>Inizio pulizia cache Mexal...</info>');

            $mexal = $objectManager->get(Mexal::class);
            $mexal->clearCacheFiles($output);


            $output->writeln('<info>Pulizia cache Mexal utimata con successo.</info>');
        } else {
            $output->writeln('<error>Sincronizzazione disabilitata. Abilita il modulo nelle configurazioni di sistema.</error>');
        }

        return 0; // Exit code
    }
}

