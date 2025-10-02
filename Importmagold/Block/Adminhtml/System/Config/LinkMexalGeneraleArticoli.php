<?php

namespace LM\Importmagold\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

class LinkMexalGeneraleArticoli extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Render button
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $url = $this->getUrl('lm_importmagold/mexal/generalearticoli'); // URL del controller
        $html = '<p>Qui si hanno solo codice attributi select e vari e se deve essere in vendita. Non ci sono i prezzi e qta<br/><a href="' . $url . '" target="_blank">TEST chiamata generale articoli</a></p>';
        return $html;
    }
}
?>
