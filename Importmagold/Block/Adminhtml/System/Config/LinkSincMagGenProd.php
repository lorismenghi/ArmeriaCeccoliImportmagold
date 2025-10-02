<?php

namespace LM\Importmagold\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

class LinkSincMagGenProd extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Render button
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $url = $this->getUrl('lm_importmagold/mexal/sincgenprod'); // URL del controller
        $html = '<p>Sincronizza attributi vari prodotto da Mexal<br/><a href="' . $url . '" target="_blank">TEST chiamata sinc gen prod</a></p>';
        return $html;
    }
}
?>
