<?php

namespace LM\Importmagold\Model\ResourceModel\ProdottiImportati;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use LM\Importmagold\Model\ProdottiImportati as Model;
use LM\Importmagold\Model\ResourceModel\ProdottiImportati as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'lm_importmagold_prodotti_importati_collection';
    protected $_eventObject = 'prodotti_importati_collection';

    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}

