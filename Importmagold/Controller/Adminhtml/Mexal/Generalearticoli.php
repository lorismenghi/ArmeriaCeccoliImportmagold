<?php

namespace LM\Importmagold\Controller\Adminhtml\Mexal;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Generalearticoli extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
		$resultPage = $this->resultPageFactory->create();
		$resultPage->getConfig()->getTitle()->prepend(__('Mexal'));

		// Recupera il block dal layout
		$block = $resultPage->getLayout()->getBlock('lm_importmagold_mexal');

		$articoli = $block->getArrayRispostaGeneraleArticoli();
		$articoli_rielaborati = $block->getArrayRispostaGeneraleArticoliRielaborati();
		$conto_articoli = count($articoli);
		$id_etichetta = $block->getArrayIdEtichettaCampi();

		// Passa i dati al block tramite il layout
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('articoli', $articoli);
		
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('articoli_rielaborati', $articoli_rielaborati);
		/*
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('conto_articoli', $conto_articoli);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('id_etichetta', $id_etichetta);
*/
		return $resultPage;
    }

}
