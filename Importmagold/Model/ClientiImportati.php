<?php

namespace LM\Importmagold\Model;

use Magento\Framework\Model\AbstractModel;

class ClientiImportati extends AbstractModel
{
    protected function _construct()
    {
        $this->_init('LM\Importmagold\Model\ResourceModel\ClientiImportati');
    }
}
