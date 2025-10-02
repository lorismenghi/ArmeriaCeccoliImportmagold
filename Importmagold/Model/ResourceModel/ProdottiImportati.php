<?php

namespace LM\Importmagold\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ProdottiImportati extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('prodotti_aggiornati', 'id');
    }
}

