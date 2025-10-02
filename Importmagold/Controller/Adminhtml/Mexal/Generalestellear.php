<?php

namespace LM\Importmagold\Controller\Adminhtml\Mexal;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Generalestellear extends Action
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
		$array_id_valore = $block->getArrayIdValoreStellear();
		$conto_opzioni = count($array_id_valore);
		$etichetta_stellear = $block->getEtichettaStellear();
		

		// Passa i dati al block tramite il layout
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('conto_opzioni', $conto_opzioni);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('etichetta_stellear', $etichetta_stellear);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('array_id_valore', $array_id_valore);

		return $resultPage;
    }

}
