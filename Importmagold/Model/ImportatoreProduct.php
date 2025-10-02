<?php
namespace LM\Importmagold\Model;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;

class ImportatoreProduct extends Importatore
{

    const MAGENTO_STATUS_ENABLED = 1;
    const MAGENTO_STATUS_DISABLED = 2;
    
    const MAGENTO_VISIBILITA_NON_VSIBILE = Visibility::VISIBILITY_NOT_VISIBLE; //1;
    const MAGENTO_VISIBILITA_CATALOGO = Visibility::VISIBILITY_IN_CATALOG; //2;
    const MAGENTO_VISIBILITA_RICERCA = Visibility::VISIBILITY_IN_SEARCH; //3;
    const MAGENTO_VISIBILITA_CATALOGO_RICERCA = Visibility::VISIBILITY_BOTH; //4;
    
    public $data_product;
    protected $attributeValues = []; // per ottimizzare salvo le opzioni label->valore dei vari attributi drop down

    const DEFAULT_ATTRIBUTE_SET_NAME = 'Default';
    const DEFAULT_TAX_CLASS_ID = 2; // Taxable Goods
    const ID_ATTRIBUTE_SET_DEFAULT = 4;
    
    protected $productLinkInterface;
    protected $attributeSetCollection;
    protected $attributeOptionLabelInterfaceFactory;
    protected $attributeOptionInterfaceFactory;
    protected $attributeOptionManagementInterface;
    
    protected $imageProcessor;
    protected $tableFactory;
    protected $categoryLinkRepositoryInterface;
    protected $prodotto_importato;
    //protected $productRepository;

	public $output = ['sincProduct' => ''];
	
	const URL_GET_DATA_PRODUCT = "https://armeriaceccoli.com/script_vari/export_products.php?token=hinet123&from_id=";
	const URL_GET_DATA_CATEGORY = "https://armeriaceccoli.com/script_vari/export_category.php?token=hinet123&from_id=";
	
	protected $setupInterface;
	public $categoryFactory;
		
	public function __construct() {
		
		parent::__construct();
		$this->setupInterface = $this->objectManager->create('Magento\Framework\Setup\ModuleDataSetupInterface');

		$this->productLinkInterface = $this->objectManager->create('Magento\Catalog\Api\Data\ProductLinkInterface');
		$this->attributeSetCollection = $this->objectManager->create('\Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory');
		$this->attributeOptionLabelInterfaceFactory = $this->objectManager->create('\Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory');
		$this->attributeOptionInterfaceFactory = $this->objectManager->create('\Magento\Eav\Api\Data\AttributeOptionInterfaceFactory');
		$this->attributeOptionManagementInterface = $this->objectManager->create('\Magento\Eav\Api\AttributeOptionManagementInterface');
		
		$this->imageProcessor = $this->objectManager->create('\Magento\Catalog\Model\Product\Gallery\Processor');
		$this->tableFactory = $this->objectManager->create('\Magento\Eav\Model\Entity\Attribute\Source\TableFactory');
		$this->categoryLinkRepositoryInterface = $this->objectManager->create('\Magento\Catalog\Api\CategoryLinkRepositoryInterface');
		//$this->productRepository = $this->objectManager->create('\Magento\Catalog\Api\ProductRepositoryInterface');
		$this->categoryFactory = $this->objectManager->create('\Magento\Catalog\Api\Data\CategoryInterfaceFactory');

	}

	// restituisce id del magento vecchio dell'ultimo prodotto importato
	public function getLastProductImportedId() {

		$mysqli = $this->getMysqliConnection();
		$db = $this->db;
		
		
		$q = "SELECT * FROM `".$db['name']."`.`".$db['pref']."prodotti_aggiornati` WHERE `aggiornata` = 1 ORDER BY `entity_id_old` DESC;";
		$results = $mysqli->query($q);
		
		if ($row = $results->fetch_assoc())
			return (int) $row['entity_id_old'];
		else
			return 0;
	}

	// restituisce id del magento vecchio dell'ultimo prodotto importato fallato
	public function getLastProductImportedFallatoId() {

		$mysqli = $this->getMysqliConnection();
		$db = $this->db;
		
		
		$q = "SELECT * FROM `".$db['name']."`.`".$db['pref']."prodotti_aggiornati` WHERE `entity_id_new` = 0 ORDER BY `entity_id_old` ASC;";
		$results = $mysqli->query($q);
		
		if ($row = $results->fetch_assoc())
			return (int) $row['entity_id_old'];
		else
			return 0;
	}


	public function getNextProductImportId($cerca_fallato = false) {
		if ($cerca_fallato)
			return $this->getLastProductImportedFallatoId();
		else
			return $this->getLastProductImportedId() + 1;
	}

