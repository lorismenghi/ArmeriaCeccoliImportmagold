<?php

namespace LM\Importmagold\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ClientiImportati extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('clienti_importati', 'id');
    }
}

