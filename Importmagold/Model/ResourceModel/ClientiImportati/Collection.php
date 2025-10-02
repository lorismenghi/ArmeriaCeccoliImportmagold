<?php

namespace LM\Importmagold\Model\ResourceModel\ClientiImportati;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use LM\Importmagold\Model\ClientiImportati as Model;
use LM\Importmagold\Model\ResourceModel\ClientiImportati as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'lm_importmagold_clienti_importati_collection';
    protected $_eventObject = 'clienti_importati_collection';

    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}

