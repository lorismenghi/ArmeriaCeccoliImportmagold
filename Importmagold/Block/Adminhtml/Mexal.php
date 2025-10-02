<?php

namespace LM\Importmagold\Block\Adminhtml;

use LM\Importmagold\Model\ImportatoreProduct;
use Magento\Store\Model\ScopeInterface;

class Mexal extends MexalBase
{
	public $etichetta_calibri = null; // etichetta generale dell'attributo select
	public $array_id_valore_calibri = [];

	public $etichetta_altezza1 = null; // etichetta generale dell'attributo select
	public $array_id_valore_altezza1 = [];

	public $etichetta_compdies = null; // etichetta generale dell'attributo select
	public $array_id_valore_compdies = [];

	public $etichetta_piomboar = null; // etichetta generale dell'attributo select
	public $array_id_valore_piombar = [];

	public $etichetta_stellear = null; // etichetta generale dell'attributo select
	public $array_id_valore_stellear = [];

	public $etichetta_diametro = null; // etichetta generale dell'attributo select
	public $array_id_valore_diametro = [];

	public $etichetta_tagabbig = null; // etichetta generale dell'attributo select
	public $array_id_valore_tagabbig = [];

	public $etichetta_colorear = null; // etichetta generale dell'attributo select
	public $array_id_valore_colorear = [];

	public $etichetta_annoarme = null; // etichetta generale dell'attributo select
	public $array_id_valore_annoarme = [];

	public $etichetta_material = null; // etichetta generale dell'attributo select
	public $array_id_valore_material = [];
	
	public $array_codice_gruppo_merceologico = null; // contiene tutte le categorie ma come chiave associativa array ha il codice 

    public $codice_qta = []; // array associatvo codice => qta
    public $id_codice = []; // array id => codice
    public $id_etichetta_campo = []; // array id => nome attributo campo

	protected $array_risposta_progressivi_articoli = null;
	protected $array_risposta_articoli_ricerca = null;
	protected $array_risposta_clienti_ricerca = null;
	protected $array_risposta_generale_articoli = null;
	protected $array_risposta_generale_articoli_rielaborati = null;
	protected $array_risposta_generale_calibri = null;
	protected $array_risposta_generale_altezza1 = null;
	protected $array_risposta_generale_compdies = null;
	protected $array_risposta_generale_piomboar = null;
	protected $array_risposta_generale_fondello = null;
	protected $array_risposta_generale_colpiarm = null;
	protected $array_risposta_generale_stellear = null;
	protected $array_risposta_generale_diametro = null;
	protected $array_risposta_generale_tagabbig = null;
	protected $array_risposta_generale_colorear = null;
	protected $array_risposta_generale_annoarme = null;
	protected $array_risposta_generale_material = null;
	protected $array_risposta_gruppi_merceologici = null;
	
	public function getArrayRispostaProgressiviArticoli()
	{
		if ( is_null($this->array_risposta_progressivi_articoli) ) {
			$response = $this->getApiProgressiviArticoliResponse();
			$this->array_risposta_progressivi_articoli = json_decode($response, true);
		}
		return $this->array_risposta_progressivi_articoli;
	}
	
	// totalmente 
	public function getArrayCodiceQta()
	{
		//return []; // bo non ricordo piu come funzionava
		$this->codice_qta = []; // array associatvo codice => qta
		$this->id_codice = [];
		$this->id_qta_inventario = [];
		$this->id_qta_carico = [];
		$this->id_qta_scarico = [];
		$this->id_qta_ord_dimp = [];
		$this->id_qta_ord_imp = [];
		$this->id_qta_finale = [];
		$progressivi_articoli = $this->getArrayRispostaArticoliRicercaRielaborati();

		foreach ($progressivi_articoli['qta_inventario'] as $item) {
			 $this->id_qta_inventario[$item[0]] = $item[1];
		}
		foreach ($progressivi_articoli['qta_carico'] as $item) {
			 $this->id_qta_carico[$item[0]] = $item[1];
		}
		foreach ($progressivi_articoli['qta_scarico'] as $item) {
			 $this->id_qta_scarico[$item[0]] = $item[1];
		}
		foreach ($progressivi_articoli['qta_ord_dimp'] as $item) {
			 $this->id_qta_ord_dimp[$item[0]] = $item[1];
		}
		foreach ($progressivi_articoli['qta_ord_imp'] as $item) {
			 $this->id_qta_ord_imp[$item[0]] = $item[1];
		}
		foreach ($progressivi_articoli['cod_articolo'] as $item) {
			 $this->id_codice[$item[0]] = $item[1]; // $item[0] è l'id, $item[1] è il codice
		}
		foreach ($this->id_codice as $id => $codice) {
			$qta = 0;
			
			// inventario + carico - scarico
			if ( array_key_exists($id, $this->id_qta_inventario) )
				$qta += $this->id_qta_inventario[$id];
			if ( array_key_exists($id, $this->id_qta_carico) )
				$qta += $this->id_qta_carico[$id];
			if ( array_key_exists($id, $this->id_qta_scarico) )
				$qta -= $this->id_qta_scarico[$id];
				
			// inventario + carico - scarico -

			/*
			//$this->codice_qta[]
			if ( array_key_exists($id, $this->id_qta_carico) )
				$qta += $this->id_qta_carico[$id];
			if ( array_key_exists($id, $this->id_qta_scarico) )
				$qta -= $this->id_qta_scarico[$id];
			if ( array_key_exists($id, $this->id_qta_ord_dimp) )
				$qta -= $this->id_qta_ord_dimp[$id];
			if ( array_key_exists($id, $this->id_qta_ord_imp) )
				$qta -= $this->id_qta_ord_imp[$id];
			*/
			$this->id_qta_finale[$id] = $qta;
			$this->codice_qta["$codice"] = $qta;
		}
		return $this->codice_qta;
	}