	// sincronizza importa product su magento
	public function creaAggiornaProdottoSoloFoto($obj_data) {
		error_log(print_r($obj_data, true));
		//error_log('#creaAggiornaProdotto STEP1');
		$data = (array) $obj_data;
		$data = $this->data_product = $this->convDataProduct($data);
		$productExisting = $this->getProductBySku($this->data_product['sku']);
		$data_child = null; // adesso lo imposto nullo non gestisco le varainti
		$boolean_prod_gia_esistente = false;

		// non usato sono tutti 0
		$id_parent = is_null($data_child) ? 0 : $data_child['id_parent_simple']; // se ha un parent è solo una variante taglia
		
		// setto inizialmente per ottimizzazione salvataggio taglie
		$variante_cambiata = false;
		
		if ( is_object($productExisting) ) {
			$this->output['dettaglio_azioni'] .= ' ##productExisting## ';
			$productM = $productExisting->load($productExisting->getId());
			$boolean_prod_gia_esistente = true;
			//error_log('#creaAggiornaProdotto prodotto esistente');
			
		} else {
			//error_log('#creaAggiornaProdotto prodotto NON esistente');
			$this->output['dettaglio_azioni'] .= ' ##product NOT Existing NON FACCIO NIENTE## ';
			$this->output['sincProduct'] = '#OK IMPORT ULTIMATO CON SUCCESSO#';
			return true;
		}
		
		$productM->setWebsiteIds($this->websites); //website ID the product is assigned to, as an array
		$productM->setStoreId(self::ID_STORE_DEFAULT); //you can set data in store scope

		$this->output['dettaglio_azioni'] .= ' #prodotto SKU:'.$this->data_product['sku'].'# ';
		if ( $id_parent ) {
			// VARIANTE TAGLIA


		} else {
			// PARENT


		}

		if ( array_key_exists('data_modifica', $data) && strlen($data['data_modifica']) ) {
			//$productM->setDataModifica($data['data_modifica']);
		}


		//$this->output['dettaglio_azioni'] .= ' #STEP SAVE DOPO DI QTA# ';
		//return;

        	// Foto
			//$data_attuale_foto = (int) $productM->getLastUpdateFoto();
			if ( array_key_exists('image_ids', $data) // || $data_attuale_foto < $data['last_update_foto']
			) {

                // Remove existing
                $this->output['dettaglio_azioni'] .= '##aggiornare foto##';
                if ( is_object($productExisting) ) {

						
						$images = $productM->getMediaGalleryImages();
						foreach($images as $immagine){
							//echo '-----Rimuovo immagine-------'.$immagine->getFile().'------------';
							$path_relativo_foto = $immagine->getFile();
							 $this->imageProcessor->removeImage($productM, $path_relativo_foto);

								$path_assoluto_foto = $this->getMediaFolderPath().'catalog/product' . $path_relativo_foto;
                                if(file_exists($path_assoluto_foto)) {
                                    unlink($path_assoluto_foto);
                                }


						}
						$productM->setMediaGalleryEntries([]);
						$mediatypeArray = array('image', 'thumbnail', 'small_image');
						$this->imageProcessor->clearMediaAttribute($productM,$mediatypeArray);
						//$this->productRepository->save($productM); //$productM->save(); //
						$this->saveProductWithRetry($productM);
						
						$productM->load($productM->getId());
						//echo "RICARICO!!!";
						
                    
                }
                // Add Images To The Product
                $image_base = null;
                $almeno_una_foto = false;
                if ( array_key_exists('image_ids', $data) && array_key_exists(0, $data['image_ids']) ) {

                    $almeno_una_foto = true;
                    $newName = null; //$this->getSeoImageName(1,$data, 'it');
                    $image_base = $this->getFilePathImgProductM1($data['image_ids'][0],$newName);
                    
                    //error_log(print_r($image_base, true));
                    try {
                    if (count($image_base)) {
                        if ( file_exists($image_base['filepath']) && exif_imagetype($image_base['filepath']) !== false ) {
                        	//error_log('PRINCIPALE: '.print_r($image_base, true));
                        	//try {
                            $productM->addImageToMediaGallery($image_base['filepath_relativo'], array('image', 'thumbnail', 'small_image'), false, false); //assigning image, thumb and small image to media gallery
                            $almeno_una_foto = true;
                        	//} catch (Exception $e) {
                        		//$this->logger->error('Errore durante l\'aggiunta dell\'immagine: ' . $e->getMessage() . ' per il prodotto con SKU: ' . $productM->getSku());
                        	//}
                        }
                    } // fine if count
					} catch (\Exception $e) {
						$this->logger->error('Errore immagine principale: ' . $e->getMessage() . ' per SKU: ' . $productM->getSku());
					}

                    
                }
                
                $images_gallery = array();
                if ( array_key_exists('image_ids', $data) ) {
		             /*
		             if (array_key_exists(0, $data['image_ids'])) {
		                 array_push($images_gallery, $data['image_ids'][0]);
		             }
		             */
		             if (array_key_exists(1, $data['image_ids'])) {
		                 array_push($images_gallery, $data['image_ids'][1]);
		             }

		             if (array_key_exists(2, $data['image_ids'])) {
		                 array_push($images_gallery, $data['image_ids'][2]);
		             }

		             if (array_key_exists(3, $data['image_ids'])) {
		                 array_push($images_gallery, $data['image_ids'][3]);
		             }

		             if (array_key_exists(4, $data['image_ids'])) {
		                 array_push($images_gallery, $data['image_ids'][4]);
		             }

		             if (array_key_exists(5, $data['image_ids'])) {
		                 array_push($images_gallery, $data['image_ids'][5]);
		             }
                }

                if (count($images_gallery)) {
                    foreach ($images_gallery as $s=>$imagePath) {
                    	try {
		                    $newName = null;
		                    $immagine = $this->getFilePathImgProductM1($imagePath,$newName);
		                    if ( array_key_exists('filepath', $immagine) && file_exists($immagine['filepath']) && exif_imagetype($immagine['filepath']) !== false ) {
								//error_log('GALLERIA: '.print_r($immagine, true));
								//$this->productRepository->save($productM); //$productM->save();
								$this->saveProductWithRetry($productM);

		                    	//$this->output['dettaglio_azioni'] .= '##aggiornare foto##';
		                    	//try {
		                        $productM->addImageToMediaGallery($immagine['filepath_relativo'], null, false, false);
		                    	//} catch (Exception $e) {
		                    		//$this->logger->error('Errore durante l\'aggiunta dell\'immagine: ' . $e->getMessage() . ' per il prodotto con SKU: ' . $productM->getSku());
		                    	//}
		                        //$this->output['dettaglio_azioni'] .= 'add '.$immagine['filepath_relativo'];
		                        //$this->output['dettaglio_azioni'] .= '--- '.$immagine['filepath'];
		                    }

						} catch (\Exception $e) {
							$this->logger->error('Errore immagine galleria: ' . $e->getMessage() . ' per SKU: ' . $productM->getSku());
							continue; // Salta questa immagine ma prosegue
						}

                    } // foreach
                }

                //$productM->setLastUpdateFoto($data['last_update_foto']);
                //$this->output['dettaglio_azioni'] .= '##setto  last_update_foto '.$data['last_update_foto'].'##';

                if (!$almeno_una_foto) {
                    //$productM->setStatus(2); //product status (1 - enabled, 2 - disabled)
                }

                // voglio controllare ulteriormente se il prodotto ha foto per nasconderlo in caso negativo
                if (!count($productM->getMediaGalleryImages())) {
                    //$productM->setStatus(2); //product status (1 - enabled, 2 - disabled)
                }
                $this->output['dettaglio_azioni'] .= ' #fine agg foto# ';
		} else {
           //$this->output['dettaglio_azioni'] .= ' #la data di agg foto non richiede aggiornamento# ';
		}

		//$productM->save();
		//$this->output['dettaglio_azioni'] .= ' #STEP SAVE DOPO FOTO# ';
		//return;


		if ( !array_key_exists('image_ids', $data) || !array_key_exists(0, $data['image_ids']) ) {
                //$productM->setStatus(2);
 		}

		// fine foto
		//$this->productRepository->save($productM); //$productM->save();
		try {
			$this->saveProductWithRetry($productM);
		} catch (\Exception $e) {
			$this->logger->error('Errore salvataggio prodotto con foto: ' . $e->getMessage() . ' per SKU: ' . $productM->getSku());
			return; // oppure `continue` se sei in un ciclo
		}
		$this->output['dettaglio_azioni'] .= ' #SAVE PRPDUCT# ';
    	
		$this->prodotto_importato = $productM;
		$this->output['sincProduct'] = '#OK IMPORT ULTIMATO CON SUCCESSO#';
		//error_log('STEP_17');
		//return $this->mandaOutput(); // lo faccio nel file che chiama la classe
	}

