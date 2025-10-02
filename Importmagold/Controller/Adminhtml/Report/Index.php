<?php

namespace LM\Importmagold\Controller\Adminhtml\Report;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use LM\Importmagold\Model\ResourceModel\ClientiImportati\CollectionFactory;
use LM\Importmagold\Model\ResourceModel\ProdottiImportati\CollectionFactory as ProdottiCollectionFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    protected $collectionFactory;
    protected $prodottiCollectionFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CollectionFactory $collectionFactory,
        ProdottiCollectionFactory $prodottiCollectionFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->collectionFactory = $collectionFactory;
        $this->prodottiCollectionFactory = $prodottiCollectionFactory;
    }

    /**
     * Report page
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
    		// clienti
        $collection = $this->collectionFactory->create();
        $collection->setOrder('id', 'DESC');
        $collection->setPageSize(10);

        $aggiornataCount = $collection->addFieldToFilter('aggiornata', 1)->getSize();

        $resultPage = $this->resultPageFactory->create();
        //$resultPage->getConfig()->getTitle()->prepend(__('Clienti Importati Report'));

        $block = $resultPage->getLayout()->getBlock('lm_importmagold_report');
        $block->setData('collection', $collection);
        $block->setData('aggiornataCount', $aggiornataCount);


				// prodotti
        $prodotti_collection = $this->prodottiCollectionFactory->create();
        $prodotti_collection->setOrder('id', 'DESC');
        $prodotti_collection->setPageSize(10);

        $prodottiAggiornataCount = $prodotti_collection->addFieldToFilter('aggiornata', 1)->getSize();

        //$resultPage = $this->resultPageFactory->create();

        //$block = $resultPage->getLayout()->getBlock('lm_importmagold_report');
        $block->setData('prodotti_collection', $prodotti_collection);
        $block->setData('prodottiAggiornataCount', $prodottiAggiornataCount);

        return $resultPage;
    }
}