	// interpola 2 chiamate API in modo da avere sia il prezzo che la qta
	public function getArrayRispostaArticoliRicercaRielaborati($output_terminale = null, $array_risposta_articoli_ricerca = null)
	{
		 // Ottieni la risposta completa degli articoli
		 $articoli = $this->getArrayRispostaArticoliRicerca($output_terminale);
		 // Ottieni la quantità per ciascun codice articolo
		 //$codiciQta = $this->getArrayCodiceQta();
		 
		 $this->array_codice_gruppo_merceologico = $this->getArrayRispostaGruppiMerceologiciRielaborati();

		 // Definisci i campi utili che desideri mantenere
		 $articoliRielaborati = [];

		 foreach ($articoli as $articolo) {

		     // Estrai i prezzi di listino e prezzo speciale
		     $prz_listino = null;
		     $prz_speciale = null;
		     foreach ($articolo['prz_listino'] as $prz) {
		         if ($prz[0] == 1) {
		             $prz_listino = $prz[1];
		         }
		         if ($prz[0] == 6) {
		             $prz_speciale = $prz[1];
		         }
		     }

		     // Estrai la quantità dall'array codiciQta
		     //$qta = isset($codiciQta[$articolo['codice']]) ? $codiciQta[$articolo['codice']] : 0;
		     $qta_ord_dimp = array_key_exists('qta_ord_dimp', $articolo) ? $articolo['qta_ord_dimp'] : 0;
		     $qta_ord_imp = array_key_exists('qta_ord_imp', $articolo) ? $articolo['qta_ord_imp'] : 0;
		     $qta = $articolo['qta_inventario'] + $articolo['qta_carico'] - $articolo['qta_scarico'] - $qta_ord_dimp - $qta_ord_imp;
			
			$nome_categoria = ''; $codice_categoria_parent = ''; $nome_categoria_parent = '';
			$cod_grp_merc = trim($articolo['cod_grp_merc']); //print_r($this->array_codice_gruppo_merceologico);
			if ( strlen($cod_grp_merc) && array_key_exists($cod_grp_merc, $this->array_codice_gruppo_merceologico) ) {
				$nome_categoria = $this->array_codice_gruppo_merceologico[$cod_grp_merc]['descrizione'];
				$codice_categoria_parent = $this->array_codice_gruppo_merceologico[$cod_grp_merc]['cod_grp_merc']; // nella chiamata categorie cod_grp_merc è ilcodice della parent
			}
			if ( strlen($codice_categoria_parent) && array_key_exists($codice_categoria_parent, $this->array_codice_gruppo_merceologico) ) {
				$nome_categoria_parent = $this->array_codice_gruppo_merceologico[$codice_categoria_parent]['descrizione'];
			}
			
		     // Aggiungi i campi filtrati all'array
		     $articoliRielaborati[] = [
		         'codice' => $articolo['codice'], // SKU del prodotto
		         //'descrizione' => $articolo['descrizione'] . $articolo['descrizione_agg'], // Nome prodotto
		         //'descrizione' => $articolo['descrizione'],
		         'descrizione' => $articolo['descr_completa'],
		         'cod_grp_merc' => $cod_grp_merc, // Codice gruppo merceologico / categoria
		         'nome_categoria' => $nome_categoria,
		         'codice_categoria' => $cod_grp_merc, // nel prodotto cod_grp_merc è la categoria, non la parent
		         'codice_categoria_parent' => $codice_categoria_parent,
		         'nome_categoria_parent' => $nome_categoria_parent,
		         'prz_listino' => $prz_listino, // Prezzo di vendita (posizione 1)
		         'prz_speciale' => $prz_speciale, // Prezzo speciale (posizione 6, se presente)
		         'qta_inventario' => $articolo['qta_inventario'],
		         'qta_carico' => $articolo['qta_carico'],
				 'qta_scarico' => $articolo['qta_scarico'],
				 'qta_ord_dimp' => $qta_ord_dimp, // non esistono piu
				 'qta_ord_imp' => $qta_ord_imp,
		         'qta' => $qta // Quantità per articolo
		     ];
		 }

		 return $articoliRielaborati;
	}