	public function creaAggiornaProdotto($obj_data) {
		error_log(print_r($obj_data, true));
		//error_log('#creaAggiornaProdotto STEP1');
		$data = (array) $obj_data;
		$data = $this->data_product = $this->convDataProduct($data);
		$productExisting = $this->getProductBySku($this->data_product['sku']);
		$data_child = null; // adesso lo imposto nullo non gestisco le varainti
		$boolean_prod_gia_esistente = false;

		// non usato sono tutti 0
		$id_parent = is_null($data_child) ? 0 : $data_child['id_parent_simple']; // se ha un parent è solo una variante taglia
		
		// setto inizialmente per ottimizzazione salvataggio taglie
		$variante_cambiata = false;
		
		if ( is_object($productExisting) ) {
			$this->output['dettaglio_azioni'] .= ' ##productExisting## ';
			$productM = $productExisting->load($productExisting->getId());
			$boolean_prod_gia_esistente = true;
			//error_log('#creaAggiornaProdotto prodotto esistente');
			
		} else {
			//error_log('#creaAggiornaProdotto prodotto NON esistente');
			$this->output['dettaglio_azioni'] .= ' ##product NOT Existing## ';
      		$productM = $this->objectManager->create('\Magento\Catalog\Model\Product');
      		$productM->setId(null);
			$productM->setAttributeSetId( $this->data_product['attribute_set_id'] );
			//$this->output['dettaglio_azioni'] .= "setAttributeSetId:".$this->data_product['attribute_set_id'];
			//echo "###id_parent:$id_parent ##";
			//$this->output['dettaglio_azioni'] .= "##".$this->data_product['sku']."##";
			if ( !array_key_exists('name', $this->data_product) )
				$this->data_product['name'] = $this->data_product['sku'];

			if ( $id_parent ) {
				$productM->setTypeId('simple');
				if ( array_key_exists('sku_simple', $data_child) )
					$productM->setSku($data_child['sku_simple']);
			} else {
				$productM->setTypeId($this->data_product['type_id']); // simple o configurable
				//$this->output['dettaglio_azioni'] .= "setTypeId:".$this->data_product['type_id'];
				$productM->setSku($this->data_product['sku']);
			}


			// Set a unique URL key
			$uniqueUrlKey = $this->generateUniqueUrlKey($productM->getSku());
			$productM->setUrlKey($uniqueUrlKey);
			
			$productM->setPrice(1000000); // presetto perchè obbligatorio
		}
		
		$productM->setWebsiteIds($this->websites); //website ID the product is assigned to, as an array
		$productM->setStoreId(self::ID_STORE_DEFAULT); //you can set data in store scope

		$this->output['dettaglio_azioni'] .= ' #prodotto SKU:'.$this->data_product['sku'].'# ';
		if ( $id_parent ) {
			// VARIANTE TAGLIA
			$this->output['dettaglio_azioni'] .= ' #prodotto con PARENT# ';
			$productM->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE);
			
			if ( array_key_exists('name_simple', $data_child) && $productM->getName() != $data_child['name_simple'] ) $variante_cambiata = true;
			if ( array_key_exists('name_simple', $data_child) ) $productM->setName($data_child['name_simple']);
			
			if ( array_key_exists('status_simple', $data_child) && $productM->getStatus() != $data_child['status_simple'] ) $variante_cambiata = true;
			if ( array_key_exists('status_simple', $data_child) ) $productM->setStatus($data_child['status_simple']);
			//echo "Setto status:".$data_child['status_simple']." al prodotto:".$productM->getSku();
			if ( array_key_exists('barcode_simple', $data_child) && $productM->getGtin() != $data_child['barcode_simple'] ) $variante_cambiata = true;
			if ( array_key_exists('barcode_simple', $data_child) ) $productM->setGtin($data_child['barcode_simple']);
			
			if ( array_key_exists('price_simple', $data_child) && $productM->getPrice() != $data_child['price_simple'] ) $variante_cambiata = true;
			if ( array_key_exists('price_simple', $data_child) ) $productM->setPrice($data_child['price_simple']);
			if ( array_key_exists('special_price_simple', $data_child) && $productM->getSpecialPrice() != $data_child['special_price_simple'] ) $variante_cambiata = true;
			if ( array_key_exists('special_price_simple', $data_child) ) $productM->setSpecialPrice($data_child['special_price_simple']);

			if ( array_key_exists('taglia', $data_child) && strlen($data_child['taglia']) ) {
				$OptionId = $this->getCreateAttributeOptionValueByLabel($this->getAttributeConfName($data['attributeSetName']), $data_child['taglia']);
				$productM = $this->setAttributeConfName($data['attributeSetName'], $OptionId, $productM);
			}

			
			if ( array_key_exists('qta_simple', $data_child) && array_key_exists('is_in_stock_simple', $data_child) ) {
				$stockItem = $productM->getExtensionAttributes()->getStockItem(); // stockItem potrebbe essere nullo se sto creando ora il prodotto
				if ( !is_object($stockItem) || $stockItem->getQty() != $data_child['qta_simple'] ) {
					$variante_cambiata = true;
					$productM->setStockData(
						array(
						    'use_config_manage_stock' => 1,
						    'manage_stock' => 1,
						    'min_sale_qty' => 1, //Minimum Qty Allowed in Shopping Cart
						    'max_sale_qty' => 10000000, //Maximum Qty Allowed in Shopping Cart
						    'is_in_stock' => $data_child['is_in_stock_simple'],
						    'qty' => $data_child['qta_simple']
						)
					);
				}
			}


		} else {
			// PARENT
			//$this->output['dettaglio_azioni'] .= ' #prodotto senza PARENT# ';
			$productM->setVisibility($data['visibility']);

			if ( array_key_exists('url_key', $data) && $productM->getUrlKey() != $data['url_key'] ) {
				$productM->setUrlKey($data['url_key']);
			}

			if ( array_key_exists('name', $data) ) $productM->setName($data['name']);
			if ( array_key_exists('short_description', $data) ) $productM->setShortDescription($data['short_description']);
			if ( array_key_exists('description', $data) ) $productM->setDescription($data['description']);

			// blocco seo
			if ( array_key_exists('meta_title', $data) ) $productM->setMetaTitle($data['meta_title']);
			if ( array_key_exists('meta_description', $data) ) $productM->setMetaDescription($data['meta_description']);
			if ( array_key_exists('meta_keyword', $data) ) $productM->setMetaKeyword($data['meta_keyword']);
			
			if ( array_key_exists('status', $data) ) $productM->setStatus($data['status']);
			if ( array_key_exists('barcode', $data) ) $productM->setGtin($data['barcode']);
			if ( array_key_exists('weight', $data) ) $productM->setWeight($data['weight']);
			if ( array_key_exists('taglia', $data) ) $productM->setTaglia($data['taglia']);
			
			if ( array_key_exists('price', $data) ) $productM->setPrice($data['price']);
			if ( array_key_exists('special_price', $data) ) $productM->setSpecialPrice($data['special_price']);

			if ( array_key_exists('news_from_date', $data) ) {
				$news_from_date = isset($data['news_from_date']) && strlen($data['news_from_date']) ? strtotime($data['news_from_date']) : '';
				$productM->setNewsFromDate($news_from_date);
			}
			if ( array_key_exists('news_to_date', $data) ) {
				$news_to_date = isset($data['news_to_date']) && strlen($data['news_to_date']) ? strtotime($data['news_to_date']) : '';
				$productM->setNewsToDate($news_to_date);
			}

			if ( array_key_exists('special_from_date', $data) ) {
				$special_from_date = isset($data['special_from_date']) && strlen($data['special_from_date']) ? strtotime($data['special_from_date']) : '';
				$productM->setSpecialFromDate($special_from_date);
			}
			if ( array_key_exists('special_to_date', $data) ) {
				$special_to_date = isset($data['special_to_date']) && strlen($data['special_to_date']) ? strtotime($data['special_to_date']) : '';
				$productM->setSpecialToDate($special_to_date);
			}

			$this->output['dettaglio_azioni'] .= ' #STEP PRIMA DI QTA# ';
			//$this->productRepository->save($productM); //$productM->save();
			$this->saveProductWithRetry($productM);
			
			$this->output['dettaglio_azioni'] .= ' #STEP SAVE PRIMA DI QTA# ';


			if ( array_key_exists('qta', $data) && array_key_exists('is_in_stock', $data) ) {
				$productM->setStockData(
				    array(
				        'use_config_manage_stock' => 1,
				        'manage_stock' => 1,
				        'min_sale_qty' => 1, //Minimum Qty Allowed in Shopping Cart
				        'max_sale_qty' => 10000000, //Maximum Qty Allowed in Shopping Cart
				        'is_in_stock' => $data['is_in_stock'],
				        'qty' => $data['qta']
				    )
				);
				$this->output['dettaglio_azioni'] .= '##aggiorno qta '.$data['qta'].' setStockData ##';
			}


		}

		//$productM->save();
		//$this->output['dettaglio_azioni'] .= ' #STEP SAVE DOPO DI QTA# ';
		//return;


		// in ogni caso, sia che sia un parent che una variante
		if ( array_key_exists('manufacturer', $data) && strlen($data['manufacturer']) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('manufacturer', $data['manufacturer']);
			$productM->setManufacturer($OptionId);
		}
		if ( array_key_exists('country', $data) ) $productM->setCountryOfManufacture($data['country']);
		if ( array_key_exists('ids_user', $data) ) $productM->setIdsUserAmministrazione( implode(',', $ids_user) );
		if ( array_key_exists('stagione', $data) && strlen($data['stagione']) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('stagione', $data['stagione']);
			$productM->setStagione($OptionId);
		}

		if ( array_key_exists('data_modifica', $data) && strlen($data['data_modifica']) ) {
			$productM->setDataModifica($data['data_modifica']);
		}

		// multiselect gender
		if ( array_key_exists('gender', $data) ) {
			$OptionIds = array();
			foreach ($data['gender'] as $id) {
				$OptionId = $this->getCreateAttributeOptionValueByLabel('gender', $id);
				array_push($OptionIds, $OptionId);
			}
			if ( count($OptionIds) )
				$productM->setGender(implode(',', $OptionIds));
			else
				$productM->setGender([]);
		}
		if ( array_key_exists('materiale', $data) && strlen($data['materiale']) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('materiale', $data['materiale']);
			$productM->setMateriale($OptionId);
		}
		if ( array_key_exists('tipo_telaio', $data) && strlen($data['tipo_telaio']) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('tipo_telaio', $data['tipo_telaio']);
			$productM->setTipoTelaio($OptionId);
		}
		if ( array_key_exists('color', $data) && strlen($data['color']) ) {
			//$this->output['dettaglio_azioni'] .= " #color:".$data['color']."# ";
			$codice_attributo = 'color';
			$this->setIfAttributeDropExist($productM, $codice_attributo, $data['color'], $data['attribute_set_id']);

			$codice_attributo = 'colore_abbigliamento';
			$this->setIfAttributeDropExist($productM, $codice_attributo, $data['color'], $data['attribute_set_id']);
			
			$codice_attributo = 'colore_accessori';
			$this->setIfAttributeDropExist($productM, $codice_attributo, $data['color'], $data['attribute_set_id']);
			
			$codice_attributo = 'colore_armi';
			$this->setIfAttributeDropExist($productM, $codice_attributo, $data['color'], $data['attribute_set_id']);
			
			$codice_attributo = 'colore_bossoli';
			$this->setIfAttributeDropExist($productM, $codice_attributo, $data['color'], $data['attribute_set_id']);
			
			$codice_attributo = 'colore_calzature';
			$this->setIfAttributeDropExist($productM, $codice_attributo, $data['color'], $data['attribute_set_id']);
			
		}
		if ( array_key_exists('famiglia_colore', $data) && strlen($data['famiglia_colore']) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('famiglia_colore', $data['famiglia_colore']);
			$productM->setFamigliaColore($OptionId);
		}
		if ( array_key_exists('calibro', $data) && strlen($data['calibro']) ) {
		
			$codice_attributo = 'calibro_aria_comp';
			$this->setIfAttributeDropExist($productM, $codice_attributo, $data['calibro'], $data['attribute_set_id']);
		
			$codice_attributo = 'calibro_armi';
			$this->setIfAttributeDropExist($productM, $codice_attributo, $data['calibro'], $data['attribute_set_id']);
		
			$codice_attributo = 'calibro_bossoli';
			$this->setIfAttributeDropExist($productM, $codice_attributo, $data['calibro'], $data['attribute_set_id']);
		
			$codice_attributo = 'calibro_dies';
			$this->setIfAttributeDropExist($productM, $codice_attributo, $data['calibro'], $data['attribute_set_id']);
		
			$codice_attributo = 'calibro_munizioni';
			$this->setIfAttributeDropExist($productM, $codice_attributo, $data['calibro'], $data['attribute_set_id']);

			$codice_attributo = 'calibro_palle';
			$this->setIfAttributeDropExist($productM, $codice_attributo, $data['calibro'], $data['attribute_set_id']);

		}
		if ( array_key_exists('genere', $data) && strlen($data['genere']) ) {
			//$OptionId = $this->getCreateAttributeOptionValueByLabel('genere', $data['genere']);
			//$productM->setGenere($OptionId);
			// ora genere è alfanumerico
			$productM->setGenere($data['genere']);
		}
		if ( array_key_exists('altezza', $data) && strlen($data['altezza']) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('altezza', $data['altezza']);
			$productM->setAltezza($OptionId);
		}
		if ( array_key_exists('composizione_dies', $data) && strlen($data['composizione_dies']) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('composizione_dies', $data['composizione_dies']);
			$productM->setComposizioneDies($OptionId);
		}
		if ( array_key_exists('fondello', $data) && strlen($data['fondello']) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('fondello', $data['fondello']);
			$productM->setFondello($OptionId);
		}
		if ( array_key_exists('colpi', $data) && strlen($data['colpi']) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('colpi', $data['colpi']);
			$productM->setColpi($OptionId);
		}
		if ( array_key_exists('stelle', $data) && strlen($data['stelle']) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('stelle', $data['stelle']);
			$productM->setStelle($OptionId);
		}
		if ( array_key_exists('litri', $data) && strlen($data['litri']) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('litri', $data['litri']);
			$productM->setLitri($OptionId);
		}
		if ( array_key_exists('diametro_palle', $data) && strlen($data['diametro_palle']) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('diametro_palle', $data['diametro_palle']);
			$productM->setDiametroPalle($OptionId);
		}
		if ( array_key_exists('profilo', $data) && strlen($data['profilo']) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('profilo', $data['profilo']);
			$productM->setProfilo($OptionId);
		}
		if ( array_key_exists('nome_palla', $data) && strlen($data['nome_palla']) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('nome_palla', $data['nome_palla']);
			$productM->setNomePalla($OptionId);
		}
		if ( array_key_exists('decreto', $data) ) {
			//$OptionId = $this->getCreateAttributeOptionValueByLabel('prescription_required', $data['decreto']);
			$this->output['dettaglio_azioni'] .= "##DECRETO:".$data['decreto']."##";
			//$this->output['dettaglio_azioni'] .= "##prescription_required OptionId:$OptionId##";
			//$productM->setPrescriptionRequired($OptionId);
			if ( strlen(trim($data['decreto'])) )
				$productM->setPrescriptionRequired(1);
			else
				$productM->setPrescriptionRequired(0);
		}
		if ( array_key_exists('decreto', $data) ) {
			if ( strlen(trim($data['decreto'])) ) {
				$OptionId = $this->getCreateAttributeOptionValueByLabel('decreto', $data['decreto']);
				$productM->setDecreto($OptionId);
			} else {
				$productM->setData('decreto', null);
			}
		}
		if ( array_key_exists('scala_taglie', $data) ) $productM->setScalaTaglie($data['scala_taglie']);
		if ( array_key_exists('weight', $data) ) $productM->setWeight($data['weight']);
		if ( array_key_exists('tax_class_id', $data) ) $productM->setTaxClassId($data['tax_class_id']);

