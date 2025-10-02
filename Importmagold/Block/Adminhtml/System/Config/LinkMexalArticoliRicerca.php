<?php

namespace LM\Importmagold\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

class LinkMexalArticoliRicerca extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Render button
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $url = $this->getUrl('lm_importmagold/mexal/articoliricerca'); // URL del controller
        $html = '<p>Qui ci sono solo prezzi e qta.<br/><a href="' . $url . '" target="_blank">TEST chiamata articoli ricerca</a></p>';
        return $html;
    }
}
?>
