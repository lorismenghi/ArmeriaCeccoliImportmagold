<?php
namespace LM\Importmagold\Model;

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
//use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\InventorySalesApi\Api\StockRegistryInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class Importatore
{
	protected $mysqli;
	protected $mysqli_gestionale;
	protected $db; // array con coordinate di accesso
	protected $db_gestionale; // array con coordinate di accesso
	public $URL_GESTIONALE = 'https://admin.xxx.it'; // comunque poi lo prendo dal file env.php di magento
	protected $bootstrap;
	protected $objectManager;
	protected $state; // area code
	protected $resourceConnection;
	protected $filesystem;
	protected $directoryList;

  protected $productFactory;
  protected $productRepository;
  protected $productCollectionFactory;
	
	public $categoryRepository;

	protected $categoryFactory;
	protected $stockStateInterface;
	
	protected $stockRegistry;
	protected $logger;
	
	 protected $eavConfig;
	 protected $productAttributeRepositoryInterface;
	protected $attributeCollectionFactory;
	protected $productResource;

	const ID_WEBSITE_DEFAULT = 1; // il website contiene gli store
	const ID_STORE_DEFAULT = 0;
	const ID_STORE_IT = 1;
	public $websites = [];
	
	const TIME_LIMIT = 175;
	public $output = [];

    const MAGENTO_STATUS_ENABLED = 1;
    const MAGENTO_STATUS_DISABLED = 2;
    
    const MAGENTO_VISIBILITA_NON_VSIBILE = Visibility::VISIBILITY_NOT_VISIBLE; //1;
    const MAGENTO_VISIBILITA_CATALOGO = Visibility::VISIBILITY_IN_CATALOG; //2;
    const MAGENTO_VISIBILITA_RICERCA = Visibility::VISIBILITY_IN_SEARCH; //3;
    const MAGENTO_VISIBILITA_CATALOGO_RICERCA = Visibility::VISIBILITY_BOTH; //4;
    
    const IS_IN_STOCK = 1;
    const OUT_OFF_STOCK = 0;

    public $data_product;
    protected $attributeValues = []; // per ottimizzare salvo le opzioni label->valore dei vari attributi drop down

	protected $setupInterface;
	
    protected $productLinkInterface;
    protected $attributeSetCollection;
    protected $attributeOptionLabelInterfaceFactory;
    protected $attributeOptionInterfaceFactory;
    protected $attributeOptionManagementInterface;
    
    protected $imageProcessor;
    protected $tableFactory;
    protected $categoryLinkRepositoryInterface;
    protected $prodotto_importato;
    
    public $output_terminale; // Symfony\Component\Console\Output\OutputInterface
		
    public function __construct() {
        $this->bootstrap = Bootstrap::create(BP, $_SERVER);
        $this->objectManager = $this->bootstrap->getObjectManager();
        $this->state = $this->objectManager->get('Magento\Framework\App\State');
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $this->websites = [self::ID_WEBSITE_DEFAULT];

        $this->registry = $this->objectManager->get('Magento\Framework\Registry');
        $this->registry->register('isSecureArea', true);

        $this->scopeConfig = $this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
        $this->resourceConnection = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $this->directoryList = $this->objectManager->get('Magento\Framework\Filesystem\DirectoryList');
        $this->filesystem = $this->objectManager->get('Magento\Framework\Filesystem');

        $this->productFactory = $this->objectManager->get('Magento\Catalog\Model\ProductFactory');
        //$this->productRepository = $this->objectManager->get('Magento\Catalog\Model\ProductRepository');
        $this->productRepository = $this->objectManager->create('\Magento\Catalog\Api\ProductRepositoryInterface');
        $this->productCollectionFactory = $this->objectManager->get('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $this->categoryRepository = $this->objectManager->get('Magento\Catalog\Api\CategoryRepositoryInterface');
        $this->categoryFactory = $this->objectManager->get('Magento\Catalog\Model\CategoryFactory');
        $this->storeManagerInterface = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $this->logger = $this->objectManager->get('Psr\Log\LoggerInterface');
        $this->stockRegistry = $this->objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface');
        $this->attributeSetRepository = $this->objectManager->get('Magento\Eav\Api\AttributeSetRepositoryInterface');
        $this->productAttributeRepositoryInterface = $this->objectManager->create('\Magento\Catalog\Api\ProductAttributeRepositoryInterface');
        $this->eavConfig = $this->objectManager->create('\Magento\Eav\Model\Config');
        $this->attributeCollectionFactory = $this->objectManager->create('\Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory');
        $this->productResource = $this->objectManager->create(ProductResource::class);
        $this->configurableProductType = $this->objectManager->create(Configurable::class);

		  
        //set_time_limit(self::TIME_LIMIT);
        $this->output['dettaglio_azioni'] = '';
        $configResult = include BP . '/app/etc/env.php';
        //$this->URL_GESTIONALE = $configResult['db']['connection']['default']['url_gestionale'];
		$this->productLinkFactory = $this->objectManager->get(ProductLinkInterfaceFactory::class);
		
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
		//$this->categoryFactory = $this->objectManager->create('\Magento\Catalog\Api\Data\CategoryInterfaceFactory');

    }

    public function getIdProductBySku($sku)
    {
    	
		$product = $this->getProductBySku($sku);
		if (!is_null($product)) {
			return $product->getId();
		} else {
			return 0;
		}
    }

    /**
     * Get Product by SKU
     *
     * @param string $sku
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
	public function getProductBySku($sku)
	{
		$sku = trim($sku);
		try {
		    //error_log(" getProductBySku:$sku ");
		    if ( strlen($sku) )
		    	$product = $this->productRepository->get($sku); // Carica il prodotto
		    else
				$product = null;

		    return $product;
		} catch (NoSuchEntityException $e) {
		    // Prodotto non trovato
		    error_log(" getProductBySku NON TROVATO:$sku ");
		    return null;
		} catch (\Exception $e) {
		    // Gestisce altre eccezioni generiche
		    error_log(" getProductBySku ERRORE GENERICO per SKU: $sku. Dettagli: " . $e->getMessage());
		    return null;
		}
	}

    /**
     * Ottiene il percorso del folder media
     *
     * @return string
     */
    public function getMediaFolderPath()
    {
        // Ottiene il percorso del folder media
        $mediaPath = $this->directoryList->getPath(DirectoryList::MEDIA);
        return $mediaPath.'/';
    }

	// le configurazioni admin di magento
    public function getConfig($config_path) {
        return $this->scopeConfig->getValue($config_path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

	// ritorna oggetto mysqli già connesso al DB di magento
    public function getMysqliConnection() {
        if (!is_object($this->mysqli)) {
            $configResult = include BP . '/app/etc/env.php';
            $db = [
                'host' => $configResult['db']['connection']['default']['host'],
                'name' => $configResult['db']['connection']['default']['dbname'],
                'user' => $configResult['db']['connection']['default']['username'],
                'pass' => $configResult['db']['connection']['default']['password'],
                'pref' => $configResult['db']['table_prefix']
            ];
            $this->db = $db;

            $mysqli = new \mysqli($db['host'], $db['user'], $db['pass'], $db['name']);
            if ($mysqli->connect_errno) {
                throw new LocalizedException(__('Failed to connect to MySQL: (%1) %2', $mysqli->connect_errno, $mysqli->connect_error));
            } else {
                $this->mysqli = $mysqli;
            }
        }
        return $this->mysqli;
    }
    
    
	// ritorna oggetto mysqli già connesso al DB del gestionale
    public function getMysqliConnectionGestionale() {
        if (!is_object($this->mysqli_gestionale)) {
            $configResult = include BP . '/app/etc/env.php';
            $db = [
                'host' => $configResult['db']['connection']['default']['host_gestionale'],
                'name' => $configResult['db']['connection']['default']['dbname_gestionale'],
                'user' => $configResult['db']['connection']['default']['username_gestionale'],
                'pass' => $configResult['db']['connection']['default']['password_gestionale']
            ];
            $this->db_gestionale = $db;

            $mysqli = new \mysqli($db['host'], $db['user'], $db['pass'], $db['name']);
            if ($mysqli->connect_errno) {
                throw new LocalizedException(__('Failed to connect to MySQL: (%1) %2', $mysqli->connect_errno, $mysqli->connect_error));
            } else {
                $this->mysqli_gestionale = $mysqli;
            }
        }
        return $this->mysqli_gestionale;
    }
    
	// chiude la connessione mysqli
	public function closeMysqliConnection() {
		if ( !is_null($this->mysqli) ) {
			mysqli_close($this->mysqli);
			$this->mysqli = null;
			return true;
		} else
			return false;
	}

	// chiude la connessione mysqli_gestionale
	public function closeMysqliConnectionGestionale() {
		if ( !is_null($this->mysqli_gestionale) ) {
			mysqli_close($this->mysqli_gestionale);
			$this->mysqli_gestionale = null;
			return true;
		} else
			return false;
	}

	public function mandaOutput() {
		echo json_encode( $this->output );
	}

	public static function convertiWebsiteId($id) {
		if ( is_null($id) || $id == 'null' )
			return null;
		$id = (int) $id;
		switch ($id) {
			case 1: // B2C IT
				$new_id = 1;
				break;
			default:
				$new_id = self::ID_WEBSITE_DEFAULT;
		}
		return (int) $new_id;
	}	

	public static function convertiStoreId($id) {
		if ( is_null($id) || $id == 'null' )
			return null;
		$id = (int) $id;
		switch ($id) {
			case 0: // Default Store View non so perche' e' 0
				$new_id = self::ID_WEBSITE_DEFAULT;
				break;
			case 1: // B2C IT
				$new_id = self::ID_STORE_IT;
				break;
			default:
				$new_id = self::ID_WEBSITE_DEFAULT;
		}
		return (int) $new_id;
	}	

	public function getCategory($categoryId)
	{
		if ( !$categoryId )
			return false;
		 $category = $this->categoryRepository->get($categoryId, $this->storeManagerInterface->getStore()->getId());
		 return $category;
	}

	public static function getFileLog() {
		return dirname(__FILE__) . '/log_importatore.txt';
	}

	public static function scriviFileLog($txt) {
		$log_filename = self::getFileLog();
		if (!file_exists($log_filename))
		{
			fopen($log_filename, "w") or die("Unable to open file!");
		}
		file_put_contents($log_filename, $txt, FILE_APPEND | LOCK_EX);
	}

	// modifico restituendo semplicemente il path assoluto seguito da quello relativo
    public function getFilePathImgProductM1($filename, $newName=null)
    {
    	//$filepath_relativo = $filename;
    	$url_cruscotto = $this->getMediaFolderPath().'catalogM1/product'.$filename; // non è più un url
    	//$filename = basename($filename).PHP_EOL;
    	$filename = basename($filename);
    	error_log("filename originale: $url_cruscotto");
    	//echo "@@@@$filename@@@";
    	//return array('filepath' => $filepath, 'filepath_relativo' => $filepath_relativo);
		try {
		    // ora non è più un nome ma solo un id numerico
		    //$url_cruscotto = $this->URL_GESTIONALE.'/images/file?id=' . $filename.'&watermark=1';
		    //echo $url_cruscotto = $this->URL_GESTIONALE.'/images/file?id=' . $filename;

		    //$dir = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		    //$dir = $this->objectManager->get('Magento\Framework\App\Filesystem\DirectoryList');
		    /*$base = $dir->getStore()
		            ->getBasePath(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);*/
		    $base = $this->getMediaFolderPath(); //$dir->getPath(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

		    if($newName){
		        //$filename = $newName;
		    }

		    $filepath = $base . 'import/' . $filename; //path for temp storage folder: ./media/import/
		    $filepath_relativo = '../media/import/' . $filename;

			if ( !file_exists($base . '/import/') ) {
				mkdir($base . '/import/', 0777, true);
			}

			//if ( !file_exists($base . '/import/temporaneo/') ) {
				//mkdir($base . '/import/temporaneo/', 0777, true);
			//}
		    //$filepath_temporaneo = $base . '/import/temporaneo/' . $filename;
			

		    //file_put_contents($filepath_temporaneo, $img_cruscotto); // lo salvo temporanemante solo per capire che tipo di foto è
			if ($this->isAccessible($url_cruscotto)) {
		    	$img_cruscotto = file_get_contents($url_cruscotto);
		    	file_put_contents($filepath, $img_cruscotto);
		    	
				$image_type = exif_imagetype($filepath);
				//error_log("IMAGE TYPE:$image_type");
				if ($image_type == IMAGETYPE_GIF) {
				    //$filepath .= '.gif';
				    //$filepath_relativo .= '.gif';
				} elseif ($image_type == IMAGETYPE_JPEG) {
				    //$filepath .= '.jpg';
				    //$filepath_relativo .= '.jpg';
				} elseif ($image_type == IMAGETYPE_PNG) {
				    //$filepath .= '.png';
				    //$filepath_relativo .= '.png';
				} elseif ($image_type == IMAGETYPE_PSD) {
				    //$filepath .= '.psd';
				    //$filepath_relativo .= '.psd';
				} elseif ($image_type == IMAGETYPE_BMP) {
				    //$filepath .= '.bmp';
				    //$filepath_relativo .= '.bmp';
				} elseif ($image_type == IMAGETYPE_WEBP) {
					//$filepath .= '.webp';
					//$filepath_relativo .= '.webp';
					//return array();
				} else {
				    return array();
				}
		    	
		    	
		    } else {
				return array();
			}

		    
			//echo "####@@@@$filepath@@@@@#####";
		    if (!file_exists($filepath))
		        return array();
		    //else
		    	//echo "Il file $filepath esiste!";
		    //copy($url_cruscotto, $filepath);
			 //$this->copyImageToMedia($filepath);
		    return array('filepath' => $filepath, 'filepath_relativo' => $filepath_relativo);
		} catch(Exception $ex) {
			return array();
		}
    }

	function isAccessible($path)
	{
		// Controlla se il path è un URL
		if (filter_var($path, FILTER_VALIDATE_URL)) {
		    // Verifica l'accessibilità dell'URL con cURL
		    $ch = curl_init($path);
		    curl_setopt($ch, CURLOPT_NOBODY, true);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		    curl_exec($ch);
		    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		    curl_close($ch);

		    return $httpCode === 200; // Codice HTTP 200 indica che l'URL è accessibile
		} else {
		    // Verifica file locale
		    return file_exists($path) && is_readable($path);
		}
	}

    function copyImageToMedia($imagePath) {
    	$mediaDirectory = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $mediaPath = 'catalog/product/' . basename($imagePath);
        $mediaDirectory->copyFile($imagePath, $mediaPath);
        return $mediaPath;
    }

    public function getCreateAttributeOptionValueByLabel($attributeCode, $label)
    {
        if ( is_null($label) || strlen(trim($label)) == 0) {
            //throw new \Magento\Framework\Exception\LocalizedException( __('Label for %1 must not be empty.', $attributeCode) );
            return '';
        }

		// Assicurati che la label sia sempre trattata come stringa
		//$label = (string)trim($label);
    	$label = $this->sanitizeLabel((string)trim($label));

		//error_log( "---------$attributeCode, $label-----------");
		
		// fix database sbagattato
		//if ( $attributeCode == 'calibro_armi' && $label == '200' )
			//$label = '200mm';
		

        // Does it already exist?
        $optionId = $this->getOptionId($attributeCode, $label);

		//echo "getCreateAttributeOptionValueByLabel $attributeCode, $label###";

        if (!$optionId) {
            // If no, add it.
            error_log(" AGGIUNGO opzione $attributeCode, $label ");

            $optionLabelFactory = $this->objectManager->create('\Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory');
            $optionLabel = $optionLabelFactory->create();
            $optionLabel->setStoreId(0);
            $optionLabel->setLabel($label);

            $optionFactory = $this->objectManager->create('\Magento\Eav\Api\Data\AttributeOptionInterfaceFactory');
            $option = $optionFactory->create();
            $option->setLabel($label); //$option->setLabel($optionLabel);
            $option->setStoreLabels([$optionLabel]);
            $option->setSortOrder(0);
            $option->setIsDefault(false);

            $attributeOptionManagement = $this->objectManager->create('\Magento\Eav\Api\AttributeOptionManagementInterface');
            $attributeOptionManagement->add(
                \Magento\Catalog\Model\Product::ENTITY,
                $this->getAttribute($attributeCode)->getAttributeId(),
                $option
            );

            // Get the inserted ID. Should be returned from the installer, but it isn't.
            $optionId = $this->getOptionId($attributeCode, $label);
        }
        
        
        return $optionId;
    }

    /**
     * Find the ID of an option matching $label, if any.
     *
     * @param string $attributeCode Attribute code
     * @param string $label Label to find
     * @param bool $force If true, will fetch the options even if they're already cached.
     * @return int|false
     */
     /*
    public function getOptionId($attributeCode, $label, $force = false)
    {
    		//echo "\n<p>attributeCode:$attributeCode label:$label force:$force</p>\n";
        $attribute = $this->getAttribute($attributeCode);
        $attribute->setStoreId(0);
        
        

        // Build option array if necessary
        if ($force === true || !isset($attributeValues[$attribute->getAttributeId()])) {
            $attributeValues[$attribute->getAttributeId()] = [];

            // We have to generate a new sourceModel instance each time through to prevent it from
            // referencing its _options cache. No other way to get it to pick up newly-added values.

            $tableFactory = $this->objectManager->create('\Magento\Eav\Model\Entity\Attribute\Source\TableFactory');
            $sourceModel = $tableFactory->create();
            $sourceModel->setAttribute($attribute);

            foreach ($sourceModel->getAllOptions() as $option) {            
                $attributeValues[$attribute->getAttributeId()][$option['label']] = $option['value'];
            }
        }
        
        // Return option ID if exists
        if (isset($attributeValues[$attribute->getAttributeId()][$label])) {
            return $attributeValues[$attribute->getAttributeId()][$label];
        }

        // Return false if does not exist
        return false;
    }
	*/

public function getOptionId($attributeCode, $label, $force = false)
{
    // Assicurati che il label sia trattato come stringa
    $label = (string)$label;


    $attribute = $this->getAttribute($attributeCode);
    $attribute->setStoreId(self::ID_STORE_DEFAULT);
    $attributeValues = [];

    // Costruisci l'array delle opzioni se necessario
    if ($force === true || !isset($attributeValues[$attribute->getAttributeId()])) {
        $attributeValues[$attribute->getAttributeId()] = [];


        $tableFactory = $this->objectManager->create('\Magento\Eav\Model\Entity\Attribute\Source\TableFactory');
        $sourceModel = $tableFactory->create();
        $sourceModel->setAttribute($attribute);

		// Recupera le opzioni dall'Admin Store (store ID 0)
        foreach ($sourceModel->getAllOptions(false) as $option) {
            $attributeValues[$attribute->getAttributeId()][(string)$option['label']] = $option['value'];
        }
    }

	foreach ($attributeValues[$attribute->getAttributeId()] as $existingLabel => $value) {
		//error_log(" existingLabel:$existingLabel === label:$label ");
		if (strtolower((string)$existingLabel) === strtolower((string)$label)) {
			//error_log(" TROVATO! existingLabel:$existingLabel === label:$label RETURN value:$value ");
		    return $value;
		}
	}

    // Se non esiste, ritorna false
    return false;
}


	
	
    /**
     * Get attribute by code.
     *
     * @param string $attributeCode
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface
     */
    public function getAttribute($attributeCode)
    {
        $attributeRepository = $this->productAttributeRepositoryInterface;
        return $attributeRepository->get($attributeCode);
    }

    public function getAllAttributeOptions($attributeCode)
    {
        $attributes = $this->productAttributeRepositoryInterface->get(\Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE, $attributeCode);
        $options = $attributes->getSource()->getAllOptions(false);
        return $options;
    }

    // Funzione per creare collegamenti prodotto (related)
    function createProductLinks($productLinkFactory, $type, $productSku, $linkedSkus) {
        $links = [];
        foreach ($linkedSkus as $linkedSku) {
        
        	if ( $this->getIdProductBySku($linkedSku) ) {
		        $link = $productLinkFactory->create();
		        $link->setSku($productSku);
		        $link->setLinkedProductSku($linkedSku);
		        $link->setLinkType($type);
		        $links[] = $link;
            }
        }
        return $links;
    }

	public function setAttributeConfName ($AttributeSetName, $OptionId, $product) {
			switch ( trim($AttributeSetName) ) {
					//case 'Bike';	$product->setTagliaTelaio($OptionId);	break;
					//case 'Telai';	$product->setTagliaTelaio($OptionId);	break;
					//case 'Selle';	$product->setTagliaSelle($OptionId);	break;
					//case 'Scarpe';	$product->setTagliaScarpe($OptionId);	break;
					//case 'Ruote';	$product->setTagliaRuote($OptionId);	break;
					//case 'Componenti';	$product->setTagliaAccessori($OptionId);	break;
					//case 'Caschi';	$product->setTagliaDefault($OptionId);	break;
					//case 'Abbigliamento';	$product->setTagliaDefault($OptionId);	break;
					//case 'Default';	$product->setTagliaDefault($OptionId);	break;
					default;	$product->setTaglia($OptionId);	break;
			}
			return $product;
	}

    public function getStockItemByProduct($product)
    {
        // Usa lo SKU del prodotto, non l'ID
        $sku = $product->getSku();
        
        // Ottieni lo StockItem usando lo SKU
        $stockItem = $this->stockRegistry->getStockItemBySku($sku);

        return $stockItem;
    }

    public function getQtyByProduct($product)
    {
        $_productStock = $this->getStockItemByProduct($product);
        
        // Ritorna la quantità disponibile
        return $_productStock->getQty();
    }

    public function getMinSaleQtyByProduct($product)
    {
        $_productStock = $this->getStockItemByProduct($product);
        return $_productStock->getMinSaleQty();
    }


	public function generateUniqueUrlKey($name)
	{
		return strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($name))) . '-' . uniqid();
	}

/*
	// Method to generate unique URL key
	public function generateUniqueUrlKey($sku)
	{
		$urlKey = strtolower(preg_replace('/[^a-z0-9]+/', '-', $sku));
		$counter = 1;
		$finalUrlKey = $urlKey;
		
		while ($this->doesUrlKeyExist($finalUrlKey)) {
		    $finalUrlKey = $urlKey . '-' . $counter++;
		}

		return $finalUrlKey;
	}

	// Check if URL key exists
	public function doesUrlKeyExist($urlKey)
	{
		$productCollection = $this->objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\Collection');
		//$this->productCollectionFactory->addFieldToFilter('url_key', $urlKey);
		$productCollection->addFieldToFilter('url_key', $urlKey);
		return ($productCollection->getSize() > 0);
	}
*/

	public function getAssociatedSimpleProducts($skuConfigurabile)
	{
		/** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
		$collection = $this->productCollectionFactory->create();
		$collection->addAttributeToSelect('*')
		    ->addFieldToFilter('type_id', 'simple')
		    ->addFieldToFilter('sku', ['like' => $skuConfigurabile . '%']); // esempio: tutti i SKU figli

		return $collection->getItems();
	}
	
    public function isAttributeInAttributeSet($attributeSetId, $attributeCode)
    {
        // Ottieni l'attributo specifico per l'entità prodotto (o altra entità)
        $attribute = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $attributeCode);
		//$this->output['dettaglio_azioni'] .= " #attributeSetId $attributeSetId # ";
        // Verifica che l'attributo sia valido
        if (!$attribute || !$attribute->getId()) {
        	//$this->output['dettaglio_azioni'] .= " #Attributo $attributeCode non trovato # ";
            return false; // Attributo non trovato
        }

        // Ottieni la collezione degli attributi associati al set di attributi
		$attributeCollection = $this->attributeCollectionFactory->create()
			->setAttributeSetFilter($attributeSetId)
			->addFieldToFilter('main_table.attribute_id', $attribute->getId());

        // Se troviamo un risultato, significa che l'attributo è associato al set
        return $attributeCollection->getSize() > 0;
    }

	public function setIfAttributeDropExist($product, $codice_attributo, $valore_opzione, $attribute_set_id) {
		//$this->output['dettaglio_azioni'] .= " ## ";
		if ( $this->isAttributeInAttributeSet($attribute_set_id, $codice_attributo) ) {
			//$this->output['dettaglio_azioni'] .= " #TROVATO $attribute_set_id $codice_attributo $valore_opzione# ";
			$optionId = $this->getCreateAttributeOptionValueByLabel($codice_attributo, $valore_opzione);
			if ($optionId)
			$product->setData($codice_attributo, $optionId);
		}
		return $product;
	}

    /**
     * Salva un prodotto con gestione dei lock e retry
     *
     * @param \Magento\Catalog\Model\Product &$product Il prodotto da salvare (per riferimento)
     * @return void
     * @throws \Exception
     */
    public function saveProductWithRetry(&$product)
    {
    
    	return $this->productRepository->save($product);
    	
    
        $maxRetries = 5; // Numero massimo di tentativi
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            $connection = $this->resourceConnection->getConnection();

            try {
                // Inizia la transazione
                $connection->beginTransaction();

                // Salva il prodotto usando il ResourceModel
                $this->productResource->save($product);

                // Conferma la transazione
                $connection->commit();

                // Uscita dal ciclo se il salvataggio è riuscito
                return;
            } catch (\Exception $e) {
                // Rollback della transazione in caso di errore
                $connection->rollBack();

                if (strpos($e->getMessage(), 'lock wait timeout') !== false) {
                    $retryCount++;
                    echo "Tentativo $retryCount: Lock wait timeout, ritento...\n";

                    // Attendi prima di ritentare
                    sleep(1);
                } else {
                    // Rilancia l'eccezione per errori diversi dal lock timeout
                    throw $e;
                }
            }
        }

        // Se tutti i tentativi falliscono, lancia un'eccezione
        throw new \Exception("Errore: Non è stato possibile salvare il prodotto dopo $maxRetries tentativi.");
    }
    
	public static function filtraNome($txt, $allow_empty = true) {
		$txt = trim(preg_replace("/[^ \w]+/", "", $txt)); // toglie asterischi e caratteracci
		//$txt = trim($txt);
		if ($allow_empty)
			return $txt;
		else
			return strlen($txt) ? $txt : 'null';
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

	public function generateUniqueUrlKeyCategory(string $categoryName, int $storeId): string
	{
		$reserved = ['admin', 'soap', 'rest', 'graphql', 'standard', 'amministrazione2021'];

		$urlKey = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($categoryName)));
		$urlKey = trim($urlKey, '-');

		if (empty($urlKey)) {
		    $urlKey = 'category-' . time();
		}

		if (in_array($urlKey, $reserved)) {
		    $urlKey = 'categoria-' . $urlKey;
		}

		$originalUrlKey = $urlKey;
		$index = 1;

		// 🔁 verifica sia url_key che request_path
		while (
		    $this->isUrlKeyCategoryExists($urlKey) ||
		    $this->urlRewriteExists($urlKey, $storeId)
		) {
		    $urlKey = $originalUrlKey . '-' . $index;
		    $index++;
		}

		return $urlKey;
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


	// Metodo di pulizia
	public function cleanUp()
	{
		//$this->objectManager->clear();
		return $this->objectManager->get(\Magento\Framework\App\ResourceConnection::class)->closeConnection();
	}
	
	private function sanitizeLabel($label)
	{
		// Normalizza la codifica a UTF-8 per evitare caratteri strani
		$label = mb_convert_encoding($label, 'UTF-8', 'UTF-8');

		// Rimuovi caratteri di controllo non stampabili (eccetto tab, newline e carriage return)
		$label = preg_replace('/[^\P{C}\t\n\r]+/u', '', $label);

		// Elimina spazi iniziali e finali
		$label = trim($label);

		// Sostituisci eventuali spazi multipli con uno singolo
		$label = preg_replace('/\s+/', ' ', $label);

		return $label;
	}
	
	
    /**
     * Salva un prodotto con gestione dei lock e retry
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface &$product Il prodotto da salvare (per riferimento)
     * @return void
     * @throws \Exception

    public function saveProductWithRetry(&$product)
    {
        $maxRetries = 5;
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            // Ottieni la connessione
            $connection = $this->resourceConnection->getConnection();
            try {
                // Inizia la transazione
                $connection->beginTransaction();

                // Salva il prodotto
                $this->productRepository->save($product);

                // Conferma la transazione
                $connection->commit();

                // Uscita dal ciclo se l'operazione è riuscita
                return;
            } catch (\Exception $e) {
                // Rollback della transazione in caso di errore
                $connection->rollBack();

                // Controlla se l'errore è dovuto a un lock
                if (strpos($e->getMessage(), 'lock wait timeout') !== false) {
                    $retryCount++;
                    // Log o messaggio di debug
                    echo "Tentativo $retryCount: Lock wait timeout, ritento...\n";

                    // Chiudi la connessione per rilasciare eventuali lock
                    $connection->closeConnection();

                    // Attendi prima di ritentare
                    sleep(1);
                } else {
                    // Rilancia l'eccezione per errori diversi dal lock timeout
                    throw $e;
                }
            } finally {
                // Riapri la connessione per il prossimo tentativo
                $connection = $this->resourceConnection->getConnection();
            }
        }

        // Se tutti i tentativi falliscono, lancia un'eccezione
        throw new \Exception("Errore: Non è stato possibile salvare il prodotto dopo $maxRetries tentativi.");
    }
     */

    public function resetOutput() {
        $this->output = ['sincProduct' => '', 'dettaglio_azioni' => ''];
    }

	private function resetConfigurableProduct($product)
	{
		if (!$product || !$product->getId()) {
		    return null;
		}

		$sku = $product->getSku();

		try {
		    // Reload completo e pulito dal repository
		    $freshProduct = $this->productRepository->getById($product->getId(), false, 0, true);

		    // Pulisce eventuali dati in memoria
		    $freshProduct->unsetData('associated_product_ids');
		    $freshProduct->unsetData('media_gallery_entries'); // se vuoi rigenerare immagini

		    // Imposta store neutro
		    $freshProduct->setStoreId(0);

		    error_log("[RESET_CONFIG] Prodotto configurabile $sku ricaricato e ripulito.");

		    return $freshProduct;
		} catch (\Exception $e) {
		    error_log("[RESET_CONFIG][ERRORE] Impossibile resettare prodotto $sku: " . $e->getMessage());
		    return $product; // fallback: restituisce comunque il vecchio oggetto
		}
	}

	public function createOrUpdateConfigurableProduct($simpleProduct, $skuConfigurabile)
	{
		// Verifica se esiste già il configurabile
		$configurableProduct = $this->getProductBySku($skuConfigurabile);
		$preserveName = null;

		// 🔴 Se esiste ed è un semplice, non possiamo procedere
		if ($configurableProduct && $configurableProduct->getId()) {
			if ($configurableProduct->getTypeId() !== \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
				// 🔸 Salva il name prima di cancellare
				$preserveName = $configurableProduct->getName();

				try {
				    $this->productRepository->delete($configurableProduct);
				    $this->logger->warning("⚠️ Prodotto semplice con SKU '$skuConfigurabile' eliminato per fare spazio al configurabile.");
				} catch (\Exception $e) {
				    $this->logger->error("❌ Errore nell'eliminazione del prodotto semplice '$skuConfigurabile': " . $e->getMessage());
				    return null;
				}

				$configurableProduct = null;
			} else {
				$configurableProduct = $this->resetConfigurableProduct($configurableProduct);
			}
		}

		if ($configurableProduct && $configurableProduct->getId()) {
			$configurableProduct = $this->resetConfigurableProduct($configurableProduct);
		}

		$simpleProduct->load($simpleProduct->getId());
		if (!$configurableProduct) {
		    // Clona il prodotto semplice
		    /** @var \Magento\Catalog\Model\Product $configurableProduct */
		    $configurableProduct = $this->productFactory->create();
		    $configurableProduct->setTypeId(\Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE);
		    $configurableProduct->setAttributeSetId($simpleProduct->getAttributeSetId());
		    $configurableProduct->setWebsiteIds($simpleProduct->getWebsiteIds());
		    $configurableProduct->setStoreId($simpleProduct->getStoreId());

		    // Copia attributi base
		    //$configurableProduct->setName($simpleProduct->getName());
		    
			if ($preserveName) {
				$configurableProduct->setName($preserveName);
			} else {
				$productName = trim($simpleProduct->getName());
				if (empty($productName)) {
					$productName = $skuConfigurabile;
				}
				$configurableProduct->setName($productName);
			}

		    $configurableProduct->setUrlKey($this->generateUniqueUrlKey($skuConfigurabile));
		    $configurableProduct->setDescription($simpleProduct->getDescription());
		    $configurableProduct->setShortDescription($simpleProduct->getShortDescription());
		    $configurableProduct->setTaxClassId($simpleProduct->getTaxClassId());
		    $configurableProduct->setVisibility(self::MAGENTO_VISIBILITA_CATALOGO_RICERCA);
		    $configurableProduct->setStatus(self::MAGENTO_STATUS_ENABLED);
		    $configurableProduct->setSku($skuConfigurabile);
		    $configurableProduct->setPrice($simpleProduct->getPrice());
		    $configurableProduct->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1, 'qty' => 0]);

			// Copia categorie
			$configurableProduct->setCategoryIds($simpleProduct->getCategoryIds());

		    // Clona attributi custom
		    foreach ($simpleProduct->getCustomAttributes() as $attr) {
		        $code = $attr->getAttributeCode();
		        $value = $attr->getValue();
		        if (!in_array($code, ['sku', 'url_key', 'entity_id'])) {
		            $configurableProduct->setData($code, $value);
		        }
		    }

		    // Salva il prodotto configurabile
		    $this->saveProductWithRetry($configurableProduct);
		    $this->copyFirstAvailableImage($simpleProduct, $configurableProduct);
		    $this->saveProductWithRetry($configurableProduct);
		} else {
			$this->copyFirstAvailableImage($simpleProduct, $configurableProduct);
		}

		// Associa il semplice al configurabile
		$associatedSkus = [$simpleProduct->getSku()];
		//$configurableAttributesData = $this->getConfigurableAttributesForProduct([$simpleProduct], $configurableProduct);
		
		$allSimpleProducts = $this->getAssociatedSimpleProducts($skuConfigurabile);

		// Mappa combinazioni uniche
		$uniqueCombinations = [];

		foreach ($allSimpleProducts as $prod) {
			$values = [];
			$color = $prod->getData('color');
			$tg = $prod->getData('tg_abbigliamento');
			$diametro = $prod->getData('diametro_palle');
			$calibro = $prod->getData('calibro');

			if ($color !== null) $values[] = $color;
			if ($tg !== null) $values[] = $tg;
			if ($diametro !== null) $values[] = $diametro;
			if ($calibro !== null) $values[] = $calibro;

			$key = implode('-', $values);

			if (!isset($uniqueCombinations[$key])) {
				$uniqueCombinations[$key] = $prod;
			}
			
		}

		// Ora aggiungiamo anche il nuovo semplice, se serve
		$valuesNew = [];

		$colorNew = $simpleProduct->getData('color');
		$tgNew = $simpleProduct->getData('tg_abbigliamento');
		$diametroNew = $simpleProduct->getData('diametro_palle');
		$calibroNew = $simpleProduct->getData('calibro');

		if ($colorNew !== null) $valuesNew[] = $colorNew;
		if ($tgNew !== null) $valuesNew[] = $tgNew;
		if ($diametroNew !== null) $valuesNew[] = $diametroNew;
		if ($calibroNew !== null) $valuesNew[] = $calibroNew;

		$keyNew = implode('-', $valuesNew);

		if (!isset($uniqueCombinations[$keyNew])) {
			$uniqueCombinations[$keyNew] = $simpleProduct;
		}

		// Infine aggiorniamo la lista dei semplici unici
		$allSimpleProducts = array_values($uniqueCombinations);

		$configurableAttributesData = $this->getConfigurableAttributesForProduct($allSimpleProducts, $configurableProduct);


		/** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $typeInstance */
		$typeInstance = $this->objectManager->get(\Magento\ConfigurableProduct\Model\Product\Type\Configurable::class);
		
		$this->validateConfigurableAttributesData($allSimpleProducts, $configurableAttributesData);
		$typeInstance->setUsedProductAttributeIds($configurableAttributesData['ids'], $configurableProduct);
		
		$configurableProduct->setNewVariationsAttributeSetId($simpleProduct->getAttributeSetId());
		
		//$configurableProduct->setAssociatedProductIds([$simpleProduct->getId()]);
		//$configurableProduct->setAssocopyFirstAvailableImageciatedProductIds(array_map(function($p) { return $p->getId(); }, $allSimpleProducts));
		$existingAssociates = $configurableProduct->getTypeInstance()->getUsedProducts($configurableProduct);
		$existingAssociateIds = array_map(function ($p) {
			return $p->getId();
		}, $existingAssociates);

		// Aggiunge solo se non già presente
		$newId = $simpleProduct->getId();
		if (!in_array($newId, $existingAssociateIds)) {
			$existingAssociateIds[] = $newId;
		}

		$configurableProduct->unsetData('associated_product_ids');
		$configurableProduct->setAssociatedProductIds($existingAssociateIds);



		$configurableProduct->setCanSaveConfigurableAttributes(true);
		$configurableProduct->setConfigurableAttributesData($configurableAttributesData['data']);

		// Salva di nuovo con le associazioni
		$this->saveProductWithRetry($configurableProduct);

		// ✅ Rendi il prodotto semplice "non visibile individualmente"
		$simpleProduct->setVisibility(self::MAGENTO_VISIBILITA_NON_VSIBILE);
		$this->saveProductWithRetry($simpleProduct);
		
		$this->logger->info("✅ Configurabile {$configurableProduct->getSku()} aggiornato con figli: " . implode(', ', $existingAssociateIds));


		return $configurableProduct;
	}


	public function OLD_createOrUpdateConfigurableProduct($simpleProduct, $skuConfigurabile)
	{
		// Verifica se esiste già il configurabile
		$configurableProduct = $this->getProductBySku($skuConfigurabile);

		// 🔴 Se esiste ed è un semplice, non possiamo procedere
		if ($configurableProduct && $configurableProduct->getId()) {
			if ($configurableProduct->getTypeId() !== \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
				// Opzionale: loggare un warning
				$this->logger->warning("❌ SKU '$skuConfigurabile' già esistente come semplice. Salto la creazione del configurabile.");
				return null;
			}

			// Altrimenti lo resettiamo per riutilizzarlo
			$configurableProduct = $this->resetConfigurableProduct($configurableProduct);
		}

		if ($configurableProduct && $configurableProduct->getId()) {
			$configurableProduct = $this->resetConfigurableProduct($configurableProduct);
		}

		$simpleProduct->load($simpleProduct->getId());
		if (!$configurableProduct) {
		    // Clona il prodotto semplice
		    /** @var \Magento\Catalog\Model\Product $configurableProduct */
		    $configurableProduct = $this->productFactory->create();
		    $configurableProduct->setTypeId(\Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE);
		    $configurableProduct->setAttributeSetId($simpleProduct->getAttributeSetId());
		    $configurableProduct->setWebsiteIds($simpleProduct->getWebsiteIds());
		    $configurableProduct->setStoreId($simpleProduct->getStoreId());

		    // Copia attributi base
		    //$configurableProduct->setName($simpleProduct->getName());
		    
			$productName = trim($simpleProduct->getName());

			if (empty($productName)) {
				// Se il semplice non ha nome, usiamo lo SKU configurabile come nome
				$productName = $skuConfigurabile;
			}

			$configurableProduct->setName($productName);
		    
		    
		    
		    $configurableProduct->setUrlKey($this->generateUniqueUrlKey($skuConfigurabile));
		    $configurableProduct->setDescription($simpleProduct->getDescription());
		    $configurableProduct->setShortDescription($simpleProduct->getShortDescription());
		    $configurableProduct->setTaxClassId($simpleProduct->getTaxClassId());
		    $configurableProduct->setVisibility(self::MAGENTO_VISIBILITA_CATALOGO_RICERCA);
		    $configurableProduct->setStatus(self::MAGENTO_STATUS_ENABLED);
		    $configurableProduct->setSku($skuConfigurabile);
		    $configurableProduct->setPrice($simpleProduct->getPrice());
		    $configurableProduct->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1, 'qty' => 0]);

			// Copia categorie
			$configurableProduct->setCategoryIds($simpleProduct->getCategoryIds());

		    // Clona attributi custom
		    foreach ($simpleProduct->getCustomAttributes() as $attr) {
		        $code = $attr->getAttributeCode();
		        $value = $attr->getValue();
		        if (!in_array($code, ['sku', 'url_key', 'entity_id'])) {
		            $configurableProduct->setData($code, $value);
		        }
		    }

		    // Salva il prodotto configurabile
		    $this->saveProductWithRetry($configurableProduct);
		    $this->copyFirstAvailableImage($simpleProduct, $configurableProduct);
		    $this->saveProductWithRetry($configurableProduct);
		} else {
			$this->copyFirstAvailableImage($simpleProduct, $configurableProduct);
		}

		// Associa il semplice al configurabile
		$associatedSkus = [$simpleProduct->getSku()];
		//$configurableAttributesData = $this->getConfigurableAttributesForProduct([$simpleProduct], $configurableProduct);
		
		$allSimpleProducts = $this->getAssociatedSimpleProducts($skuConfigurabile);

		// Mappa combinazioni uniche
		$uniqueCombinations = [];

		foreach ($allSimpleProducts as $prod) {
			$color = $prod->getData('color');
			$tg = $prod->getData('tg_abbigliamento');

			$key = (string)$color . '-' . (string)$tg;

			if (!isset($uniqueCombinations[$key])) {
				$uniqueCombinations[$key] = $prod;
			}
		}

		// Ora aggiungiamo anche il nuovo semplice, se serve
		$colorNew = $simpleProduct->getData('color');
		$tgNew = $simpleProduct->getData('tg_abbigliamento');
		$keyNew = (string)$colorNew . '-' . (string)$tgNew;

		if (!isset($uniqueCombinations[$keyNew])) {
			$uniqueCombinations[$keyNew] = $simpleProduct;
		}

		// Infine aggiorniamo la lista dei semplici unici
		$allSimpleProducts = array_values($uniqueCombinations);

		$configurableAttributesData = $this->getConfigurableAttributesForProduct($allSimpleProducts, $configurableProduct);


		/** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $typeInstance */
		$typeInstance = $this->objectManager->get(\Magento\ConfigurableProduct\Model\Product\Type\Configurable::class);
		
		$this->validateConfigurableAttributesData($allSimpleProducts, $configurableAttributesData);
		$typeInstance->setUsedProductAttributeIds($configurableAttributesData['ids'], $configurableProduct);
		
		$configurableProduct->setNewVariationsAttributeSetId($simpleProduct->getAttributeSetId());
		
		//$configurableProduct->setAssociatedProductIds([$simpleProduct->getId()]);
		//$configurableProduct->setAssocopyFirstAvailableImageciatedProductIds(array_map(function($p) { return $p->getId(); }, $allSimpleProducts));
		$existingAssociates = $configurableProduct->getTypeInstance()->getUsedProducts($configurableProduct);
		$existingAssociateIds = array_map(function ($p) {
			return $p->getId();
		}, $existingAssociates);

		// Aggiunge solo se non già presente
		$newId = $simpleProduct->getId();
		if (!in_array($newId, $existingAssociateIds)) {
			$existingAssociateIds[] = $newId;
		}

		$configurableProduct->unsetData('associated_product_ids');
		$configurableProduct->setAssociatedProductIds($existingAssociateIds);



		$configurableProduct->setCanSaveConfigurableAttributes(true);
		$configurableProduct->setConfigurableAttributesData($configurableAttributesData['data']);

		// Salva di nuovo con le associazioni
		$this->saveProductWithRetry($configurableProduct);

		// ✅ Rendi il prodotto semplice "non visibile individualmente"
		$simpleProduct->setVisibility(self::MAGENTO_VISIBILITA_NON_VSIBILE);
		$this->saveProductWithRetry($simpleProduct);

		return $configurableProduct;
	}

	private function getConfigurableAttributesForProduct(array $simpleProducts, $existingConfigurableProduct = null)
	{
		$attributeIds = [];
		$configurableAttributesData = [];

		// Attributi candidati
		$candidateAttributeCodes = ['color', 'tg_abbigliamento'];

		if ($existingConfigurableProduct 
			&& $existingConfigurableProduct->getId()
			//&& $existingConfigurableProduct->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
			) {
		    $typeInstance = $existingConfigurableProduct->getTypeInstance();
		    $usedAttributes = $typeInstance->getConfigurableAttributes($existingConfigurableProduct);

		    foreach ($usedAttributes as $attribute) {
		        $attributeCode = $attribute->getProductAttribute()->getAttributeCode();
		        $candidateAttributeCodes[] = $attributeCode;
		    }

		    $candidateAttributeCodes = array_unique($candidateAttributeCodes);
		}

		foreach ($candidateAttributeCodes as $attributeCode) {
		    $attribute = $this->getAttribute($attributeCode);
		    if (!$attribute || !$attribute->getId()) {
		        continue;
		    }

		    $foundInSimpleProducts = false;
		    $valueIndexes = [];

		    foreach ($simpleProducts as $simpleProduct) {
		        $value = $simpleProduct->getData($attributeCode);
		        if ($value !== null && $value !== '' && !in_array($value, $valueIndexes)) {
		            $foundInSimpleProducts = true;
		            $valueIndexes[] = $value;
		        }
		    }

		    if ($foundInSimpleProducts) {
		        $attributeIds[] = $attribute->getId();
		        $values = [];
		        foreach ($valueIndexes as $valueIndex) {
		            $values[] = ['value_index' => $valueIndex];
		        }

		        $configurableAttributesData[] = [
		            'attribute_id' => $attribute->getId(),
		            'attribute_code' => $attribute->getAttributeCode(),
		            'frontend_label' => $attribute->getDefaultFrontendLabel(),
		            'values' => $values,
		            'label' => $attribute->getDefaultFrontendLabel()
		        ];
		    }
		}

		return [
		    'ids' => $attributeIds,
		    'data' => $configurableAttributesData
		];
	}


	private function validateConfigurableAttributesData(array $simpleProducts, array $configurableAttributesData)
	{
		$errors = [];

		if (empty($configurableAttributesData['ids']) || empty($configurableAttributesData['data'])) {
		    $errors[] = 'Configurable attributes data is empty.';
		}

		foreach ($configurableAttributesData['data'] as $attributeData) {
		    if (empty($attributeData['attribute_code'])) {
		        $errors[] = 'Attribute code missing in one of configurable attributes.';
		    }
		    if (empty($attributeData['values'])) {
		        $errors[] = 'Attribute ' . $attributeData['attribute_code'] . ' has no values.';
		    }

		    $valueIndexes = [];

		    foreach ($attributeData['values'] as $value) {
		        if (!isset($value['value_index'])) {
		            $errors[] = 'Missing value_index in attribute ' . $attributeData['attribute_code'];
		        } else {
		            $valueIndexes[] = $value['value_index'];
		        }
		    }

		    // Controlla se ci sono duplicati nei value_index
		    if (count($valueIndexes) !== count(array_unique($valueIndexes))) {
		        $errors[] = 'Duplicate value_index detected in attribute ' . $attributeData['attribute_code'];
		    }
		}

		// Check duplicazioni tra prodotti semplici
		$combinations = [];
		foreach ($simpleProducts as $simpleProduct) {
		    $comboKey = '';
		    foreach ($configurableAttributesData['data'] as $attributeData) {
		        $value = $simpleProduct->getData($attributeData['attribute_code']);
		        $comboKey .= $value . '-';
		    }
		    $comboKey = rtrim($comboKey, '-');

		    if (isset($combinations[$comboKey])) {
		        $errors[] = 'Duplicate simple product combination detected: SKU ' . $simpleProduct->getSku() . ' conflicts with SKU ' . $combinations[$comboKey];
		    } else {
		        $combinations[$comboKey] = $simpleProduct->getSku();
		    }
		}

		if (!empty($errors)) {
		    throw new \Magento\Framework\Exception\LocalizedException(
		        __('Errore nella validazione dei dati configurabili: ' . implode(' | ', $errors))
		    );
		}
	}

