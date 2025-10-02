<?php

// NON USATO AL MOMENTO E' SOLO UN ESEMPIO CHE RITORNA UN JSON

namespace LM\Importmagold\Controller\Adminhtml\Report;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class RunReport extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Run report action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        // Esegui qui la tua logica per eseguire il report
        // Ad esempio, leggi dal database e ritorna un JSON

        $result = $this->resultJsonFactory->create();
        $data = ['success' => true, 'message' => 'Report eseguito correttamente'];
        return $result->setData($data);
    }
}

