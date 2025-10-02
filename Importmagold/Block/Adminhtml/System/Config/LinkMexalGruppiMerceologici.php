<?php

namespace LM\Importmagold\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

class LinkMexalGruppiMerceologici extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Render button
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $url = $this->getUrl('lm_importmagold/mexal/gruppimerceologici'); // URL del controller
        $html = '<a href="' . $url . '" target="_blank">TEST chiamata grupppi merceologici</a>';
        return $html;
    }
}
?>