		//$productM->save();
		//$this->output['dettaglio_azioni'] .= ' #prodotto id:# '.$productM->getId();


		if ( array_key_exists('linkDataAll', $data) )
			$productM->setProductLinks($data['linkDataAll']);

		//$this->productRepository->save($productM); //$productM->save();
		$this->saveProductWithRetry($productM);

		//$this->output['dettaglio_azioni'] .= ' #STEP SAVE DOPO DI QTA# ';
		//return;

        	// Foto
			//$data_attuale_foto = (int) $productM->getLastUpdateFoto();
			if ( array_key_exists('image_ids', $data) // || $data_attuale_foto < $data['last_update_foto']
			) {

                // Remove existing
                $this->output['dettaglio_azioni'] .= '##aggiornare foto##';
                if ( is_object($productExisting) ) {

						
						$images = $productM->getMediaGalleryImages();
						foreach($images as $immagine){
							//echo '-----Rimuovo immagine-------'.$immagine->getFile().'------------';
							$path_relativo_foto = $immagine->getFile();
							 $this->imageProcessor->removeImage($productM, $path_relativo_foto);

								$path_assoluto_foto = $this->getMediaFolderPath().'catalog/product' . $path_relativo_foto;
                                if(file_exists($path_assoluto_foto)) {
                                    unlink($path_assoluto_foto);
                                }


						}
						$productM->setMediaGalleryEntries([]);
						$mediatypeArray = array('image', 'thumbnail', 'small_image');
						$this->imageProcessor->clearMediaAttribute($productM,$mediatypeArray);
						//$this->productRepository->save($productM); //$productM->save(); //
						$this->saveProductWithRetry($productM);
						
						$productM->load($productM->getId());
						//echo "RICARICO!!!";
						
                    
                }
                // Add Images To The Product
                $image_base = null;
                $almeno_una_foto = false;
                if ( array_key_exists('image_ids', $data) && array_key_exists(0, $data['image_ids']) ) {

                    $almeno_una_foto = true;
                    $newName = null; //$this->getSeoImageName(1,$data, 'it');
                    $image_base = $this->getFilePathImgProductM1($data['image_ids'][0],$newName);
                    
                    //error_log(print_r($image_base, true));
                    if (count($image_base)) {
                        if ( file_exists($image_base['filepath']) && exif_imagetype($image_base['filepath']) !== false ) {
                        	//error_log('PRINCIPALE: '.print_r($image_base, true));
                        	//try {
                            $productM->addImageToMediaGallery($image_base['filepath_relativo'], array('image', 'thumbnail', 'small_image'), false, false); //assigning image, thumb and small image to media gallery
                            $almeno_una_foto = true;
                        	//} catch (Exception $e) {
                        		//$this->logger->error('Errore durante l\'aggiunta dell\'immagine: ' . $e->getMessage() . ' per il prodotto con SKU: ' . $productM->getSku());
                        	//}
                        }
                    }
                }
                
                $images_gallery = array();
                if ( array_key_exists('image_ids', $data) ) {
		             /*
		             if (array_key_exists(0, $data['image_ids'])) {
		                 array_push($images_gallery, $data['image_ids'][0]);
		             }
		             */
		             if (array_key_exists(1, $data['image_ids'])) {
		                 array_push($images_gallery, $data['image_ids'][1]);
		             }

		             if (array_key_exists(2, $data['image_ids'])) {
		                 array_push($images_gallery, $data['image_ids'][2]);
		             }

		             if (array_key_exists(3, $data['image_ids'])) {
		                 array_push($images_gallery, $data['image_ids'][3]);
		             }

		             if (array_key_exists(4, $data['image_ids'])) {
		                 array_push($images_gallery, $data['image_ids'][4]);
		             }

		             if (array_key_exists(5, $data['image_ids'])) {
		                 array_push($images_gallery, $data['image_ids'][5]);
		             }
                }

                if (count($images_gallery)) {

                    foreach ($images_gallery as $s=>$imagePath) {
                        $newName = null;
                        $immagine = $this->getFilePathImgProductM1($imagePath,$newName);
                        if ( array_key_exists('filepath', $immagine) && file_exists($immagine['filepath']) && exif_imagetype($immagine['filepath']) !== false ) {
							//error_log('GALLERIA: '.print_r($immagine, true));
							//$this->productRepository->save($productM); //$productM->save();
							$this->saveProductWithRetry($productM);

                        	//$this->output['dettaglio_azioni'] .= '##aggiornare foto##';
                        	//try {
                            $productM->addImageToMediaGallery($immagine['filepath_relativo'], null, false, false);
                        	//} catch (Exception $e) {
                        		//$this->logger->error('Errore durante l\'aggiunta dell\'immagine: ' . $e->getMessage() . ' per il prodotto con SKU: ' . $productM->getSku());
                        	//}
                            //$this->output['dettaglio_azioni'] .= 'add '.$immagine['filepath_relativo'];
                            //$this->output['dettaglio_azioni'] .= '--- '.$immagine['filepath'];
                        }
                    }
                }

                //$productM->setLastUpdateFoto($data['last_update_foto']);
                //$this->output['dettaglio_azioni'] .= '##setto  last_update_foto '.$data['last_update_foto'].'##';

                if (!$almeno_una_foto) {
                    //$productM->setStatus(2); //product status (1 - enabled, 2 - disabled)
                }

                // voglio controllare ulteriormente se il prodotto ha foto per nasconderlo in caso negativo
                if (!count($productM->getMediaGalleryImages())) {
                    //$productM->setStatus(2); //product status (1 - enabled, 2 - disabled)
                }
                $this->output['dettaglio_azioni'] .= ' #fine agg foto# ';
		} else {
           //$this->output['dettaglio_azioni'] .= ' #la data di agg foto non richiede aggiornamento# ';
		}

