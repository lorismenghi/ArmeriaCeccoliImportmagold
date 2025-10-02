<?php

namespace LM\Importmagold\Controller\Adminhtml\Report;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface;
use LM\Importmagold\Model\ImportatoreProduct;
use LM\Importmagold\Model\ImportProductService;
use LM\Importmagold\Model\Importatore;
use Magento\Framework\App\Bootstrap;

class ImportProdotto extends Action
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
     * @var ImportatoreProduct
     */
    protected $importatoreProduct; // non e' usato
    
    protected $importProductService;

    /**
     * @param Context $context
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        RedirectFactory $resultRedirectFactory,
        ManagerInterface $messageManager,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
        //$this->importatoreCustomer = $importatoreCustomer;
        //$this->importatoreCustomer = new ImportatoreCustomer();
        $this->importProductService = new ImportProductService();
    }


    /**
     * Run report action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
    	for ($i = 1; $i <= 1; $i++) {
        	$result = $this->importProductService->importProduct();
        }

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

