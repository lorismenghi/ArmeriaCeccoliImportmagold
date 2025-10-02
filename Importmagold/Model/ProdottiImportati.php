<?php

namespace LM\Importmagold\Model;

use Magento\Framework\Model\AbstractModel;

class ProdottiImportati extends AbstractModel
{
    protected function _construct()
    {
        $this->_init('LM\Importmagold\Model\ResourceModel\ProdottiImportati');
    }
}
