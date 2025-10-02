<?php

namespace LM\Importmagold\Controller\Adminhtml\Mexal;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use LM\Importmagold\Block\Adminhtml\Mexal;

class Rigeneracache extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;


    /**
     * @var Mexal
     */
    protected $mexalBlock;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        RedirectFactory $resultRedirectFactory,
        ManagerInterface $messageManager,
        Mexal $mexalBlock
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->mexalBlock = $mexalBlock;
    }
    
    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // Recupera il block
        $resultPage = $this->resultPageFactory->create();
        $block = $this->mexalBlock;

        // Controlla se il block esiste e cancella i file di cache
        if ($block) {
            //$out = $block->clearCacheFiles();
		     $block->getApiProgressiviArticoliResponse();
		     $block->getApiArticoliRicercaResponse();
		     $block->getApiGeneraleArticoliResponse();
            $block->getApiGeneraleCalibriResponse();
            $block->getApiGeneraleAltezza1Response();
            $block->getApiGeneraleCompdiesResponse();
            $block->getApiGeneralePiomboarResponse();
            $block->getApiGeneraleFondelloResponse();
            $block->getApiGeneraleColpiarmResponse();
            $block->getApiGeneraleStellearResponse();
            $block->getApiGeneraleDiametroResponse();
            $block->getApiGeneraleTagabbigResponse();
            $block->getApiGeneraleColorearResponse();
            $block->getApiGeneraleAnnoarmeResponse();
            $block->getApiGeneraleMaterialResponse();

            $out = "cache mexal rigenrata";
            // Aggiungi un messaggio di successo con l'output
            $this->messageManager->addSuccessMessage(__($out));
        } else {
            // Se il block non è stato trovato, aggiungi un messaggio di errore
            $this->messageManager->addErrorMessage(__('Block not found, unable to regenerate cache.'));
        }

        // Reindirizza alla pagina precedente
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setRefererUrl();
    }
}
