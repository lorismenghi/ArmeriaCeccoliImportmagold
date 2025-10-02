<?php

namespace LM\Importmagold\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

class LinkSincMagPrzQta extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Render button
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $url = $this->getUrl('lm_importmagold/mexal/sincprzqta'); // URL del controller
        $html = '<p>Sincronizza prz qta da Mexal<br/><a href="' . $url . '" target="_blank">TEST chiamata sinc prz qta</a></p>';
        return $html;
    }
}
?>
