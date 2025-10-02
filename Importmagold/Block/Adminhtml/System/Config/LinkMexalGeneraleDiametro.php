<?php

namespace LM\Importmagold\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

class LinkMexalGeneraleDiametro extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Render button
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $url = $this->getUrl('lm_importmagold/mexal/generalediametro'); // URL del controller
        $html = '<a href="' . $url . '" target="_blank">TEST chiamata generale diametro</a>';
        return $html;
    }
}
?>
