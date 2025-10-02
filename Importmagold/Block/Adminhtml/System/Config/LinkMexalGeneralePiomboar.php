<?php

namespace LM\Importmagold\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

class LinkMexalGeneralePiomboar extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Render button
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $url = $this->getUrl('lm_importmagold/mexal/generalepiomboar'); // URL del controller
        $html = '<a href="' . $url . '" target="_blank">TEST chiamata generale piombo</a>';
        return $html;
    }
}
?>
