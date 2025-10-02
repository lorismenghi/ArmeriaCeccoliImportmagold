<?php
namespace LM\Importmagold\Cron;

use LM\Importmagold\Model\ImportCustomerService;

class ImportCustomerCron
{
    protected $importCustomerService;

    public function __construct(
        ImportCustomerService $importCustomerService
    ) {
        $this->importCustomerService = $importCustomerService;
    }

    public function execute()
    {
        $this->importCustomerService->importCustomer();
        //$this->importCustomerService->importCustomer();
        //$this->importCustomerService->importCustomer();
    }
}

