<?php

namespace LM\Importmagold\Controller\Adminhtml\Report;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface;
use LM\Importmagold\Model\ImportatoreCustomer;
use LM\Importmagold\Model\ImportCustomerService;
use LM\Importmagold\Model\Importatore;
use Magento\Framework\App\Bootstrap;

class ResetimportClienti extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ImportatoreCustomer
     */
    protected $importatoreCustomer;
    
    protected $importCustomerService;

    /**
     * @param Context $context
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Importatore $importatoreCustomer
     */
    public function __construct(
        Context $context,
        RedirectFactory $resultRedirectFactory,
        ManagerInterface $messageManager,
        ScopeConfigInterface $scopeConfig//,
    ) {
        parent::__construct($context);
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
        //$this->importatoreCustomer = $importatoreCustomer;
        $this->importatoreCustomer = new ImportatoreCustomer();
        //$this->importCustomerService = new ImportCustomerService();
    }


    /**
     * Run report action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->importatoreCustomer->resetImportCustomers();

        if ($result['success']) {
            $this->messageManager->addSuccessMessage($result['message']);
        } else {
            $this->messageManager->addErrorMessage($result['message']);
        }


        // Redirigi al controller Index
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/index');
    }
}

