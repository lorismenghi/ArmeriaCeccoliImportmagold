<?php
namespace LM\Importmagold\Cron;

use LM\Importmagold\Model\ImportProductService;

class ImportProductCron
{
    protected $importProductService;

    public function __construct(
        ImportProductService $importProductService
    ) {
        $this->importProductService = $importProductService;
    }

    public function execute()
    {
    	for ($i = 1; $i <= 30; $i++) {
        	$this->importProductService->importProduct();
        }
    }
}