		//$productM->save();
		//$this->output['dettaglio_azioni'] .= ' #STEP SAVE DOPO FOTO# ';
		//return;


		if ( !array_key_exists('image_ids', $data) || !array_key_exists(0, $data['image_ids']) ) {
                //$productM->setStatus(2);
 		}

		// fine foto

		// inizio aggiornamento categorie prodotto che al momento disabilito (non era in preventivo)
		if ( false && array_key_exists('category_ids', $data) ) {
				$cats_magento_nuove = $data['category_ids']; // ho gia le categorie di magento nuove qui 

				// rimuovo le categorie che non sono più presenti nel prodotto
				$previousCategoryIds = $productM->getCategoryIds();
				$da_risalvare_per_problema_categorie = false;
				foreach ( $previousCategoryIds as $id_cat_old ) {
					if ( !in_array($id_cat_old, $cats_magento_nuove) ) {
						$da_risalvare_per_problema_categorie = true;
					}
				}
				if ($da_risalvare_per_problema_categorie) {
					$this->output['dettaglio_azioni'] .= '## salvo e ricarico per bug categorie ##';
					//$this->productRepository->save($productM); //$productM->save();
					$this->saveProductWithRetry($productM);

					// salvo quanto fatto fin qui, poi cancello e ricarico il prodotto perchè altrimenti mi reinserisce le cagtegorie vecchie
					$this->togliCategorieProdottoNonPresentiConApiMagento($productM,$cats_magento_nuove);
					$productM->load($productM->getId());
					$productM->setCategoryIds($cats_magento_nuove);
				} else {
					$productM->setCategoryIds($cats_magento_nuove);
				}
				$this->output['dettaglio_azioni'] .= ' #fine agg categorie# ';

		}
		
		if ( array_key_exists('category_ids_da_mexal', $data) ) {
				$cats_magento_nuove = $data['category_ids_da_mexal']; // ho gia le categorie di magento nuove qui 

				// rimuovo le categorie che non sono più presenti nel prodotto
				$previousCategoryIds = $productM->getCategoryIds();
				$da_risalvare_per_problema_categorie = false;
				foreach ( $previousCategoryIds as $id_cat_old ) {
					if ( !in_array($id_cat_old, $cats_magento_nuove) ) {
						$da_risalvare_per_problema_categorie = true;
					}
				}
				if ($da_risalvare_per_problema_categorie) {
					$this->output['dettaglio_azioni'] .= '## salvo e ricarico per bug categorie ##';
					//$this->productRepository->save($productM); //$productM->save();
					$this->saveProductWithRetry($productM);

					// salvo quanto fatto fin qui, poi cancello e ricarico il prodotto perchè altrimenti mi reinserisce le cagtegorie vecchie
					$this->togliCategorieProdottoNonPresentiConApiMagento($productM,$cats_magento_nuove);
					$productM->load($productM->getId());
					$productM->setCategoryIds($cats_magento_nuove);
				} else {
					$productM->setCategoryIds($cats_magento_nuove);
				}
				foreach ($cats_magento_nuove as $cat_magento_nuova) {
					$this->output['dettaglio_azioni'] .= " #SET categoria ID $cat_magento_nuova# ";
				}
				$this->output['dettaglio_azioni'] .= ' #fine agg categorie# ';		
		}

		//error_log('STEP_11');
		//$this->productRepository->save($productM); //$productM->save();
		$this->saveProductWithRetry($productM);

		//error_log('STEP_12');
		$this->output['dettaglio_azioni'] .= ' #SAVE PRPDUCT# ';

    	
    	
		$storeIds = $this->storeManagerInterface->getStores(true, false);
		if ($boolean_prod_gia_esistente) {
			//error_log('STEP_13');
			foreach ($storeIds as $store) {
				 if ($store->getId() != self::ID_STORE_DEFAULT) {
				 	//error_log('STEP_14 STORE ID:'.$store->getId());
					$productM = $this->productRepository->getById($productM->getId(), false, $store->getId());
					  $productM->setStoreId($store->getId());
					  
					  // Reimposta gli attributi specifici dello store a null
					  $productM->setName(null);
					  $productM->setDescription(null);
					  $productM->setShortDescription(null);
					  $productM->setMetaTitle(null);
					  $productM->setMetaDescription(null);
					  $productM->setMetaKeyword(null);
					  $productM->setData('status', null);
					  $productM->setData('manufacturer', null);
					  $productM->setData('tax_class_id', null);
					  $productM->setData('visibility', null);
					  //$productM->setSpecialPrice(null); //sono globali
					  //$productM->setPrice(null);
					  $productM->setNewsFromDate(null);
					  $productM->setNewsToDate(null);
					  $productM->setSpecialFromDate(null);
					  $productM->setSpecialToDate(null);
					  //error_log('STEP_15');
					  // Salva il prodotto per questo store view
					  //$this->productRepository->save($productM);
					$this->saveProductWithRetry($productM);
					  
					  
					  //error_log('STEP_16');
				 }
			}
		}
		