	public function getArrayRispostaArticoliRicerca($output_terminale = null, $json_risposta = null)
	{
		if ( is_null($json_risposta) ) {
			$response = $this->getApiArticoliRicercaResponse($output_terminale);
			$array_response = json_decode($response, true);
			$this->array_risposta_articoli_ricerca = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		} else {
			$response = $json_risposta;
			$array_response = json_decode($response, true);
			$this->array_risposta_articoli_ricerca = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		return $this->array_risposta_articoli_ricerca;
	}

	public function getArrayRispostaGeneraleArticoliRielaborati($output_terminale = null)
	{
		 // Ottieni la risposta completa degli articoli
		 $articoli = $this->getArrayRispostaGeneraleArticoli($output_terminale);
		 // Ottieni la quantità per ciascun codice articolo
		 $codiciQta = $this->getArrayCodiceQta();

		 // Definisci i campi utili che desideri mantenere
		 $articoliRielaborati = [];

		 foreach ($articoli as $articolo) {
		 	$dati_campi_prodotto = $articolo['dati_campi'];
		 	$articolo_rielaborato = [];
		 	
		 	foreach ($dati_campi_prodotto as $dato) {
		 		// cerco lo sku
		 		if ( $dato[0] == 1 )
		 			$articolo_rielaborato['sku'] = $dato[1];

		 		// cerco lo status (ABILITATO)
		 		if ( $dato[0] == 2 )
		 			$articolo_rielaborato['status'] = $dato[1] == 'S' ? ImportatoreProduct::MAGENTO_STATUS_ENABLED : ImportatoreProduct::MAGENTO_STATUS_DISABLED;

		 		// cerco VENDITA che non uso al momento
		 		if ( $dato[0] == 3 )
		 			$articolo_rielaborato['VENDITA'] = $dato[1] == 'S' ? true : false;

		 		// cerco name (NOME)
		 		if ( $dato[0] == 4 )
		 			$articolo_rielaborato['name'] = $dato[1];

		 		// cerco short_description (DESCRIZIONE BREVE)
		 		if ( $dato[0] == 5 )
		 			$articolo_rielaborato['short_description'] = $dato[1];

		 		// cerco news_from_date (PERIODO VETRINA DA)
		 		if ( $dato[0] == 6 ) {
		 			$dataRicevuta = $dato[1];
		 			// Trasforma la stringa nel formato corretto per Magento
					$dataFormattata = \DateTime::createFromFormat('Ymd', $dataRicevuta)->format('Y-m-d H:i:s');
					$articolo_rielaborato['news_from_date'] = $dataFormattata;
				}

		 		// cerco news_from_date (PERIODO VETRINA A)
		 		if ( $dato[0] == 7 ) {
		 			$dataRicevuta = $dato[1];
		 			// Trasforma la stringa nel formato corretto per Magento
					$dataFormattata = \DateTime::createFromFormat('Ymd', $dataRicevuta)->format('Y-m-d H:i:s');
					$articolo_rielaborato['news_to_date'] = $dataFormattata;
				}

		 		// cerco weight (PESO)
		 		if ( $dato[0] == 8 ) {
					$articolo_rielaborato['weight'] = (int) $dato[1];
				}

				// non sono sicuro che intendano questo
		 		// cerco min_sale_qty (QUNATITA MINIMA)
		 		if ( $dato[0] == 9 ) {
					$articolo_rielaborato['min_sale_qty'] = (int) $dato[1];
				}

		 		// cerco data_modifica (DATA MODIFICA)
		 		if ( $dato[0] == 10 ) {
		 			$dataRicevuta = $dato[1];
		 			// Trasforma la stringa nel formato corretto per Magento
					$dataFormattata = \DateTime::createFromFormat('Ymd', $dataRicevuta)->format('Y-m-d H:i:s');
					$articolo_rielaborato['data_modifica'] = $dataFormattata;
				}

		 		// cerco WEB
		 		if ( $dato[0] == 11 )
		 			$articolo_rielaborato['WEB'] = $dato[1] == 'S' ? true : false;

		 		// cerco genere (GENERE)
		 		if ( $dato[0] == 12 )
		 			$articolo_rielaborato['genere'] = trim($dato[1]);

		 		// cerco calibro (CALIBRO)
		 		if ( $dato[0] == 13 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreCalibri();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['calibro'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco altezza (ALTEZZA)
		 		if ( $dato[0] == 14 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreAltezza1();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['altezza'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco composizione_dies (COMPOSIZIONE)
		 		if ( $dato[0] == 15 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreCompdies();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['composizione_dies'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco piombo (PIOMBO)
		 		if ( $dato[0] == 16 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValorePiomboar();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['piombo'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco fondello (FONDELLO)
		 		if ( $dato[0] == 17 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreFondello();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['fondello'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco colpi (COLPI)
		 		if ( $dato[0] == 18 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreColpiarm();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['colpi'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco stelle (STELLE)
		 		if ( $dato[0] == 19 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreStellear();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['stelle'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco litri (LITRI)
		 		if ( $dato[0] == 20 )
		 			$articolo_rielaborato['litri'] = trim($dato[1]);

		 		// cerco diametro_palle (DIAMETRO PALLE)
		 		if ( $dato[0] == 21 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreDiametro();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['diametro_palle'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco profilo (PROFILO)
		 		if ( $dato[0] == 22 )
		 			$articolo_rielaborato['profilo'] = trim($dato[1]);

		 		// cerco nome_palla (NOME PALLA)
		 		if ( $dato[0] == 23 )
		 			$articolo_rielaborato['nome_palla'] = trim($dato[1]);

		 		// cerco taglia (TAGLIA)
		 		if ( $dato[0] == 24 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreTagabbig();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['taglia'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco color (COLORE)
		 		if ( $dato[0] == 25 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreColorear();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['color'] = $array_id_valore[$dataRicevuta];
				}
				
		 		// cerco anno (ANNO)
		 		if ( $dato[0] == 26 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreAnnoarme();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['anno'] = $array_id_valore[$dataRicevuta];
				}
				
		 		// cerco materiale (MATERIALE)
		 		if ( $dato[0] == 27 ) {
		 			$dataRicevuta = (int) $dato[1];
		 			$array_id_valore = $this->getArrayIdValoreMaterial();
		 			if ( array_key_exists($dataRicevuta, $array_id_valore) )
		 				$articolo_rielaborato['materiale'] = $array_id_valore[$dataRicevuta];
				}

		 		// cerco Profilo palla (PROFILO PALLA)
		 		if ( $dato[0] == 28 )
		 			$articolo_rielaborato['profilo_palla'] = $dato[1];

		 		// cerco Grani (GRANI)
		 		if ( $dato[0] == 29 )
		 			$articolo_rielaborato['grani'] = $dato[1];

		 		// cerco MASTERSKU (MASTERSKU)
		 		if ( $dato[0] == 30 )
		 			$articolo_rielaborato['mastersku'] = $dato[1];

				
		 	}
			array_push($articoliRielaborati, $articolo_rielaborato);
		 }

		 return $articoliRielaborati;
	}


	public function getArrayRispostaGeneraleArticoli($output_terminale = null, $json_risposta = null)
	{
		if ( is_null($json_risposta) ) {
			$response = $this->getApiGeneraleArticoliResponse($output_terminale);
			$array_response = json_decode($response, true);
			$this->array_risposta_generale_articoli = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		} else {
			$response = $json_risposta;
			$array_response = json_decode($response, true);
			$this->array_risposta_generale_articoli = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		return $this->array_risposta_generale_articoli;
	}

	// array id -> nome attributo
	public function getArrayIdEtichettaCampi()
	{
		if ( is_null($this->array_risposta_generale_articoli) ) {
			$response = $this->getApiGeneraleArticoliResponse();
			$array_response = json_decode($response, true);
			$this->array_risposta_generale_articoli = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		if ( !count($this->id_etichetta_campo) ) {
			if ( count($this->array_risposta_generale_articoli) ) {
				$etichette_campi = array_key_exists('etichette_campi', current($this->array_risposta_generale_articoli)) ? current($this->array_risposta_generale_articoli)['etichette_campi'] : [];
				foreach ($etichette_campi as $campo) {
					$this->id_etichetta_campo[$campo[0]] = $campo[1];
				}
			}
		}
		return $this->id_etichetta_campo;
	}


	public function getEtichettaCalibri() {
		if ( !strlen($this->etichetta_calibri) )
			$this->getArrayRispostaGeneraleCalibri();
		return $this->etichetta_calibri;
	}
	
	public function getArrayIdValoreCalibri() {
		$dati = $this->getArrayRispostaGeneraleCalibri();
		foreach ($dati as $dato) {
			$valore = '';
			if ( array_key_exists(1, $dato['dati_campi']) && array_key_exists(1, $dato['dati_campi'][1]) )
				$valore = $dato['dati_campi'][1][1];
			$this->array_id_valore_calibri[$dato['id']] = $valore;
		}
		return $this->array_id_valore_calibri;
	}

	public function getArrayRispostaGeneraleCalibri()
	{
		if ( is_null($this->array_risposta_generale_calibri) ) {
			$response = $this->getApiGeneraleCalibriResponse();
			$array_response = json_decode($response, true);
			$this->array_risposta_generale_calibri = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		$this->etichetta_calibri = count($this->array_risposta_generale_calibri) && array_key_exists('etichette_campi', $this->array_risposta_generale_calibri[0]) ? $this->array_risposta_generale_calibri[0]['etichette_campi'][1][1] : ''; // etichetta generale dell'attributo select
		return $this->array_risposta_generale_calibri;
	}
	

	public function getEtichettaAltezza1() {
		if ( !strlen($this->etichetta_altezza1) )
			$this->getArrayRispostaGeneraleAltezza1();
		return $this->etichetta_altezza1;
	}

	public function getArrayIdValoreAltezza1() {
		$dati = $this->getArrayRispostaGeneraleAltezza1();
		foreach ($dati as $dato) {
			$valore = '';
			if ( array_key_exists(1, $dato['dati_campi']) && array_key_exists(1, $dato['dati_campi'][1]) )
				$valore = $dato['dati_campi'][1][1];
			$this->array_id_valore_altezza1[$dato['id']] = $valore;
		}
		return $this->array_id_valore_altezza1;
	}

	public function getArrayRispostaGeneraleAltezza1()
	{
		if ( is_null($this->array_risposta_generale_altezza1) ) {
			$response = $this->getApiGeneraleAltezza1Response();
			$array_response = json_decode($response, true);
			$this->array_risposta_generale_altezza1 = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		$this->etichetta_altezza1 = count($this->array_risposta_generale_altezza1) && array_key_exists('etichette_campi', $this->array_risposta_generale_altezza1[0]) ? $this->array_risposta_generale_altezza1[0]['etichette_campi'][1][1] : ''; // etichetta generale dell'attributo select
		return $this->array_risposta_generale_altezza1;
	}
	
	public function getEtichettaCompdies() {
		if ( !strlen($this->etichetta_compdies) )
			$this->getArrayRispostaGeneraleCompdies();
		return $this->etichetta_compdies;
	}
	
	public function getArrayIdValoreCompdies() {
		$dati = $this->getArrayRispostaGeneraleCompdies();
		//echo '<pre>'; print_r($dati); echo '</pre>';
		foreach ($dati as $dato) {
			//echo "#######################################".$dato['id']."#";
			//echo '<pre>'; print_r($dato['dati_campi'][1][1]); echo '</pre>';
			$valore = '';
			if ( array_key_exists(1, $dato['dati_campi']) && array_key_exists(1, $dato['dati_campi'][1]) )
				$valore = $dato['dati_campi'][1][1];
			$this->array_id_valore_compdies[$dato['id']] = $valore;
		}
		return $this->array_id_valore_compdies;
	}

	public function getArrayRispostaGeneraleCompdies()
	{
		if ( is_null($this->array_risposta_generale_compdies) ) {
			$response = $this->getApiGeneraleCompdiesResponse();
			$array_response = json_decode($response, true);
			$this->array_risposta_generale_compdies = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		$this->etichetta_compdies = count($this->array_risposta_generale_compdies) && array_key_exists('etichette_campi', $this->array_risposta_generale_compdies[0]) ? $this->array_risposta_generale_compdies[0]['etichette_campi'][1][1] : ''; // etichetta generale dell'attributo select
		return $this->array_risposta_generale_compdies;
	}

	public function getEtichettaPiomboar() {
		if ( !strlen($this->etichetta_piomboar) )
			$this->getArrayRispostaGeneralePiomboar();
		return $this->etichetta_piomboar;
	}
	
	public function getArrayIdValorePiomboar() {
		$dati = $this->getArrayRispostaGeneralePiomboar();
		foreach ($dati as $dato) {
			$valore = '';
			if ( array_key_exists(1, $dato['dati_campi']) && array_key_exists(1, $dato['dati_campi'][1]) )
				$valore = $dato['dati_campi'][1][1];
			$this->array_id_valore_piomboar[$dato['id']] = $valore;
		}
		return $this->array_id_valore_piomboar;
	}

	public function getArrayRispostaGeneralePiomboar()
	{
		if ( is_null($this->array_risposta_generale_piomboar) ) {
			$response = $this->getApiGeneralePiomboarResponse();
			$array_response = json_decode($response, true);
			$this->array_risposta_generale_piomboar = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		$this->etichetta_piomboar = count($this->array_risposta_generale_piomboar) && array_key_exists('etichette_campi', $this->array_risposta_generale_piomboar[0]) ? $this->array_risposta_generale_piomboar[0]['etichette_campi'][1][1] : ''; // etichetta generale dell'attributo select
		return $this->array_risposta_generale_piomboar;
	}

	public function getEtichettaFondello() {
		if ( !strlen($this->etichetta_fondello) )
			$this->getArrayRispostaGeneraleFondello();
		return $this->etichetta_fondello;
	}
	
	public function getArrayIdValoreFondello() {
		$dati = $this->getArrayRispostaGeneraleFondello();
		foreach ($dati as $dato) {
			$valore = '';
			if ( array_key_exists(1, $dato['dati_campi']) && array_key_exists(1, $dato['dati_campi'][1]) )
				$valore = $dato['dati_campi'][1][1];
			$this->array_id_valore_fondello[$dato['id']] = $valore;
		}
		return $this->array_id_valore_fondello;
	}

	public function getArrayRispostaGeneraleFondello()
	{
		if ( is_null($this->array_risposta_generale_fondello) ) {
			$response = $this->getApiGeneraleFondelloResponse();
			$array_response = json_decode($response, true);
			$this->array_risposta_generale_fondello = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		$this->etichetta_fondello = count($this->array_risposta_generale_fondello) && array_key_exists('etichette_campi', $this->array_risposta_generale_fondello[0]) ? $this->array_risposta_generale_fondello[0]['etichette_campi'][1][1] : ''; // etichetta generale dell'attributo select
		return $this->array_risposta_generale_fondello;
	}

	public function getEtichettaColpiarm() {
		if ( !strlen($this->etichetta_colpiarm) )
			$this->getArrayRispostaGeneraleColpiarm();
		return $this->etichetta_colpiarm;
	}
	
	public function getArrayIdValoreColpiarm() {
		$dati = $this->getArrayRispostaGeneraleColpiarm();
		foreach ($dati as $dato) {
			$valore = '';
			if ( array_key_exists(1, $dato['dati_campi']) && array_key_exists(1, $dato['dati_campi'][1]) )
				$valore = $dato['dati_campi'][1][1];
			$this->array_id_valore_colpiarm[$dato['id']] = $valore;
		}
		return $this->array_id_valore_colpiarm;
	}

	public function getArrayRispostaGeneraleColpiarm()
	{
		if ( is_null($this->array_risposta_generale_colpiarm) ) {
			$response = $this->getApiGeneraleColpiarmResponse();
			$array_response = json_decode($response, true);
			$this->array_risposta_generale_colpiarm = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		$this->etichetta_colpiarm = count($this->array_risposta_generale_colpiarm) && array_key_exists('etichette_campi', $this->array_risposta_generale_colpiarm[0]) ? $this->array_risposta_generale_colpiarm[0]['etichette_campi'][1][1] : ''; // etichetta generale dell'attributo select
		return $this->array_risposta_generale_colpiarm;
	}

	public function getEtichettaStellear() {
		if ( !strlen($this->etichetta_stellear) )
			$this->getArrayRispostaGeneraleStellear();
		return $this->etichetta_stellear;
	}
	
	public function getArrayIdValoreStellear() {
		$dati = $this->getArrayRispostaGeneraleStellear();
		foreach ($dati as $dato) {
			$valore = '';
			if ( array_key_exists(1, $dato['dati_campi']) && array_key_exists(1, $dato['dati_campi'][1]) )
				$valore = $dato['dati_campi'][1][1];
			$this->array_id_valore_stellear[$dato['id']] = $valore;
		}
		return $this->array_id_valore_stellear;
	}

	public function getArrayRispostaGeneraleStellear()
	{
		if ( is_null($this->array_risposta_generale_stellear) ) {
			$response = $this->getApiGeneraleStellearResponse();
			$array_response = json_decode($response, true);
			$this->array_risposta_generale_stellear = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		$this->etichetta_stellear = count($this->array_risposta_generale_stellear) && array_key_exists('etichette_campi', $this->array_risposta_generale_stellear[0]) ? $this->array_risposta_generale_stellear[0]['etichette_campi'][1][1] : ''; // etichetta generale dell'attributo select
		return $this->array_risposta_generale_stellear;
	}

	public function getEtichettaDiametro() {
		if ( !strlen($this->etichetta_diametro) )
			$this->getArrayRispostaGeneraleDiametro();
		return $this->etichetta_diametro;
	}
	
	public function getArrayIdValoreDiametro() {
		$dati = $this->getArrayRispostaGeneraleDiametro();
		foreach ($dati as $dato) {
			$valore = '';
			if ( array_key_exists(1, $dato['dati_campi']) && array_key_exists(1, $dato['dati_campi'][1]) )
				$valore = $dato['dati_campi'][1][1];
			$this->array_id_valore_diametro[$dato['id']] = $valore;
		}
		return $this->array_id_valore_diametro;
	}

	public function getArrayRispostaGeneraleDiametro()
	{
		if ( is_null($this->array_risposta_generale_diametro) ) {
			$response = $this->getApiGeneraleDiametroResponse();
			$array_response = json_decode($response, true);
			$this->array_risposta_generale_diametro = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		$this->etichetta_diametro = count($this->array_risposta_generale_diametro) && array_key_exists('etichette_campi', $this->array_risposta_generale_diametro[0]) ? $this->array_risposta_generale_diametro[0]['etichette_campi'][1][1] : ''; // etichetta generale dell'attributo select
		return $this->array_risposta_generale_diametro;
	}

	public function getEtichettaTagabbig() {
		if ( !strlen($this->etichetta_tagabbig) )
			$this->getArrayRispostaGeneraleTagabbig();
		return $this->etichetta_tagabbig;
	}
	
	public function getArrayIdValoreTagabbig() {
		$dati = $this->getArrayRispostaGeneraleTagabbig();
		foreach ($dati as $dato) {
			$valore = '';
			if ( array_key_exists(1, $dato['dati_campi']) && array_key_exists(1, $dato['dati_campi'][1]) )
				$valore = $dato['dati_campi'][1][1];
			$this->array_id_valore_tagabbig[$dato['id']] = $valore;
		}
		return $this->array_id_valore_tagabbig;
	}

	public function getArrayRispostaGeneraleTagabbig()
	{
		if ( is_null($this->array_risposta_generale_tagabbig) ) {
			$response = $this->getApiGeneraleTagabbigResponse();
			$array_response = json_decode($response, true);
			$this->array_risposta_generale_tagabbig = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		$this->etichetta_tagabbig = count($this->array_risposta_generale_tagabbig) && array_key_exists('etichette_campi', $this->array_risposta_generale_tagabbig[0]) ? $this->array_risposta_generale_tagabbig[0]['etichette_campi'][1][1] : ''; // etichetta generale dell'attributo select
		return $this->array_risposta_generale_tagabbig;
	}

	public function getEtichettaColorear() {
		if ( !strlen($this->etichetta_colorear) )
			$this->getArrayRispostaGeneraleColorear();
		return $this->etichetta_colorear;
	}
	
	public function getArrayIdValoreColorear() {
		$dati = $this->getArrayRispostaGeneraleColorear();
		foreach ($dati as $dato) {
			$valore = '';
			if ( array_key_exists(1, $dato['dati_campi']) && array_key_exists(1, $dato['dati_campi'][1]) )
				$valore = $dato['dati_campi'][1][1];
			$this->array_id_valore_colorear[$dato['id']] = $valore;
		}
		return $this->array_id_valore_colorear;
	}

	public function getArrayRispostaGeneraleColorear()
	{
		if ( is_null($this->array_risposta_generale_colorear) ) {
			$response = $this->getApiGeneraleColorearResponse();
			$array_response = json_decode($response, true);
			$this->array_risposta_generale_colorear = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		$this->etichetta_colorear = count($this->array_risposta_generale_colorear) && array_key_exists('etichette_campi', $this->array_risposta_generale_colorear[0]) ? $this->array_risposta_generale_colorear[0]['etichette_campi'][1][1] : ''; // etichetta generale dell'attributo select
		return $this->array_risposta_generale_colorear;
	}

	public function getEtichettaAnnoarme() {
		if ( !strlen($this->etichetta_annoarme) )
			$this->getArrayRispostaGeneraleAnnoarme();
		return $this->etichetta_annoarme;
	}
	
	public function getArrayIdValoreAnnoarme() {
		$dati = $this->getArrayRispostaGeneraleAnnoarme();
		foreach ($dati as $dato) {
			$valore = '';
			if ( array_key_exists(1, $dato['dati_campi']) && array_key_exists(1, $dato['dati_campi'][1]) )
				$valore = $dato['dati_campi'][1][1];
			$this->array_id_valore_annoarme[$dato['id']] = $valore;
		}
		return $this->array_id_valore_annoarme;
	}

	public function getArrayRispostaGeneraleAnnoarme()
	{
		if ( is_null($this->array_risposta_generale_annoarme) ) {
			$response = $this->getApiGeneraleAnnoarmeResponse();
			$array_response = json_decode($response, true);
			$this->array_risposta_generale_annoarme = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		$this->etichetta_annoarme = count($this->array_risposta_generale_annoarme) && array_key_exists('etichette_campi', $this->array_risposta_generale_annoarme[0]) ? $this->array_risposta_generale_annoarme[0]['etichette_campi'][1][1] : ''; // etichetta generale dell'attributo select
		return $this->array_risposta_generale_annoarme;
	}

	public function getEtichettaMaterial() {
		if ( !strlen($this->etichetta_material) )
			$this->getArrayRispostaGeneraleMaterial();
		return $this->etichetta_material;
	}
	
	public function getArrayIdValoreMaterial() {
		$dati = $this->getArrayRispostaGeneraleMaterial();
		foreach ($dati as $dato) {
			$valore = '';
			if ( array_key_exists(1, $dato['dati_campi']) && array_key_exists(1, $dato['dati_campi'][1]) )
				$valore = $dato['dati_campi'][1][1];
			$this->array_id_valore_material[$dato['id']] = $valore;
		}
		return $this->array_id_valore_material;
	}

	public function getArrayRispostaGeneraleMaterial()
	{
		if ( is_null($this->array_risposta_generale_material) ) {
			$response = $this->getApiGeneraleMaterialResponse();
			$array_response = json_decode($response, true);
			$this->array_risposta_generale_material = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		$this->etichetta_material = count($this->array_risposta_generale_material) && array_key_exists('etichette_campi', $this->array_risposta_generale_material[0]) ? $this->array_risposta_generale_material[0]['etichette_campi'][1][1] : ''; // etichetta generale dell'attributo select
		return $this->array_risposta_generale_material;
	}

	public function getArrayRispostaClientiRicerca($reset = true, $data = [])
	{
		if ( is_null($this->array_risposta_clienti_ricerca) || $reset ) {
			$response = $this->getApiClientiRicercaResponse($reset, $data);
			$array_response = json_decode($response, true);
			$this->array_risposta_clienti_ricerca = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		return $this->array_risposta_clienti_ricerca;
	}

	public function getArrayRispostaGruppiMerceologici()
	{
		if ( is_null($this->array_risposta_gruppi_merceologici) ) {
			$response = $this->getApiGruppiMerceologiciResponse();
			$array_response = json_decode($response, true);
			$this->array_risposta_gruppi_merceologici = array_key_exists('dati', $array_response) ? $array_response['dati'] : [];
		}
		return $this->array_risposta_gruppi_merceologici;
	}

	public function getArrayRispostaGruppiMerceologiciRielaborati()
	{
		if ( is_null($this->array_codice_gruppo_merceologico) ) {
			$this->array_codice_gruppo_merceologico = [];
			foreach ( $this->getArrayRispostaGruppiMerceologici() as $categoria ) {
				$this->array_codice_gruppo_merceologico[$categoria['codice']] = $categoria;
			}
		}
		return $this->array_codice_gruppo_merceologico;
	}

/*
	public function getArrayClientiRicercaFiltrati($filtro = [], $reset = false, $solo_email_codice = false)
	{
		$clienti_filtrati = $this->getArrayRispostaClientiRicerca($reset);
		if ( array_key_exists('nome', $filtro) )
			$nome = strtolower(trim($filtro['nome']));
		else
			$nome = strtolower(trim($this->scopeConfig->getValue('mexal_config/chiamata_clienti_ricerca/nome', ScopeInterface::SCOPE_STORE)));
			
		if ( array_key_exists('cognome', $filtro) )
			$cognome = strtolower(trim($filtro['cognome']));
		else
			$cognome = strtolower(trim($this->scopeConfig->getValue('mexal_config/chiamata_clienti_ricerca/cognome', ScopeInterface::SCOPE_STORE)));

		if ( array_key_exists('codice', $filtro) )
			$codice = trim($filtro['codice']);
		else
			$codice = strtolower(trim($this->scopeConfig->getValue('mexal_config/chiamata_clienti_ricerca/codice', ScopeInterface::SCOPE_STORE)));

		if ( array_key_exists('email', $filtro) )
			$email = trim($filtro['email']);
		else
			$email = strtolower(trim($this->scopeConfig->getValue('mexal_config/chiamata_clienti_ricerca/email', ScopeInterface::SCOPE_STORE)));

		// in certi casi voglio solo filtrare per queste cose perche' mexal ha i campi noome e cognome salvati a cazzo di cane
		if ($solo_email_codice) {
			$nome = $cognome = ''; //$codice = ''; // momentaneamente resetto anche il codice
		}

		if ( strlen($nome) ) {
			foreach( $clienti_filtrati as $k => $row ) {
				if ( strtolower($row['nome']) != $nome )
					unset($clienti_filtrati[$k]);
			}
		}
		if ( strlen($cognome) ) {
			foreach( $clienti_filtrati as $k => $row ) {
				if ( strtolower($row['cognome']) != $cognome )
					unset($clienti_filtrati[$k]);
			}
		}
		if ( strlen($codice) ) {
			foreach( $clienti_filtrati as $k => $row ) {
				if ( $row['codice'] != $codice )
					unset($clienti_filtrati[$k]);
			}
		}
		if ( strlen($email) ) {
			foreach( $clienti_filtrati as $k => $row ) {
				if ( $row['email'] != $email )
					unset($clienti_filtrati[$k]);
			}
		}
		return $clienti_filtrati;
	}
	*/
}

