<?php

namespace LM\Importmagold\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

class LinkInvioOrdine extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Render button
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $url = $this->getUrl('lm_importmagold/mexal/invioordine'); // URL del controller
        $html = '<p>/invia Ordine a Mexal<br/><a href="' . $url . '" target="_blank">TEST invio Ordine</a></p>';
        return $html;
    }
}
?>
