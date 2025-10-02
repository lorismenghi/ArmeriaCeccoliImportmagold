<?php
namespace LM\Importmagold\Cron;

//use LM\Importmagold\Model\ImportProductService;
use LM\Importmagold\Block\Adminhtml\Mexal;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ObjectManager;

class clearMexalImportProductCron
{
	protected $mexal;
	protected $logger;
	
    public function __construct()
    {
        $objectManager = ObjectManager::getInstance();
        $this->mexal = $objectManager->get(Mexal::class);
        $this->logger = $objectManager->get(LoggerInterface::class);
    }

    public function execute()
    {
    	//echo 'START';
    	$this->logger->info("clearMexalImportProductCron eseguito");
        $this->mexal->clearCacheFiles();
    }
}

