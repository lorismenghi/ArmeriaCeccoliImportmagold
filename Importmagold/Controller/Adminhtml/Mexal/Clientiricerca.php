<?php

namespace LM\Importmagold\Controller\Adminhtml\Mexal;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Clientiricerca extends Action
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

		$clienti_ricerca = $block->getArrayRispostaClientiRicerca();
		$clienti_ricerca_filtrati = $block->getArrayClientiRicercaFiltrati();
		$conto_clienti = count($clienti_ricerca);

		// Passa i dati al block tramite il layout
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('clienti_ricerca', $clienti_ricerca_filtrati);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('conto_clienti', $conto_clienti);
		//$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('articoli_ricerca_rielaborati', $articoli_ricerca_rielaborati);


		return $resultPage;
    }
}