		$this->prodotto_importato = $productM;
		$this->output['sincProduct'] = '#OK IMPORT ULTIMATO CON SUCCESSO#';
		//error_log('STEP_17');
		//return $this->mandaOutput(); // lo faccio nel file che chiama la classe
	}


	public function togliCategorieProdottoNonPresentiConApiMagento($productM,$category_ids_nuove) {
			
			// vecchio metodo per rimuovere un prodotto da una categoria con le api di magento
			$categoryLink = $this->objectManager->get('\Magento\Catalog\Api\CategoryLinkRepositoryInterface');
			$previousCategoryIds = $productM->getCategoryIds();
			foreach ( $previousCategoryIds as $id_cat_old ) {
				if ( !in_array($id_cat_old, $category_ids_nuove) ) {
						try {
							$categoryLink->deleteByIds($id_cat_old, $productM->getSku());
						} catch (Exception $e) {
							//echo 'Caught exception: ',  $e->getMessage(), "\n";
						}
				}
			}
	}


	public static function filtraNome($txt, $allow_empty = true) {
		$txt = trim(preg_replace("/[^ \w]+/", "", $txt)); // toglie asterischi e caratteracci
		//$txt = trim($txt);
		if ($allow_empty)
			return $txt;
		else
			return strlen($txt) ? $txt : 'null';
	}

	public function convDataProduct($data) {
		$this->getLastCategoryImportedId();
		//echo '<pre>'; print_r($data); echo '</pre>'; //return;
		//$this->data_product = $data;
		$this->data_product = [];
		$this->data_product['entity_id_old'] = array_key_exists('entity_id', $data) ? $data['entity_id'] : 0;
		$this->data_product['sku'] = $data['sku'];
		//$this->data_product['name'] = !array_key_exists('name', $data) ? $data['sku'] : $data['name'];
		if ( array_key_exists('name', $data) ) $this->data_product['name'] = $data['name'];
		//$this->data_product['description'] = array_key_exists('description', $data) ? $data['description']  : '';
		if ( array_key_exists('description', $data) ) $this->data_product['description'] = $data['description'];
		//$this->data_product['short_description'] = array_key_exists('short_description', $data) ? $data['short_description'] : '';
		if ( array_key_exists('short_description', $data) ) $this->data_product['short_description'] = $data['short_description'];
		
		if ( array_key_exists('data_modifica', $data) ) $this->data_product['data_modifica'] = $data['data_modifica'];
		
		
		$this->data_product['attribute_set_name'] = array_key_exists('attribute_set_name', $data) ? $data['attribute_set_name']  : 'Default';
		$this->data_product['attribute_set_id'] = $this->getAttrSetIdByName($this->data_product['attribute_set_name']);

		if ( array_key_exists('type_id', $data) )
			$this->data_product['type_id'] = $data['type_id'];
		else
			$this->data_product['type_id'] = 'simple';

		//$this->data_product['status'] = $data['status'];
		if ( array_key_exists('status', $data) ) $this->data_product['status'] = $data['status'];
		$this->data_product['tax_class_id'] = self::DEFAULT_TAX_CLASS_ID;
		$this->data_product['visibility'] = array_key_exists('visibility', $data) ? $data['visibility'] : self::MAGENTO_VISIBILITA_CATALOGO_RICERCA;
		if ( array_key_exists('decreto', $data) ) $this->data_product['decreto'] = $data['decreto'];

		if ( array_key_exists('price', $data) && (float) $data['price'] > 0 ) $this->data_product['price'] = (float) $data['price'];
		//if ( array_key_exists('special_price', $data) && (float) $data['special_price'] > 0 ) $this->data_product['special_price'] = (float) $data['special_price'];
		$this->data_product['special_price'] = array_key_exists('special_price', $data) && (float) $data['special_price'] > 0 ? (float) $data['special_price'] : null;
		
		// calcolo anche lo sconto in euro // qui non serve a niente comunque
		if ( array_key_exists('price', $data) && (float) $data['price'] > 0
			&& array_key_exists('special_price', $data) && (float) $data['special_price'] > 0 ) {
			$this->data_product['euro_sconto'] = $this->data_product['price'] - $this->data_product['special_price'];
		}
		if ( array_key_exists('weight', $data) ) $this->data_product['weight'] = (float) $data['weight'];

		// qui è diverso l'imput dei dati non abbiamo image_ids
		if ( array_key_exists('image_ids', $data) ) $this->data_product['image_ids'] = $data['image_ids'];
		// abbiamo image e media_gallery
		if ( array_key_exists('image', $data) ) {
			$this->data_product['image'] = $data['image'];
			$this->data_product['image_ids'] = array();
			$this->data_product['image_ids'][0] = $data['image'];
			$n = 1;
			if ( array_key_exists('media_gallery', $data) ) {
			
				if ( isset($data['media_gallery']->images) && is_array($data['media_gallery']->images) ) {
					foreach($data['media_gallery']->images as $image) {
						$this->output['dettaglio_azioni'] .= "media_gallery $n:".$image->file.'#';
						if ( isset($image->file) && !$image->disabled ) {
							$this->data_product['image_ids'][$n] = $image->file;
							$n += 1;
						}
					}
				}
			}
		}
		
		if ( array_key_exists('meta_title', $data) ) $this->data_product['meta_title'] = $data['meta_title'];
		//if ( array_key_exists('metatitle_en', $data) && strlen(trim($data['metatitle_en'])) ) $this->data_product['metatitle_en'] = $data['metatitle_en'];

		if ( array_key_exists('meta_description', $data) ) $this->data_product['meta_description'] = $data['meta_description'];
		//if ( array_key_exists('meta_description_en', $data) && strlen(trim($data['meta_description_en'])) ) $this->data_product['meta_description_en'] = $data['meta_description_en'];
		
		if ( array_key_exists('meta_keyword', $data) ) $this->data_product['meta_keyword'] = $data['meta_keyword'];
		
		if ( array_key_exists('news_from_date', $data) ) $this->data_product['news_from_date'] = $data['news_from_date'];
		if ( array_key_exists('news_to_date', $data) ) $this->data_product['news_to_date'] = $data['news_to_date'];
		if ( array_key_exists('special_from_date', $data) ) $this->data_product['special_from_date'] = $data['special_from_date'];
		if ( array_key_exists('special_to_date', $data) ) $this->data_product['special_to_date'] = $data['special_to_date'];
		if ( array_key_exists('manufacturer', $data) && $data['manufacturer'] !== null && strlen(trim($data['manufacturer'])) ) $this->data_product['manufacturer'] = $data['manufacturer'];
		
		
		if ( array_key_exists('country', $data) && strlen(trim($data['country'])) ) $this->data_product['country'] = $data['country'];
		
		if ( array_key_exists('taglia', $data) && strlen(trim($data['taglia'])) ) $this->data_product['taglia'] = $data['taglia'];
		
		if ( array_key_exists('ids_user', $data) ) $this->data_product['ids_user'] = $data['ids_user'];

		if ( array_key_exists('stagione', $data) && strlen(trim($data['stagione'])) ) $this->data_product['stagione'] = $data['stagione'];
		
		if ( array_key_exists('gender', $data) ) $this->data_product['gender'] = $data['gender'];
	
		if ( array_key_exists('category_ids', $data) ) {
			$category_ids = array();
			foreach ( $data['category_ids'] as $id_cat_old ) {
				$id_cat_new = $this->convertiCategoria($id_cat_old);
				if ($id_cat_new)
					array_push($category_ids, $id_cat_new);
			}
			if ( count($category_ids) )
				$this->data_product['category_ids'] = $category_ids;
		}

		if ( array_key_exists('codice_categoria', $data) ) {
			$this->data_product['category_ids_da_mexal'] = $this->getCategoryIdByCodeMexal($data['codice_categoria']) ? [$this->getCategoryIdByCodeMexal($data['codice_categoria'])] : [];
		}

		//if ( array_key_exists('composizione', $data) && strlen(trim($data['composizione'])) ) $this->data_product['materiale'] = $data['composizione']; // cambio nome composizione in materiale!!
		
		if ( array_key_exists('tipo_telaio', $data) && strlen(trim($data['tipo_telaio'])) ) $this->data_product['tipo_telaio'] = $data['tipo_telaio'];
		
		
		
		if ( array_key_exists('color', $data) && strlen(trim($data['color'])) ) $this->data_product['color'] = $data['color'];
		if ( array_key_exists('famiglia_colore', $data) && strlen(trim($data['famiglia_colore'])) ) $this->data_product['famiglia_colore'] = $data['famiglia_colore'];
		if ( array_key_exists('calibro', $data) && strlen(trim($data['calibro'])) ) $this->data_product['calibro'] = $data['calibro'];
		if ( array_key_exists('materiale', $data) && strlen(trim($data['materiale'])) ) $this->data_product['materiale'] = $data['materiale'];
		if ( array_key_exists('genere', $data) && strlen(trim($data['genere'])) ) $this->data_product['genere'] = $data['genere'];
		if ( array_key_exists('altezza', $data) && strlen(trim($data['altezza'])) ) $this->data_product['altezza'] = $data['altezza'];
		if ( array_key_exists('composizione_dies', $data) && strlen(trim($data['composizione_dies'])) ) $this->data_product['composizione_dies'] = $data['composizione_dies'];
		if ( array_key_exists('fondello', $data) && strlen(trim($data['fondello'])) ) $this->data_product['fondello'] = $data['fondello'];
		if ( array_key_exists('colpi', $data) && strlen(trim($data['colpi'])) ) $this->data_product['colpi'] = $data['colpi'];
		if ( array_key_exists('stelle', $data) && strlen(trim($data['stelle'])) ) $this->data_product['stelle'] = $data['stelle'];
		if ( array_key_exists('litri', $data) && strlen(trim($data['litri'])) ) $this->data_product['litri'] = $data['litri'];
		if ( array_key_exists('diametro_palle', $data) && strlen(trim($data['diametro_palle'])) ) $this->data_product['diametro_palle'] = $data['diametro_palle'];
		if ( array_key_exists('profilo', $data) && strlen(trim($data['profilo'])) ) $this->data_product['profilo'] = $data['profilo'];
		if ( array_key_exists('nome_palla', $data) && strlen(trim($data['nome_palla'])) ) $this->data_product['nome_palla'] = $data['nome_palla'];
		if ( array_key_exists('scala_taglie', $data) ) $this->data_product['scala_taglie'] = $data['scala_taglie'];
		
		if ( array_key_exists('barcode', $data) ) $this->data_product['barcode'] = $data['barcode'];
		
		if ( array_key_exists('last_update_foto', $data) ) $this->data_product['last_update_foto'] = (int) $data['last_update_foto'];
		
		if ( array_key_exists('qta', $data) ) $this->data_product['qta'] = (int) $data['qta']; else $this->data_product['qta'] = 0;
		if ( array_key_exists('min_sale_qty', $data) ) $this->data_product['min_sale_qty'] = (int) $data['min_sale_qty']; else $this->data_product['min_sale_qty'] = 1;
		$this->data_product['is_in_stock_simple'] = 1; //$qta>0 ? 1 : 0;
		$this->data_product['is_in_stock'] = $this->data_product['qta']>0 ? 1 : 0 ;
		
		$this->data_product['minimal_quantity'] = 1;

		if ( array_key_exists('products_child', $data) ) $this->data_product['products_child'] = $data['products_child'];
		
		// calcolo la qta totale di tutte le taglie attive
		if ( array_key_exists('products_child', $data) ) {
			$this->data_product['tot_qta_products_child'] = 0;
			foreach ( $data['products_child'] as $product_child) {
				if ( $product_child['active'] )
					$this->data_product['tot_qta_products_child'] += $product_child['qta'];
			}
		}

		$relatedLinks = array();
		$upSellLinks = array();
		$crossSellLinks = array();
		if ( array_key_exists('related', $data) ) {
			$relatedLinks = $this->createProductLinks($this->productLinkFactory, 'related', $this->data_product['sku'], $data['related']);
		}
		if (array_key_exists('up_sell', $data)) {
			 $upSellLinks = $this->createProductLinks($this->productLinkFactory, 'upsell', $this->data_product['sku'], $data['up_sell']);
		}
		if (array_key_exists('cross_sell', $data)) {
			    $crossSellLinks = $this->createProductLinks($this->productLinkFactory, 'crosssell', $this->data_product['sku'], $data['cross_sell']);
		}
		if ( array_key_exists('related', $data)
			|| array_key_exists('up_sell', $data)
			|| array_key_exists('cross_sell', $data) ) {
			$this->data_product['linkDataAll'] = array_merge($relatedLinks, $upSellLinks, $crossSellLinks);
		}
				
		return $this->data_product;
	}
	
	public function getAttrSetIdByName($attrSetName)
	{
		if ($attrSetName == 'Default')
			return 4;
		//$this->output['dettaglio_azioni'] .= "#attrSetName:$attrSetName#";
		$attributeSetCollection = $this->attributeSetCollection->create()
		  ->addFieldToSelect('attribute_set_id')
		  ->addFieldToFilter('attribute_set_name', $attrSetName)
		  ->getFirstItem()
		  ->toArray();

       $attribute_set_id = 0;
       if ( is_array($attributeSetCollection) && count($attributeSetCollection) )
        		$attribute_set_id = (int) $attributeSetCollection['attribute_set_id'];
		 if ($attribute_set_id) {
		     //$this->output['dettaglio_azioni'] .= "#TROVATO attribute_set_id:$attribute_set_id#";
		 } else {
		     // Gestisci il caso in cui non si trova l'attribute set name
		     $attribute_set_id = self::ID_ATTRIBUTE_SET_DEFAULT;
		     //$this->output['dettaglio_azioni'] .= "#non TROVATO attribute_set_id PER:$attrSetName#";
		 }
		 //echo "#attribute_set_id:$attribute_set_id#";
		return $attribute_set_id;
	}


	public function registraImportProduct() {
		$mysqli = $this->getMysqliConnection();
		$db = $this->db;
	
		$q = "SELECT * FROM `".$db['name']."`.`".$db['pref']."prodotti_aggiornati` WHERE `entity_id_old` = '".$this->data_product['entity_id_old']."' 
		
		
		ORDER BY `entity_id_old` DESC;"; //OR `email` = '".$mysqli->escape_string($this->data_product['email'])."' 
		
		$results = $mysqli->query($q);
		if ( $row = $results->fetch_assoc() ) { // se è già presente aggiorno
			$id = $row['id'];
			$entity_id_new = is_object($this->prodotto_importato) && $this->prodotto_importato->getId() ? $this->prodotto_importato->getId() : 0;
			$q = "UPDATE `".$db['name']."`.`".$db['pref']."prodotti_aggiornati` SET `aggiornata` = 1, `sku` = '".$mysqli->escape_string($this->data_product['sku'])."', `description` = '".$mysqli->escape_string($this->data_product['description'])."', `short_description` = '".$mysqli->escape_string($this->data_product['short_description'])."', `entity_id_new` = '$entity_id_new' WHERE `".$db['name']."`.`".$db['pref']."prodotti_aggiornati`.`id` = '$id';";
			$mysqli->query($q);
		} else {
			$entity_id_new = is_object($this->prodotto_importato) && $this->prodotto_importato->getId() ? $this->prodotto_importato->getId() : 0;
			$q = "INSERT INTO `".$db['name']."`.`".$db['pref']."prodotti_aggiornati` (`id`, `entity_id_old`, `entity_id_new`, `sku`, `description`, `short_description`, `aggiornata`) VALUES (NULL, '".$this->data_product['entity_id_old']."', '$entity_id_new', '".$mysqli->escape_string($this->data_product['sku'])."', '".$mysqli->escape_string($this->data_product['description'])."', '".$mysqli->escape_string($this->data_product['short_description'])."', '1');";
			$mysqli->query($q);
		}
	}

	public function resetImportProducts() {
		$mysqli = $this->getMysqliConnection();
		$db = $this->db;
		
		// Aggiorna tutte le righe impostando il valore della colonna `aggiornata` a 0
		$q = "UPDATE `" . $db['name'] . "`.`" . $db['pref'] . "prodotti_aggiornati` SET `aggiornata` = 0;";
		
		if ($mysqli->query($q) === TRUE) {
		    return ['success' => true, 'message' => __('Tutti i prodotti importati sono stati resettati.')];
		} else {
		    return ['success' => false, 'message' => __('Errore durante il reset dei prodoti importati: ' . $e->getMessage())];
		}
	}
    
    
	// al momento non richiamo mai perchè non serve
	// restituisce id categoria del magento nuovo
	public function convertiCategoria($id_cat_old) {
		$mysqli = $this->getMysqliConnection();
		$db = $this->db;
		
		$q = "SELECT `id_cat_new` FROM `".$db['name']."`.`".$db['pref']."categorie_importate` WHERE `id_cat_old` = $id_cat_old;";
		$results = $mysqli->query($q);
		
		if ($row = $results->fetch_assoc()) {
			//$row = $results->fetch_assoc();
			return (int) $row['id_cat_new'];
		} else
			return 0;
	}

    /**
     * Get category ID by name.
     *
     * @param string $categoryName
     * @return int
     */
    public function getCategoryIdByName(string $categoryName): int
    {
        $store = $this->storeManagerInterface->getStore();
        //$rootCategoryId = $store->getRootCategoryId();

        // Search for the category by name
        $categoryCollection = $this->categoryFactory->create()->getCollection()
            ->addAttributeToFilter('name', $categoryName)
            //->addAttributeToFilter('parent_id', $rootCategoryId)
            ->setPageSize(1);

        $category = $categoryCollection->getFirstItem();
        if ($category && $category->getId()) {
            return (int) $category->getId(); // Return existing category ID
        }

        return 0; // Return 0 if the category doesn't exist
    }

    public function getCategoryIdByCodeMexal(string $codice_mexal): int
    {
        $store = $this->storeManagerInterface->getStore();
        //$rootCategoryId = $store->getRootCategoryId();

        // Search for the category by name
        $categoryCollection = $this->categoryFactory->create()->getCollection()
            ->addAttributeToFilter('mexal__cod_grp_merc', $codice_mexal)
            //->addAttributeToFilter('parent_id', $rootCategoryId)
            ->setPageSize(1);

        $category = $categoryCollection->getFirstItem();
        if ($category && $category->getId()) {
            return (int) $category->getId(); // Return existing category ID
        }

        return 0; // Return 0 if the category doesn't exist
    }

    /**
     * Create a new category with the given name and disabled status.
     *
     * @param string $categoryName
     * @return int New category ID
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createCategoryByName(string $categoryName, $id_parent = 0): int
    {
    	$this->output['dettaglio_azioni'] .= '## creazione categoria nome: '.$categoryName.' e id_parent: '.$id_parent.' ##';
        $store = $this->storeManagerInterface->getStore();
        if ( $id_parent )
        	$rootCategoryId = $id_parent;
        else
        	$rootCategoryId = $store->getRootCategoryId();

        // Create a new category
        $newCategory = $this->categoryFactory->create();
        $newCategory->setName($categoryName);
        $newCategory->setIsActive(true);
        $newCategory->setParentId($rootCategoryId);
        $newCategory->setStoreId($store->getId());
        //$newCategory->setPath($this->categoryRepository->get($rootCategoryId)->getPath());
		// Generate a unique URL key
		$urlKey = $this->generateUniqueUrlKeyCategory($categoryName, $store->getId());
		$newCategory->setUrlKey($urlKey);

        // Save the new category
        $this->categoryRepository->save($newCategory);

        return (int) $newCategory->getId();
    }

	/**
	 * Check if a URL key already exists for a category.
	 *
	 * @param string $urlKey
	 * @return bool
	 */
	private function isUrlKeyCategoryExists(string $urlKey): bool
	{
		$categoryCollection = $this->categoryFactory->create()->getCollection()
		    ->addAttributeToFilter('url_key', $urlKey)
		    ->setPageSize(1);

		return $categoryCollection->getSize() > 0;
	}

	/////////////////////// INIZIO BLOCCO PER IMPORT CATEGORIE
	// restituisce id del magento vecchio dell'ultima categoria importata
	public function getLastCategoryImportedId() {

		$mysqli = $this->getMysqliConnection();
		$db = $this->db;

		$q = "CREATE TABLE IF NOT EXISTS `".$db['name']."`.`".$db['pref']."categorie_importate` (
		  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
		  `id_cat_old` int(11) NOT NULL,
		  `id_cat_new` int(11) DEFAULT '0',
		  `parent_id_cat_new` int(11) DEFAULT '0',
		  `parent_id_cat_old` int(11) DEFAULT '0',
		  `nome` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
		  `aggiornata` int(11) DEFAULT '0'
		  
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

		$mysqli->query($q);
		
		$q = "SELECT * FROM `".$db['name']."`.`".$db['pref']."categorie_importate` WHERE `aggiornata` = 1 ORDER BY `id_cat_old` DESC;";
		$results = $mysqli->query($q);
		
		if ($row = $results->fetch_assoc()) {
			//$row = $results->fetch_assoc();
			return (int) $row['id_cat_old'];
		} else
			return 0;
	}
	
	// crea o aggiorna la categoria sul magento nuovo
	public function creaAggiornaCategoria($obj_data) {
		if ( !is_object($obj_data) || !$obj_data->entity_id )
			return false;
		
		// tiro fuori i dati
		$id_cat_old = $obj_data->entity_id;
		$parent_id_cat_old = $obj_data->parent_id;
		$parent_id_cat_new = 2;
		

		$mysqli = $this->getMysqliConnection();
		$db = $this->db;

		$q = "CREATE TABLE IF NOT EXISTS `".$db['name']."`.`".$db['pref']."categorie_importate` (
		  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
		  `id_cat_old` int(11) NOT NULL,
		  `id_cat_new` int(11) DEFAULT '0',
		  `parent_id_cat_new` int(11) DEFAULT '0',
		  `parent_id_cat_old` int(11) DEFAULT '0',
		  `nome` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
		  `aggiornata` int(11) DEFAULT '0'
		  
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

		$mysqli->query($q);

		// controllo che in tabella conversioni non vi sia una riga ad una categoria cancellata su magento
		$results = $mysqli->query( "SELECT * FROM `".$db['name']."`.`".$db['pref']."categorie_importate` WHERE `id_cat_old` = '$id_cat_old';" );
		if ( $row = $results->fetch_assoc() ) {
			$test_category = $this->getCategory($row['id_cat_new']);
			if ( !is_object($test_category) || !$test_category->getId() ) {
				//echo '<p>E\' una categoria che porco iddio ha cancellato da magento</p>';
				$q = "DELETE FROM `".$db['name']."`.`".$db['pref']."categorie_importate` WHERE `".$db['name']."`.`".$db['pref']."categorie_importate`.`id_cat_new` = '".$row['id_cat_new']."';";
				$mysqli->query($q);
			} else {
				//echo '<p>categoria buona:'.$test_category->getName().'</p>';
			}
		
		}


		// calcolo il parent id di magento nuovo, rispetto al vecchio
		$results = $mysqli->query( "SELECT * FROM `".$db['name']."`.`".$db['pref']."categorie_importate` WHERE `id_cat_old` = '$parent_id_cat_old';" );
		if ( $row = $results->fetch_assoc() ) {
			$parent_id_cat_new = $row['id_cat_new'];
		}

		
		$results = $mysqli->query( "SELECT * FROM `".$db['name']."`.`".$db['pref']."categorie_importate` WHERE `id_cat_old` = '$id_cat_old';" );
		if ( $row = $results->fetch_assoc() ) {
			$results = $mysqli->query( "SELECT * FROM `".$db['name']."`.`".$db['pref']."categorie_importate` WHERE `id_cat_old` = '$id_cat_old';" );
			
			while ( $row = $results->fetch_assoc() ) {
				// è una categoria già esistente
				//echo "<h1>CATEGORIA OLD ESISTENTE ID#$id_cat_old</h1>";
				$id_cat_new = (int)$row['id_cat_new'];
				//echo "<h1>AGGIORNO NEW ID#$id_cat_new</h1>";
				// aggiornare la categoria su magento
				$category = $this->getCategory($id_cat_new);
				//echo $category->getName().$obj_data->meta_description;
				 try{
					 //$category = $this->categoryFactory->create();
					 $category->setName($obj_data->name);
					 $category->setMetaTitle($obj_data->meta_title);
					 $category->setMetaDescription($obj_data->meta_description);
					 $category->setDescription($obj_data->description);
					 $category->setUrlKey($obj_data->url_key);
					 $category->setIsActive($obj_data->is_active);
					 $category->setDisplayMode($obj_data->display_mode);
					 $category->setIncludeInMenu($obj_data->include_in_menu);
					 $category->setDisplayMode($obj_data->display_mode);
					 $category->setIsAnchor($obj_data->is_anchor); //for active anchor
					 //$category->setStoreId(Mage::app()->getStore()->getId());
					 //$category->setStoreId(0);
					 //$parentCategory = Mage::getModel('catalog/category')->load($parentId);
					 //$category->setPath($parentCategory->getPath());
					 $category->setParentId($parent_id_cat_new);
					 //$category->save();
					 $this->categoryRepository->save($category);
					 
					 
					 $q = "UPDATE `".$db['name']."`.`".$db['pref']."categorie_importate` SET `parent_id_cat_new` = '$parent_id_cat_new', `parent_id_cat_old` = '$parent_id_cat_old', `nome` = '".$mysqli->escape_string($obj_data->name)."', `aggiornata` = '1' WHERE `".$db['name']."`.`".$db['pref']."categorie_importate`.`id_cat_old` = '$id_cat_old';";
					 $mysqli->query($q);
					 //$this->closeMysqliConnection();

				} catch(Exception $e) {
					 print_r($e);
				}
				
				
			}
		

		} else {
			// è una categoria nuova sia su tab categorie_importate che in magento
			//echo "<h1>CATEGORIA NUOVA ID#$id_cat_old</h1>";
			$nome = $mysqli->escape_string($obj_data->name);

			$q = "INSERT INTO `".$db['name']."`.`".$db['pref']."categorie_importate` (`id`, `id_cat_old`, `id_cat_new`, `nome`, `parent_id_cat_old`, `parent_id_cat_new`, `aggiornata`) VALUES (NULL, '$id_cat_old', '0', '$nome', '$parent_id_cat_old', '$parent_id_cat_new', '1');";
			$result = $mysqli->query($q);
			$last_id = $mysqli->insert_id;
			
			 try{
				 $category = $this->categoryFactory->create();
				 $category->setName($obj_data->name);
				$category->setMetaTitle($obj_data->meta_title);
				 $category->setMetaDescription($obj_data->meta_description);
				 $category->setDescription($obj_data->description);
				 $category->setUrlKey($obj_data->url_key);
				 $category->setIsActive($obj_data->is_active);
				 $category->setDisplayMode($obj_data->display_mode);
				 $category->setIncludeInMenu($obj_data->include_in_menu);
				 $category->setDisplayMode($obj_data->display_mode);
				 $category->setIsAnchor($obj_data->is_anchor); //for active anchor
				 //$category->setStoreId(Mage::app()->getStore()->getId());
				 //$parentCategory = Mage::getModel('catalog/category')->load($parentId);
				 //$category->setPath($parentCategory->getPath());
				 $category->setParentId($parent_id_cat_new);
				 //$category->save();
				 $this->categoryRepository->save($category);

			} catch(Exception $e) {
				 print_r($e);
			}
			$q = "UPDATE `".$db['name']."`.`".$db['pref']."categorie_importate` SET `id_cat_new` = '".$category->getId()."' WHERE `".$db['name']."`.`".$db['pref']."categorie_importate`.`id_cat_old` = '$id_cat_old';";
			$result = $mysqli->query($q);
			//$this->closeMysqliConnection();
			$id_cat_new = (int)$category->getId();
		}

		$this->closeMysqliConnection();
		return $id_cat_new;
	}
	
	
	public function getNextCategoryImportId() {
		return $this->getLastCategoryImportedId() + 1;
	}	
	
	/////////////////////// FINE BLOCCO PER IMPORT CATEGORIE

