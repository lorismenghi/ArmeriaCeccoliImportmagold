<?php
namespace LM\Importmagold\Cron;

use LM\Importmagold\Block\Adminhtml\Mexal;
use Magento\Framework\App\ObjectManager;

class rigeneraCacheMexalImportProductCron
{
	protected $mexal;
	
    public function __construct() {
    	$objectManager = ObjectManager::getInstance();
        $this->mexal = $objectManager->get(Mexal::class);
    }

    public function execute()
    {
		// Imposta il limite di tempo su infinito per evitare timeout
		set_time_limit(900);

		// Imposta un limite di memoria più alto per gestire eventuali richieste pesanti
		ini_set('memory_limit', '2G');

		//$this->mexal->clearCacheFiles();
        $this->mexal->getApiProgressiviArticoliResponse();
        $this->mexal->getApiArticoliRicercaResponse();
        $this->mexal->getApiGeneraleArticoliResponse(); // questa chiamata e' talmente lenta che blocca tutto se la mettiamo insieme alle altre
        $this->mexal->getApiGeneraleCalibriResponse();
        $this->mexal->getApiGeneraleAltezza1Response();
        $this->mexal->getApiGeneraleCompdiesResponse();
        $this->mexal->getApiGeneralePiomboarResponse();
        $this->mexal->getApiGeneraleFondelloResponse();
        $this->mexal->getApiGeneraleColpiarmResponse();
        $this->mexal->getApiGeneraleStellearResponse();
        $this->mexal->getApiGeneraleDiametroResponse();
        $this->mexal->getApiGeneraleTagabbigResponse();
        $this->mexal->getApiGeneraleColorearResponse();
        $this->mexal->getApiGeneraleAnnoarmeResponse();
        $this->mexal->getApiGeneraleMaterialResponse();
        return true;
    }

    public function generaProgressiviArticoli()
    {
        return $this->mexal->getApiProgressiviArticoliResponse();
    }

    public function generaArticoliRicerca()
    {
        return $this->mexal->getApiArticoliRicercaResponse();
    }

    public function generaGeneraleArticoli()
    {
		return $this->mexal->getApiGeneraleArticoliResponse();
    }

    public function generaSelectArticoli()
    {
		// Imposta il limite di tempo su infinito per evitare timeout
		set_time_limit(600);

		$this->mexal->getApiGeneraleCalibriResponse();
        $this->mexal->getApiGeneraleAltezza1Response();
        $this->mexal->getApiGeneraleCompdiesResponse();
        $this->mexal->getApiGeneralePiomboarResponse();
        $this->mexal->getApiGeneraleFondelloResponse();
        $this->mexal->getApiGeneraleColpiarmResponse();
        $this->mexal->getApiGeneraleStellearResponse();
        $this->mexal->getApiGeneraleDiametroResponse();
        $this->mexal->getApiGeneraleTagabbigResponse();
        $this->mexal->getApiGeneraleColorearResponse();
        $this->mexal->getApiGeneraleAnnoarmeResponse();
        $this->mexal->getApiGeneraleMaterialResponse();
        return true;
    }


}

