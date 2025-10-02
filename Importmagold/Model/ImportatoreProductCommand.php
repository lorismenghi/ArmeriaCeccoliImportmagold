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

class ImportatoreProductCommand extends Importatore
{
    const DEFAULT_ATTRIBUTE_SET_NAME = 'Default';
    const DEFAULT_TAX_CLASS_ID = 2; // Taxable Goods
    const ID_ATTRIBUTE_SET_DEFAULT = 4;
    
    public $output = ['sincProduct' => '', 'dettaglio_azioni' => ''];
	
	const URL_GET_DATA_PRODUCT = "https://armeriaceccoli.com/script_vari/export_products.php?token=hinet123&from_id=";
	const URL_GET_DATA_CATEGORY = "https://armeriaceccoli.com/script_vari/export_category.php?token=hinet123&from_id=";

	public function __construct() {
		parent::__construct();
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

	public function convDataProduct($data) {
		//echo '<pre>'; print_r($data); echo '</pre>'; //return;
		//$this->data_product = $data;
		$this->data_product = [];
		
			//if ( is_object($this->output_terminale) )
				//$this->output_terminale->writeln("INIZIO convDataProduct GIÀ ESISTENTE! ". var_export($data, true));
		
		$this->data_product['entity_id_old'] = array_key_exists('entity_id', $data) ? $data['entity_id'] : 0;
		$this->data_product['sku'] = $data['sku'];
		//$this->data_product['name'] = !array_key_exists('name', $data) ? $data['sku'] : $data['name'];
		if ( array_key_exists('name', $data) ) $this->data_product['name'] = $data['name'];
		if ( array_key_exists('mastersku', $data) ) $this->data_product['mastersku'] = $data['mastersku'];
		if ( array_key_exists('nome_mexal', $data) ) $this->data_product['nome_mexal'] = $data['nome_mexal'];
		//$this->data_product['description'] = array_key_exists('description', $data) ? $data['description']  : '';
		if ( array_key_exists('description', $data) ) $this->data_product['description'] = $data['description'];
		//$this->data_product['short_description'] = array_key_exists('short_description', $data) ? $data['short_description'] : '';
		if ( array_key_exists('short_description', $data) ) $this->data_product['short_description'] = $data['short_description'];
		if ( array_key_exists('data_modifica', $data) ) $this->data_product['data_modifica'] = $data['data_modifica'];
		$this->data_product['attribute_set_name'] = array_key_exists('attribute_set_name', $data) ? $data['attribute_set_name']  : self::DEFAULT_ATTRIBUTE_SET_NAME;
		$this->data_product['attribute_set_id'] = $this->getAttrSetIdByName($this->data_product['attribute_set_name']);
		if ( array_key_exists('type_id', $data) )
			$this->data_product['type_id'] = $data['type_id'];
		else
			$this->data_product['type_id'] = 'simple';
		if ( array_key_exists('status', $data) ) $this->data_product['status'] = $data['status'];
		$this->data_product['tax_class_id'] = self::DEFAULT_TAX_CLASS_ID;
		$this->data_product['visibility'] = array_key_exists('visibility', $data) ? $data['visibility'] : self::MAGENTO_VISIBILITA_CATALOGO_RICERCA;
		if ( array_key_exists('decreto', $data) ) $this->data_product['decreto'] = $data['decreto'];
		
		// il prezzo non puo essere null lo special price si
		if ( array_key_exists('price', $data) && (float) $data['price'] > 0 ) $this->data_product['price'] = (float) $data['price'];
		//if ( array_key_exists('special_price', $data) && (float) $data['special_price'] > 0 ) $this->data_product['special_price'] = (float) $data['special_price'];
		if ( array_key_exists('special_price', $data) )
			$this->data_product['special_price'] = $data['special_price'] > 0 ? (float) $data['special_price'] : null;
		
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
		if ( array_key_exists('manufacturer', $data) ) $this->data_product['manufacturer'] = $data['manufacturer'];
		
		
		if ( array_key_exists('country', $data) ) $this->data_product['country'] = $data['country'];
		
		if ( array_key_exists('taglia', $data) ) $this->data_product['taglia'] = $data['taglia'];
		
		if ( array_key_exists('ids_user', $data) ) $this->data_product['ids_user'] = $data['ids_user'];

		if ( array_key_exists('stagione', $data) ) $this->data_product['stagione'] = $data['stagione'];
		
		if ( array_key_exists('gender', $data) ) $this->data_product['gender'] = $data['gender'];
	
		if ( array_key_exists('codice_categoria', $data) ) {
			$this->data_product['category_ids_da_mexal'] = $this->getCategoryIdByCodeMexal($data['codice_categoria']) ? [$this->getCategoryIdByCodeMexal($data['codice_categoria'])] : [];
		}

		//if ( array_key_exists('composizione', $data) && strlen(trim($data['composizione'])) ) $this->data_product['materiale'] = $data['composizione']; // cambio nome composizione in materiale!!
		
		if ( array_key_exists('tipo_telaio', $data) ) $this->data_product['tipo_telaio'] = $data['tipo_telaio'];
		if ( array_key_exists('color', $data) ) $this->data_product['color'] = $data['color'];
		if ( array_key_exists('famiglia_colore', $data) ) $this->data_product['famiglia_colore'] = $data['famiglia_colore'];
		if ( array_key_exists('calibro', $data) ) $this->data_product['calibro'] = $data['calibro'];
		if ( array_key_exists('materiale', $data) ) $this->data_product['materiale'] = $data['materiale'];
		if ( array_key_exists('genere', $data) ) $this->data_product['genere'] = $data['genere'];
		if ( array_key_exists('genere_mexal', $data) ) $this->data_product['genere_mexal'] = $data['genere_mexal'];
		if ( array_key_exists('anno', $data) ) $this->data_product['anno'] = $data['anno'];
		if ( array_key_exists('piombo', $data) ) $this->data_product['piombo'] = $data['piombo'];
		if ( array_key_exists('altezza', $data) ) $this->data_product['altezza'] = $data['altezza'];
		if ( array_key_exists('composizione_dies', $data) ) $this->data_product['composizione_dies'] = $data['composizione_dies'];
		if ( array_key_exists('fondello', $data) ) $this->data_product['fondello'] = $data['fondello'];
		if ( array_key_exists('colpi', $data) ) $this->data_product['colpi'] = $data['colpi'];
		if ( array_key_exists('stelle', $data) ) $this->data_product['stelle'] = $data['stelle'];
		if ( array_key_exists('litri', $data) ) $this->data_product['litri'] = $data['litri'];
		if ( array_key_exists('diametro_palle', $data) ) $this->data_product['diametro_palle'] = $data['diametro_palle'];
		if ( array_key_exists('profilo', $data) ) $this->data_product['profilo'] = $data['profilo'];
		if ( array_key_exists('nome_palla', $data) ) $this->data_product['nome_palla'] = $data['nome_palla'];
		if ( array_key_exists('scala_taglie', $data) ) $this->data_product['scala_taglie'] = $data['scala_taglie'];
		
		if ( array_key_exists('barcode', $data) ) $this->data_product['barcode'] = $data['barcode'];
		if ( array_key_exists('last_update_foto', $data) ) $this->data_product['last_update_foto'] = (int) $data['last_update_foto'];
		
		if ( array_key_exists('qta', $data) ) $this->data_product['qta'] = (int) $data['qta'];
		$this->data_product['is_in_stock_simple'] = self::IS_IN_STOCK; //$qta>0 ? 1 : 0;
		if ( array_key_exists('qta', $this->data_product) ) {
			$this->data_product['is_in_stock'] = $this->data_product['qta']>0 ? self::IS_IN_STOCK : self::OUT_OFF_STOCK;
		}
		
		if ( array_key_exists('min_sale_qty', $data) ) 
			$this->data_product['min_sale_qty'] = (int) $data['min_sale_qty'];
		//else 
			//$this->data_product['min_sale_qty'] = 1;
			
		if ( array_key_exists('min_qty', $data) ) 
			$this->data_product['min_qty'] = (int) $data['min_qty'];

		//$this->data_product['minimal_quantity'] = $this->data_product['min_sale_qty']; // questo forse non lo uso nemmeno

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

	// sincronizza importa product su magento
	public function creaAggiornaProdotto($obj_data, $output_terminale = null) {
		$this->resetOutput();
		$data = $this->data_product = [];
		//error_log(print_r($obj_data, true));
		//$this->output = ['sincProduct' => '', 'dettaglio_azioni' => ''];
		if ( is_object($output_terminale) ) {
			$this->output_terminale = $output_terminale;
			$this->output_terminale->writeln(print_r($obj_data, true));
		}
		//error_log('#creaAggiornaProdotto STEP1');
		$data = (array) $obj_data;
		$data = $this->data_product = $this->convDataProduct($data);
		$productExisting = $this->getProductBySku($this->data_product['sku']);
		$data_child = null; // adesso lo imposto nullo non gestisco le varianti
		$boolean_prod_gia_esistente = false;

		// non usato sono tutti 0
		$id_parent = is_null($data_child) ? 0 : $data_child['id_parent_simple']; // se ha un parent è solo una variante taglia
		
		// setto inizialmente per ottimizzazione salvataggio taglie
		$variante_cambiata = false;
		
		if ( is_object($productExisting) ) {
			$this->output['dettaglio_azioni'] .= ' ##productExisting## ';
			if ( is_object($this->output_terminale) )
				$this->output_terminale->writeln("Prodotto {$productExisting->getSku()} GIÀ ESISTENTE!");
			$productM = $productExisting->load($productExisting->getId());
			$boolean_prod_gia_esistente = true;
			//error_log('#creaAggiornaProdotto prodotto esistente');
			
			if ( array_key_exists('name', $data) ) 
				$productM->setName($data['name']);
			
		} else {
			//error_log('#creaAggiornaProdotto prodotto NON esistente');
			$this->output['dettaglio_azioni'] .= ' ##product NOT Existing## ';
			if ( is_object($this->output_terminale) )
				$this->output_terminale->writeln("Prodotto {$this->data_product['sku']} NON TROVATO!");
      		$productM = $this->objectManager->create('\Magento\Catalog\Model\Product');
      		$productM->setId(null);
			$productM->setAttributeSetId( $this->data_product['attribute_set_id'] );
			//$this->output['dettaglio_azioni'] .= "setAttributeSetId:".$this->data_product['attribute_set_id'];
			//echo "###id_parent:$id_parent ##";
			//$this->output['dettaglio_azioni'] .= "##".$this->data_product['sku']."##";
			$productM->setStatus(2); // disabled
			$productM->setVisibility(self::MAGENTO_VISIBILITA_CATALOGO_RICERCA); // lo setto visibile in creazione, poi se e' una taglia verra nascosto
			
			if ( array_key_exists('name', $data) ) 
				$productM->setName($data['name']);
			elseif ( array_key_exists('nome_mexal', $data) )
				$productM->setName($data['nome_mexal']);
			else
				$productM->setName($data['sku']);
							
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
			/* // non gestisco in questa maniera i conf
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
			*/

		} else {
			// PARENT
			$this->output['dettaglio_azioni'] .= ' #prodotto senza PARENT# ';
			//$productM->setVisibility($data['visibility']); // lo setto solo in creazione

			if ( array_key_exists('url_key', $data) && $productM->getUrlKey() != $data['url_key'] ) {
				$productM->setUrlKey($data['url_key']);
			}

			//if ( array_key_exists('name', $data) ) $productM->setName($data['name']);
			if ( array_key_exists('nome_mexal', $data) ) $productM->setNomeMexal($data['nome_mexal']);
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

			if ( array_key_exists('taglia', $data) ) {
				//$this->output['dettaglio_azioni'] .= " #taglia:".$data['taglia']."# ";
				$codice_attributo = 'tg_abbigliamento';
				$this->setIfAttributeDropExist($productM, $codice_attributo, $data['taglia'], $data['attribute_set_id']);
			}

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

			//$this->output['dettaglio_azioni'] .= ' #STEP PRIMA DI QTA# ';
			//$this->productRepository->save($productM); //$productM->save();
			//$this->saveProductWithRetry($productM);
			
			//$this->output['dettaglio_azioni'] .= ' #STEP SAVE PRIMA DI QTA# ';

			
			if (array_key_exists('qta', $data) && array_key_exists('is_in_stock', $data)) {
				// Se NON è configurabile → usa i dati reali di stock
				if ($productM->getTypeId() !== \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
					$stockData = [
						'use_config_manage_stock' => 1,
						'manage_stock' => 1,
						'min_sale_qty' => $data['min_sale_qty'] ?? 1,
						'max_sale_qty' => 10000000,
						'is_in_stock' => $data['is_in_stock'],
						'qty' => $data['qta']
					];

					// Se esiste min_qty, aggiungo anche use_config_min_qty
					if (isset($data['min_qty'])) {
						$stockData['min_qty'] = $data['min_qty'];
						$stockData['use_config_min_qty'] = 0;
					}

					$productM->setStockData($stockData);
					$this->output['dettaglio_azioni'] .= '##aggiorno qta '.$data['qta'].' setStockData ##';
				} else {
					// Se è un configurabile → forzalo sempre in stock
					$productM->setStockData([
						'use_config_manage_stock' => 1,
						'is_in_stock' => 1,
						'qty' => 0
					]);
					$this->output['dettaglio_azioni'] .= '##configurabile forzato in stock##';
				}
			}


		}

		//$productM->save();
		//$this->output['dettaglio_azioni'] .= ' #STEP SAVE DOPO DI QTA# ';
		//return;


		// in ogni caso, sia che sia un parent che una variante
		if ( array_key_exists('manufacturer', $data) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('manufacturer', $data['manufacturer']);
			$productM->setManufacturer($OptionId);
		}
		if ( array_key_exists('country', $data) ) $productM->setCountryOfManufacture($data['country']);
		if ( array_key_exists('ids_user', $data) ) $productM->setIdsUserAmministrazione( implode(',', $ids_user) );
		if ( array_key_exists('stagione', $data) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('stagione', $data['stagione']);
			$productM->setStagione($OptionId);
		}

		if ( array_key_exists('data_modifica', $data) ) {
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
		if ( array_key_exists('materiale', $data) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('materiale', $data['materiale']);
			$productM->setMateriale($OptionId);
		}
		if ( array_key_exists('tipo_telaio', $data) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('tipo_telaio', $data['tipo_telaio']);
			$productM->setTipoTelaio($OptionId);
		}
		if ( array_key_exists('color', $data) ) {
			//$this->output['dettaglio_azioni'] .= " #color:".$data['color']."# ";
			$codice_attributo = 'color';
			$this->setIfAttributeDropExist($productM, $codice_attributo, $data['color'], $data['attribute_set_id']);

			//$codice_attributo = 'colore_abbigliamento';
			//$this->setIfAttributeDropExist($productM, $codice_attributo, $data['color'], $data['attribute_set_id']);
			
			//$codice_attributo = 'colore_accessori';
			//$this->setIfAttributeDropExist($productM, $codice_attributo, $data['color'], $data['attribute_set_id']);
			
			//$codice_attributo = 'colore_armi';
			//$this->setIfAttributeDropExist($productM, $codice_attributo, $data['color'], $data['attribute_set_id']);
			
			//$codice_attributo = 'colore_bossoli';
			//$this->setIfAttributeDropExist($productM, $codice_attributo, $data['color'], $data['attribute_set_id']);
			
			//$codice_attributo = 'colore_calzature';
			//$this->setIfAttributeDropExist($productM, $codice_attributo, $data['color'], $data['attribute_set_id']);
			
		}
		if ( array_key_exists('famiglia_colore', $data) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('famiglia_colore', $data['famiglia_colore']);
			$productM->setFamigliaColore($OptionId);
		}
		if ( array_key_exists('calibro', $data) ) {		
			$codice_attributo = 'calibro';
			$this->setIfAttributeDropExist($productM, $codice_attributo, $data['calibro'], $data['attribute_set_id']);
		}
		
		if ( array_key_exists('genere', $data) ) {
			//$OptionId = $this->getCreateAttributeOptionValueByLabel('genere', $data['genere']);
			//$productM->setGenere($OptionId);
		}
		if ( array_key_exists('genere_mexal', $data) ) {
			//$productM->setGenereMexal($data['genere_mexal']);
		}
		if ( array_key_exists('anno', $data) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('anno', $data['anno']);
			$productM->setAnno($OptionId);
		}
		if ( array_key_exists('piombo', $data) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('piombo', $data['piombo']);
			$productM->setPiombo($OptionId);
		}
		if ( array_key_exists('altezza', $data) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('altezza', $data['altezza']);
			$productM->setAltezza($OptionId);
		}
		if ( array_key_exists('composizione_dies', $data)) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('composizione_dies', $data['composizione_dies']);
			$productM->setComposizioneDies($OptionId);
		}
		if ( array_key_exists('fondello', $data) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('fondello', $data['fondello']);
			$productM->setFondello($OptionId);
		}
		if ( array_key_exists('colpi', $data) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('colpi', $data['colpi']);
			$productM->setColpi($OptionId);
		}
		if ( array_key_exists('stelle', $data) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('stelle', $data['stelle']);
			$productM->setStelle($OptionId);
		}
		if ( array_key_exists('litri', $data) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('litri', $data['litri']);
			$productM->setLitri($OptionId);
		}
		if ( array_key_exists('diametro_palle', $data) ) {
			$OptionId = $this->getCreateAttributeOptionValueByLabel('diametro_palle', $data['diametro_palle']);
			$productM->setDiametroPalle($OptionId);
		}
		if ( array_key_exists('profilo', $data) ) {
			$productM->setProfilo($data['profilo']);
		}
		if ( array_key_exists('nome_palla', $data) ) {
			$productM->setNomePalla($data['nome_palla']);
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
		$this->saveProductWithRetry($productM);

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
		if ( is_object($this->output_terminale) )
			$this->output_terminale->writeln("#OK IMPORT ULTIMATO CON SUCCESSO#");
		
		//error_log('STEP_17');
		//return $this->mandaOutput(); // lo faccio nel file che chiama la classe
		if ( is_object($output_terminale) ) {
		$this->output_terminale->writeln("###############");
		$this->output_terminale->writeln(print_r($data, true));
		$this->output_terminale->writeln("###############");
		}
		if ( array_key_exists('mastersku', $data) && strlen(trim($data['mastersku'])) ) {
			if ( is_object($output_terminale) ) {
			$this->output_terminale->writeln("#STO PER LANCIARE createOrUpdateConfigurableProduct#");
			}
			$this->createOrUpdateConfigurableProduct($productM, trim($data['mastersku']));
		}
	}




}
?>