public function deleteCategoriaById($categoriaId)
{
    try {
        /** @var \Magento\Catalog\Model\Category $categoria */
        $categoria = $this->objectManager
            ->create(\Magento\Catalog\Model\Category::class)
            ->load($categoriaId);

        if (!$categoria->getId()) {
            echo "❌ Categoria con ID $categoriaId non trovata.\n";
            return false;
        }

        $nome = $categoria->getName();

        // ❌ Non cancellare root o assegnata a store
        if ($categoriaId == 1 || $categoria->getParentId() == 0) {
            echo "🚫 Categoria ID $categoriaId è root o sistema. Skippata.\n";
            return false;
        }

        // ❌ Controllo se è root di uno store
        $storeManager = $this->objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
        foreach ($storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                if ($group->getRootCategoryId() == $categoriaId) {
                    echo "🚫 Categoria ID $categoriaId è root per uno store. Skippata.\n";
                    return false;
                }
            }
        }

        // 🔁 Elimina figli ricorsivamente
        $children = $categoria->getChildrenCategories();
        foreach ($children as $childCategoria) {
            $this->deleteCategoriaById($childCategoria->getId());
        }

        // 🧹 Rimuove prodotti dalla categoria
        $productIds = $categoria->getProductCollection()->getAllIds();
        if (!empty($productIds)) {
            $categoria->setPostedProducts([]);
            $categoria->setProductLinkData([]);
            $categoria->setProducts([]);
            $categoria->unsetData('products');
            $categoria->save();
            echo "🔗 Rimossi prodotti dalla categoria ID $categoriaId\n";
        }

        // 🗑️ Elimina la categoria
        $categoria->delete();
        echo "✅ Categoria ID $categoriaId ($nome) eliminata con successo.\n";
        return true;

    } catch (\Exception $e) {
        echo "⚠️ Errore nella cancellazione della categoria ID $categoriaId: " . $e->getMessage() . "\n";
        return false;
    }
}


}
?>