private function hasValidImage($product)
{
    $image = $product->getImage();
    if (!$image || $image === 'no_selection') {
        return false;
    }

    // Controllo esistenza fisica
    if (!$this->imageExists($image)) {
        return false;
    }

    // Controllo presenza in media gallery
    $entries = $product->getMediaGalleryEntries();
    if (is_array($entries)) {
        foreach ($entries as $entry) {
            if (method_exists($entry, 'getFile') && $entry->getFile() === $image) {
                return true;
            }
        }
    }

    // Non presente nella media gallery → non valida
    return false;
}


private function copyFirstAvailableImage($simpleProduct, &$configurableProduct, $forceReplace = false)
{
    $skuSimple = $simpleProduct->getSku();
    $skuConfig = $configurableProduct->getSku();

    // Verifica se il configurabile ha già un'immagine valida
	$existingImage = $configurableProduct->getImage();
	$hasValidImage = $this->hasValidImage($configurableProduct);

	if (!$forceReplace && $hasValidImage) {
		error_log("[COPY_IMAGE] Il configurabile $skuConfig ha una immagine valida e presente in media gallery: $existingImage");
		return false;
	}

	if (!$hasValidImage && $existingImage) {
		error_log("[COPY_IMAGE] ATTENZIONE: $skuConfig ha immagine ($existingImage) ma NON è valida (manca o non è in media gallery) → copio nuova");
	}

    // Log valori immagine del semplice
    $image = $simpleProduct->getImage();
    $smallImage = $simpleProduct->getSmallImage();
    $thumbnail = $simpleProduct->getThumbnail();
    $mediaGalleryEntries = $simpleProduct->getMediaGalleryEntries();

    error_log("[COPY_IMAGE][DEBUG] Semplice SKU: $skuSimple");
    error_log("[COPY_IMAGE][DEBUG] image: " . ($image ?: 'NULLO'));
    error_log("[COPY_IMAGE][DEBUG] small_image: " . ($smallImage ?: 'NULLO'));
    error_log("[COPY_IMAGE][DEBUG] thumbnail: " . ($thumbnail ?: 'NULLO'));

    // 1. Prova a copiare image / small_image / thumbnail
    $imageFields = ['image', 'small_image', 'thumbnail'];
    foreach ($imageFields as $field) {
        $imagePath = $simpleProduct->getData($field);
        error_log("[COPY_IMAGE][DEBUG] Controllo campo $field per semplice $skuSimple: $imagePath");

        if ($imagePath && $imagePath !== 'no_selection' && $this->imageExists($imagePath)) {
            error_log("[COPY_IMAGE] Immagine trovata su campo $field: $imagePath - copio su $skuConfig");
            $configurableProduct->setImage($imagePath);
            $configurableProduct->setSmallImage($imagePath);
            $configurableProduct->setThumbnail($imagePath);
            
			// Aggiunta in media gallery se non già presente
			try {
				$this->imageProcessor->addImage(
					$configurableProduct,
					'catalog/product' . $imagePath,
					['image', 'small_image', 'thumbnail'],
					false,
					false
				);
				error_log("[COPY_IMAGE] Immagine $imagePath aggiunta anche in media gallery su $skuConfig");
			} catch (\Exception $e) {
				error_log("[COPY_IMAGE][ERRORE] Fallita aggiunta immagine in gallery: " . $e->getMessage());
			}

            return true;
        }
    }

    // 2. Se non troviamo immagine principale, prova media gallery
    if (is_array($mediaGalleryEntries) && count($mediaGalleryEntries)) {
        error_log("[COPY_IMAGE][DEBUG] Media gallery entries: " . count($mediaGalleryEntries));
        foreach ($mediaGalleryEntries as $entry) {
            if (method_exists($entry, 'getFile')) {
                $file = $entry->getFile();
                error_log("[COPY_IMAGE][DEBUG] Gallery entry file: " . $file);

                if ($file && $this->imageExists($file)) {
                    error_log("[COPY_IMAGE] Immagine da media gallery trovata: $file - copio su $skuConfig");
                    $configurableProduct->setImage($file);
                    $configurableProduct->setSmallImage($file);
                    $configurableProduct->setThumbnail($file);
                    
					// Aggiunta in media gallery se non già presente
					try {
						$this->imageProcessor->addImage(
							$configurableProduct,
							'catalog/product' . $imagePath,
							['image', 'small_image', 'thumbnail'],
							false,
							false
						);
						error_log("[COPY_IMAGE] Immagine $imagePath aggiunta anche in media gallery su $skuConfig");
					} catch (\Exception $e) {
						error_log("[COPY_IMAGE][ERRORE] Fallita aggiunta immagine in gallery: " . $e->getMessage());
					}
                    return true;
                }
            }
        }
    } else {
        error_log("[COPY_IMAGE][DEBUG] Media gallery entries NON VALIDE o vuote");
    }

    error_log("[COPY_IMAGE] Nessuna immagine valida trovata per $skuSimple da copiare su $skuConfig");
    return false;
}


	private function __copyFirstAvailableImage($simpleProduct, &$configurableProduct)
	{
		$imageFields = ['image', 'small_image', 'thumbnail'];

		// 1. Prova a copiare direttamente image / small_image / thumbnail
		foreach ($imageFields as $field) {
		    $imagePath = $simpleProduct->getData($field);

		    if ($imagePath && $imagePath !== 'no_selection' && $this->imageExists($imagePath)) {
		        // Copia tutte le immagini principali
		        $configurableProduct->setData('image', $simpleProduct->getImage());
		        $configurableProduct->setData('small_image', $simpleProduct->getSmallImage());
		        $configurableProduct->setData('thumbnail', $simpleProduct->getThumbnail());
		        return true;
		    }
		}

		// 2. Se non troviamo immagine principale, proviamo la media gallery
		$mediaGalleryEntries = $simpleProduct->getMediaGalleryEntries();
		if (is_array($mediaGalleryEntries) && count($mediaGalleryEntries)) {
		    foreach ($mediaGalleryEntries as $entry) {
		        if (method_exists($entry, 'getFile')) {
		            $file = $entry->getFile();
		            if ($file && $this->imageExists($file)) {
		                // Imposta la prima immagine valida trovata
		                $configurableProduct->setImage($file);
		                $configurableProduct->setSmallImage($file);
		                $configurableProduct->setThumbnail($file);
		                return true;
		            }
		        }
		    }
		}

		// Nessuna immagine trovata
		return false;
	}

	private function imageExists($imagePath)
	{
		$mediaDirectory = $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
		$filePath = 'catalog/product' . $imagePath; // Magento salva immagini così
		return $mediaDirectory->isExist($filePath);
	}

	public function urlRewriteExists(string $urlKey, int $storeId): bool
	{
		$connection = $this->resourceConnection->getConnection();
		$select = $connection->select()
		    ->from('url_rewrite', ['request_path'])
		    ->where('request_path = ?', $urlKey)
		    ->where('store_id = ?', $storeId);
		return (bool) $connection->fetchOne($select);
	}

	public function requestPathExists(string $categoryPath, int $storeId): bool
	{
		$connection = $this->resourceConnection->getConnection();
		$select = $connection->select()
		    ->from('url_rewrite', ['request_path'])
		    ->where('request_path = ?', $categoryPath)
		    ->where('store_id = ?', $storeId);

		return (bool) $connection->fetchOne($select);
	}


}
?>
