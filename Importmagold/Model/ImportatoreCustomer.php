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
use Magento\Framework\Filesystem\DirectoryList;


class ImportatoreCustomer extends Importatore
{
	const TIME_LIMIT = 175;
	const ID_CUSTOMER_GROUP_DEFAULT = 1;
	const CUSTOME_ACTIVE = 1;
  const PASSWORD_HASH = 0;
  const PASSWORD_SALT = 1;
	public $output = ['sincClienti' => ''];
	
	const URL_GET_DATA_CUSTOMER = "https://armeriaceccoli.com/script_vari/export_customers.php?token=hinet123&from_id=";
	
	protected $customerRepository;
	protected $customerFactory;
	protected $customerModel;
	protected $customerSetupFactory;
	protected $customerSetup;
	protected $customerEntity;
	protected $setupInterface;
	protected $addressDataFactory;
	protected $addressRepository;
	protected $addressFactory;
	protected $cliente_importato;
	public $data_customer;
		
	public function __construct() {
		
		parent::__construct();
		$this->customerRepository = $this->objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');
		$this->customerFactory = $this->objectManager->get('Magento\Customer\Model\CustomerFactory');
		$this->customerModel = $this->objectManager->get('Magento\Customer\Model\Customer');
    $this->customerSetupFactory = $this->objectManager->create('Magento\Customer\Setup\CustomerSetupFactory');
    $this->setupInterface = $this->objectManager->create('Magento\Framework\Setup\ModuleDataSetupInterface');
    $this->customerSetup = $this->customerSetupFactory->create(['setup' => $this->setupInterface]);
    $this->customerEntity = $this->customerSetup->getEavConfig()->getEntityType('customer');
    $this->addressDataFactory = $this->objectManager->create('Magento\Customer\Api\Data\AddressInterfaceFactory');
    $this->addressRepository = $this->objectManager->create('Magento\Customer\Api\AddressRepositoryInterface');
    $this->addressFactory = $this->objectManager->create('Magento\Customer\Model\AddressFactory');
	}

	// restituisce id del magento vecchio dell'ultimo cliente importato
	public function getLastCustomerImportedId() {

		$mysqli = $this->getMysqliConnection();
		$db = $this->db;
		
		// questo forse e' meglio
		$q = "CREATE TABLE IF NOT EXISTS `{$db['name']}.{$db['pref']}clienti_importati` (
			`id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
			`entity_id_old` int(11) NOT NULL,
			`entity_id_new` int(11) DEFAULT '0',
			`email` varchar(255) COLLATE utf8mb3_general_ci DEFAULT '',
			`aggiornata` int(11) DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
		";
		// non serve, ho gia' creato tabella in install MODULO
		//$mysqli->query($q);
		
		$q = "SELECT * FROM `".$db['name']."`.`".$db['pref']."clienti_importati` WHERE `aggiornata` = 1 ORDER BY `entity_id_old` DESC;";
		$results = $mysqli->query($q);
		
		if ($row = $results->fetch_assoc())
			return (int) $row['entity_id_old'];
		else
			return 0;
	}

	public function getNextCustomerImportId() {
		return $this->getLastCustomerImportedId() + 1;
	}

	// sincronizza importa customer su magento
	public function creaAggiornaCliente($obj_data) {
		//print_r($obj_data);
		$data = (array) $obj_data;
		$this->data_customer = $this->convDataCustomer($data);
		$customerExisting = $this->getCustomerByEmail($this->data_customer['email']);
		$this->createOrUpdateCustomer($this->data_customer, $customerExisting);
		$this->output['sincClienti'] = '#OK IMPORT ULTIMATO CON SUCCESSO#';
		//return $this->mandaOutput(); // lo faccio nel file che chiama la classe
	}

	public static function convertiCustomerGroupId($id) {
		return $id = (int) $id; // in questo caso sono uguali
	}	

	public static function filtraNome($txt, $allow_empty = true) {
		$txt = trim(preg_replace("/[^ \w]+/", "", $txt)); // toglie asterischi e caratteracci
		//$txt = trim($txt);
		if ($allow_empty)
			return $txt;
		else
			return strlen($txt) ? $txt : 'null';
	}

	public function convDataCustomer($data) {
		//echo '<pre>'; print_r($data); echo '</pre>'; //return;
		//$this->data_customer = $data;
		$this->data_customer['entity_id_old'] = array_key_exists('entity_id', $data) ? $data['entity_id'] : 0;
		$this->data_customer['dob'] = array_key_exists('dob', $data) ? $data['dob'] : '01-01-1970';
		$this->data_customer['taxvat'] = array_key_exists('taxvat', $data) ? $data['taxvat'] : '';
		$this->data_customer['email'] = array_key_exists('email', $data) ? $data['email'] : '';
		$this->data_customer['firstname'] = array_key_exists('firstname', $data) ? self::filtraNome($data['firstname'], false) : 'null';
		$this->data_customer['lastname'] = array_key_exists('lastname', $data) ? self::filtraNome($data['lastname'], false) : 'null';
		$this->data_customer['privacy'] = array_key_exists('privacy', $data) ? $data['privacy'] : 1;
		$this->data_customer['password_hash'] = array_key_exists('password_hash', $data) ? $data['password_hash'] : null;
		$this->data_customer['rp_token'] = array_key_exists('rp_token', $data) ? $data['rp_token'] : null;
		$this->data_customer['rp_token_created_at'] = array_key_exists('rp_token_created_at', $data) ? $data['rp_token_created_at'] : null;
		
		$this->data_customer['confirmation'] = array_key_exists('confirmation', $data) ? $data['confirmation'] : null;
		if ( array_key_exists('gender', $data) ) {
			switch ($data['gender']) {
				case 1: // maschio
					$this->data_customer['gender'] = 1;
					break;
				case 2: // femmina
					$this->data_customer['gender'] = 2;
					break;
				case 3: // fru fru
					$this->data_customer['gender'] = 3;
					break;
				default:
					$this->data_customer['gender'] = 3;
			}			
		} else
			$this->data_customer['gender'] = 3;
		$this->data_customer['website_id'] = array_key_exists('website_id', $data) ? $this->convertiWebsiteId($data['website_id']) : self::ID_WEBSITE_DEFAULT;
		$this->data_customer['store_id'] = array_key_exists('store_id', $data) ? $this->convertiStoreId($data['store_id']) : self::ID_STORE_DEFAULT;
		$this->data_customer['group_id'] = array_key_exists('group_id', $data) ? $this->convertiCustomerGroupId($data['group_id']) : self::ID_CUSTOMER_GROUP_DEFAULT;
		$this->data_customer['suffix'] = array_key_exists('suffix', $data) ? $data['suffix'] : '';
		$this->data_customer['middlename'] = array_key_exists('middlename', $data) ? $data['middlename'] : '';
		$this->data_customer['prefix'] = array_key_exists('prefix', $data) ? $data['prefix'] : '';
		if ( array_key_exists('created_at', $data) && strlen($data['created_at']) )
			$this->data_customer['created_at'] = $data['created_at'];
		if ( array_key_exists('updated_at', $data) && strlen($data['updated_at']) )
			$this->data_customer['updated_at'] = $data['updated_at'];
		$this->data_customer['is_active'] = array_key_exists('is_active', $data) ? (int) $data['is_active'] : self::CUSTOME_ACTIVE;
		
		// attributi personalizzati
		//$this->data_customer['codiceazienda'] = array_key_exists('codiceazienda', $data) ? $data['codiceazienda'] : '';
		//$this->data_customer['codicefiscale'] = array_key_exists('codicefiscale', $data) ? $data['codicefiscale'] : '';
		//$this->data_customer['codice_univoco'] = array_key_exists('codice_univoco', $data) ? $data['codice_univoco'] : '';
		//$this->data_customer['pec'] = array_key_exists('pec', $data) ? $data['pec'] : '';
		//$this->data_customer['shop_name'] = array_key_exists('shop_name', $data) ? $data['shop_name'] : '';
		//$this->data_customer['website'] = array_key_exists('website', $data) ? $data['website'] : '';
		//$this->data_customer['collection_seen'] = array_key_exists('collection_seen', $data) ? $data['collection_seen'] : 0;
		//$this->data_customer['collection_where'] = array_key_exists('collection_where', $data) ? $data['collection_where'] : '';
		//$this->data_customer['activity'] = array_key_exists('activity', $data) ? $data['activity'] : '';
		//$this->data_customer['comments'] = array_key_exists('comments', $data) ? $data['comments'] : '';
		//$this->data_customer['mexal_customer_id'] = array_key_exists('mexal_customer_id', $data) ? $data['mexal_customer_id'] : '';
		//$this->data_customer['braintree_customer_id'] = array_key_exists('braintree_customer_id', $data) ? $data['braintree_customer_id'] : '';
		if (false) { // non ha sistema di attivazione
			$this->data_customer['customer_activated'] = array_key_exists('customer_activated', $data) ? $data['customer_activated'] : 3; // setto momentaneamente come new del vecchio sito
			// customer_activated
			// valori vecchio sito: 3 = new , 1 = approved , 2 = rejected
			// valori nuovo   sito: 25 = new, 26 = approved, 27 = discard
			switch ($this->data_customer['customer_activated']) {
				case 3:
					$this->data_customer['customer_activated'] = 25; // new
					break;
				case 1:
					$this->data_customer['customer_activated'] = 26; // approved
					break;
				case 2:
					$this->data_customer['customer_activated'] = 27; // discard
					break;
				default:
					$this->data_customer['customer_activated'] = 25; // new
			}
		}
		
		
		/*
		if ( array_key_exists('privatoazienda', $data) ) {
			switch ($data['privatoazienda']) {
				case 8: // azienda
					$this->data_customer['privatoazienda'] = 4;
					$this->data_customer['prefix'] = 'Azienda';
					break;
				case 9: // privato
					$this->data_customer['privatoazienda'] = 5;
					$this->data_customer['prefix'] = 'Privato';
					break;
				default:
					$this->data_customer['privatoazienda'] = 4;
					$this->data_customer['prefix'] = 'Azienda';
			}			
		} else
			$this->data_customer['privatoazienda'] = 3;
		*/
			
		if ( array_key_exists('default_shipping', $data) ) {
			$default_shipping = $data['default_shipping'];
			if ( is_object($default_shipping) && property_exists($default_shipping, 'entity_id') ) {
            //$street = trim(preg_replace('/\s\s+/', ' ', $this->data_customer['default_shipping']['street']));
            $street = trim(preg_replace('/\s+/', ' ', $default_shipping->street));
			
			//var_dump($default_shipping); echo "######".$default_shipping->firstname."#######";
			$this->data_customer['default_shipping']['entity_id'] = $default_shipping->entity_id;
			$this->data_customer['default_shipping']['firstname'] = $default_shipping->firstname;
			$this->data_customer['default_shipping']['lastname'] = $default_shipping->lastname;
			//$this->data_customer['default_shipping']['middlename'] = $default_shipping->middlename;
			$this->data_customer['default_shipping']['country_id'] = $default_shipping->country_id;
			$this->data_customer['default_shipping']['is_active'] = $default_shipping->is_active;
			$this->data_customer['default_shipping']['created_at'] = $default_shipping->created_at;
			$this->data_customer['default_shipping']['region'] = property_exists($default_shipping, 'region') ? $default_shipping->region : ''; // c'e' in M2 ma e' nome esteso Rimini
			$this->data_customer['default_shipping']['prefix'] = property_exists($default_shipping, 'prefix') ? $default_shipping->prefix : null;
			$this->data_customer['default_shipping']['suffix'] = property_exists($default_shipping, 'suffix') ? $default_shipping->suffix : null;
			$this->data_customer['default_shipping']['postcode'] = property_exists($default_shipping, 'postcode') && strlen($default_shipping->postcode) ? $default_shipping->postcode : '';
			$this->data_customer['default_shipping']['city'] = property_exists($default_shipping, 'city') && strlen($default_shipping->city) ? $default_shipping->city : '';
			$this->data_customer['default_shipping']['telephone'] = property_exists($default_shipping, 'telephone') && strlen($default_shipping->telephone) ? $default_shipping->telephone : '';
			$this->data_customer['default_shipping']['fax'] = property_exists($default_shipping, 'fax') ? $default_shipping->fax : '';
			$this->data_customer['default_shipping']['company'] = property_exists($default_shipping, 'company') ? $default_shipping->company : '';
			$this->data_customer['default_shipping']['region_id'] = property_exists($default_shipping, 'region_id') ? $default_shipping->region_id : null; // c'e' in M2 e M1
			$this->data_customer['default_shipping']['street'] = strlen($street) ? $street : '-';
			$this->data_customer['default_shipping']['region_name'] = property_exists($default_shipping, 'region_name') ? $default_shipping->region_name : ''; // questo credo sia solo in M1
			$this->data_customer['default_shipping']['region'] = property_exists($default_shipping, 'region') ? $default_shipping->region : '';
			$this->data_customer['default_shipping']['region_code'] = property_exists($default_shipping, 'region_code') ? $default_shipping->region_code : ''; // questo credo sia solo in M1
			$this->data_customer['default_shipping']['vat_id'] = property_exists($default_shipping, 'vat_id') ? $default_shipping->vat_id : '';
			}
		}
		if ( array_key_exists('default_billing', $data) ) {
			$default_billing = $data['default_billing'];
			if ( is_object($default_billing) && property_exists($default_billing, 'entity_id') ) {
			//$street = trim(preg_replace('/\s\s+/', ' ', $this->data_customer['default_billing']['street']));
            $street = trim(preg_replace('/\s+/', ' ', $default_billing->street));
			$this->data_customer['default_billing']['entity_id'] = $default_billing->entity_id;
			$this->data_customer['default_billing']['created_at'] = $default_billing->created_at;
			$this->data_customer['default_billing']['firstname'] = $default_billing->firstname;
			$this->data_customer['default_billing']['lastname'] = $default_billing->lastname;
			//$this->data_customer['default_billing']['middlename'] = $default_billing->middlename;
			$this->data_customer['default_billing']['country_id'] = $default_billing->country_id;
			$this->data_customer['default_billing']['is_active'] = $default_billing->is_active;
			$this->data_customer['default_billing']['region'] = property_exists($default_billing, 'region') ? $default_billing->region : '';
			$this->data_customer['default_billing']['prefix'] = property_exists($default_billing, 'prefix') ? $default_billing->prefix : null;
			$this->data_customer['default_billing']['suffix'] = property_exists($default_billing, 'suffix') ? $default_billing->suffix : null;
			$this->data_customer['default_billing']['postcode'] = property_exists($default_billing, 'postcode') && strlen($default_billing->postcode) ? $default_billing->postcode : '';
			$this->data_customer['default_billing']['city'] = property_exists($default_billing, 'city') && strlen($default_billing->city) ? $default_billing->city : '-';
			$this->data_customer['default_billing']['telephone'] = property_exists($default_billing, 'telephone') && strlen($default_billing->telephone) ? $default_billing->telephone : '';
			$this->data_customer['default_billing']['fax'] = property_exists($default_billing, 'fax') ? $default_billing->fax : '';
			$this->data_customer['default_billing']['company'] = property_exists($default_billing, 'company') ? $default_billing->company : '';
			$this->data_customer['default_billing']['region_id'] = property_exists($default_billing, 'region_id') ? $default_billing->region_id : null;
			$this->data_customer['default_billing']['street'] = strlen($street) ? $street : '-';
			$this->data_customer['default_billing']['region_name'] = property_exists($default_billing, 'region_name') ? $default_billing->region_name : '';
			$this->data_customer['default_billing']['region_code'] = property_exists($default_billing, 'region_code') ? $default_billing->region_code : '';
			$this->data_customer['default_billing']['vat_id'] = property_exists($default_billing, 'vat_id') ? $default_billing->vat_id : '';
			}
		}
		if ( array_key_exists('default_shipping', $this->data_customer) && array_key_exists('default_billing', $this->data_customer)
			&& $this->data_customer['default_shipping']['entity_id'] == $this->data_customer['default_billing']['entity_id']
		 ) {
			$this->data_customer['shipping_billing_uguali'] = true;
		} else {
			$this->data_customer['shipping_billing_uguali'] = false;
		}
		//echo '<pre>'; print_r($this->data_customer); echo '</pre>';

		return $this->data_customer;
	}
	
    public function getCustomerByEmail($email)
    {
    	$customer = $this->customerModel;
    	$customer->setWebsiteId($this->data_customer['website_id']);
        $customer->loadByEmail($email);
        if ($customer->getId()) {
            return $customer;
        }


		if ( is_object($customer) && $customer->getId() ) {
			return $customer;
		} else {
			return false;
		}
    }

    public function getCustomerById($id)
    {
    	$customer = $this->customerRepository->getById($id);
		if ( is_object($customer) && $customer->getId() ) {
			return $customer;
		} else {
			return false;
		}
    }

	public function createOrUpdateCustomer($data, $customerExisting = null) {

		// $data e $this->data_customer hanno gli stessi valori, perchè $this->data_customer è passato in $data
		if ( is_object($customerExisting) ) {
			$this->output['dettaglio_azioni'] .= ' ##customerExisting## ';
			$customer = $customerExisting;
		} else {
			$this->output['dettaglio_azioni'] .= ' ##customer NOT Existing## ';
            $customer = $this->customerFactory->create();
            //echo "Creo cliente email:".$data['email']."#";

		}
        // Get Website ID
        //$websiteId  = $this->storeManager->getWebsite()->getWebsiteId();

		$customer->setWebsiteId($data['website_id']); //website ID
		$customer->setGroupId($data['group_id']);
		$customer->setSoreId($data['store_id']);
        // Preparing data for new customer
        $customer->setEmail($data['email']);
        $customer->setSuffix($data['suffix']);
        $customer->setPrefix($data['prefix']);
        $customer->setMiddlename($data['middlename']);
        $customer->setPrefix($data['prefix']);
        $customer->setFirstname($data['firstname']);
        $customer->setLastname($data['lastname']);
        $customer->setTaxvat($data['taxvat']);
        $customer->setDob($data['dob']);
        $customer->setConfirmation($data['confirmation']);
        $customer->setGender($data['gender']);
        $customer->setCreatedAt($data['created_at']);
        $customer->setUpdatedAt($data['updated_at']);
        $customer->setIsActive($data['is_active']);
        
        //$customer->setCodiceUnivoco($data['codice_univoco']);
        //$customer->setPec($data['pec']);
        //$customer->setShopName($data['shop_name']);
        //$customer->setWebsite($data['website']);
        //$customer->setCollectionSeen($data['collection_seen']);
        //$customer->setCollectionWhere($data['collection_where']);
        //$customer->setActivity($data['activity']);
        //$customer->setComments($data['comments']);
        //$customer->setMexalCustomerId($data['mexal_customer_id']);
        //$customer->setBraintreeCustomerId($data['braintree_customer_id']);
        //$customer->setCustomerActivated($data['customer_activated']);
        
        //$customer->setCodiceazienda($data['codiceazienda']);
        //$customer->setCodicefiscale($data['codicefiscale']);
        //$customer->setPrivatoazienda($data['privatoazienda']);
        $customer->setPrivacy($data['privacy']);
        $attributeSetId = $this->customerEntity->getDefaultAttributeSetId();
        $customer->setAttributeSetId($attributeSetId);
        //$customer->setPassword("password");
        if ( strlen($data['password_hash']) ) {
			//$nuovo_hash = $this->upgradeCustomerHash($data['password_hash']); // questo serve per trasfomare quello di magento 1
			$nuovo_hash = $data['password_hash']; // se viene già da un M2
			$customer->setPasswordHash($nuovo_hash);
		}
		$customer->save();
		
		$addresses = $customer->getAddresses();
		foreach ($addresses as $address) {
			$this->addressRepository->deleteById($address->getId());
		}

		$shipping_billing_uguali = $data['shipping_billing_uguali'] ? true : false;
		if ( array_key_exists('default_shipping', $data) && array_key_exists('entity_id', $data['default_shipping']) ) {
            $address = $this->addressFactory->create();
            
            // il region_code non corrisponde con quello del sito vecchio quindi me lo devo ricalcolare
			$country_id = $this->data_customer['default_shipping']['country_id'];
			$region_code = $this->data_customer['default_shipping']['region_code'];
			$region_id = $this->getCorrispondenteRegionId($country_id, $region_code); // potrebbe essere numero o sigla
            $street = trim(preg_replace('/\s\s+/', ' ', $this->data_customer['default_shipping']['street']));

            $address->setCustomerId($customer->getId())
                    ->setFirstname($this->data_customer['default_shipping']['firstname'])
                    ->setLastname($this->data_customer['default_shipping']['lastname'])
                    //->setMiddlename($this->data_customer['default_shipping']['middlename'])
                    ->setPrefix($this->data_customer['default_shipping']['prefix'])
                    ->setSuffix($this->data_customer['default_shipping']['suffix'])
                    ->setCountryId($this->data_customer['default_shipping']['country_id'])
                    ->setIsActive($this->data_customer['default_shipping']['is_active'])
                    ->setCreatedAt($this->data_customer['default_shipping']['created_at'])
                    ->setRegionCode($this->data_customer['default_shipping']['region_code'])
                    ->setRegionId($region_id)
                    ->setPostcode($this->data_customer['default_shipping']['postcode'])
                    ->setCity($this->data_customer['default_shipping']['city'])
                    ->setVatId($this->data_customer['default_shipping']['vat_id'])
                    ->setRegion($this->data_customer['default_shipping']['region'])
                    //->setRegionName($this->data_customer['default_shipping']['region_name'])
                    //->setState($JsonParam->State)
                    ->setTelephone($this->data_customer['default_shipping']['telephone'])
                    ->setCompany($this->data_customer['default_shipping']['company'])
                    ->setStreet($street)
                    ->setIsDefaultBilling($shipping_billing_uguali)
                    ->setIsDefaultShipping('1')
                    ->setSaveInAddressBook('1');
            $address->save();
		}
		if ( !$shipping_billing_uguali && array_key_exists('default_billing', $data) && array_key_exists('entity_id', $data['default_billing']) ) {
            $address = $this->addressFactory->create();
            
            // il region_code non corrisponde con quello del sito vecchio quindi me lo devo ricalcolare
			$country_id = $this->data_customer['default_billing']['country_id'];
			$region_code = $this->data_customer['default_billing']['region_code'];
			$region_id = $this->getCorrispondenteRegionId($country_id, $region_code); // potrebbe essere numero o sigla
			$street = trim(preg_replace('/\s\s+/', ' ', $this->data_customer['default_billing']['street']));

            $address->setCustomerId($customer->getId())
                    ->setFirstname($this->data_customer['default_billing']['firstname'])
                    ->setLastname($this->data_customer['default_billing']['lastname'])
                    ->setCountryId($this->data_customer['default_billing']['country_id'])
                    //->setMiddlename($this->data_customer['default_billing']['middlename'])
                    ->setPrefix($this->data_customer['default_billing']['prefix'])
                    ->setSuffix($this->data_customer['default_billing']['suffix'])
                    ->setCountryId($this->data_customer['default_billing']['country_id'])
                    ->setIsActive($this->data_customer['default_billing']['is_active'])
                    ->setCreatedAt($this->data_customer['default_billing']['created_at'])
                    ->setRegionCode($this->data_customer['default_billing']['region_code'])
                    ->setRegionId($region_id)
                    ->setPostcode($this->data_customer['default_billing']['postcode'])
                    ->setCity($this->data_customer['default_billing']['city'])
                    ->setVatId($this->data_customer['default_billing']['vat_id'])
                    ->setRegion($this->data_customer['default_billing']['region'])
                    //->setRegionName($this->data_customer['default_billing']['region_name'])
                    //->setState($JsonParam->BillingState)
                    ->setTelephone($this->data_customer['default_billing']['telephone'])
                    ->setCompany($this->data_customer['default_billing']['company'])
                    ->setStreet($street)
                    ->setIsDefaultBilling('1')
                    ->setIsDefaultShipping(false)
                    ->setSaveInAddressBook('1');
            $address->save();
		}
		
		$this->cliente_importato = $customer;

/*


        $customerSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY, 'attribute_code', [
            'type' => 'varchar',
            'label' => 'Attribute Title',
            'input' => 'text',
            'required' => false,
            'visible' => true,
            'user_defined' => true,
            'sort_order' => 1000,
            'position' => 1000,
            'system' => 0,
        ]);
        //add attribute to attribute set
        $attribute = $customerSetup->getEavConfig()->getAttribute(\Magento\Customer\Model\Customer::ENTITY, 'attribute_code')
        ->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => ['adminhtml_customer'],
        ]);

        $attribute->save();
*/


		
	}

    /**
     * Upgrade customer hash according M2 algorithm versions quando viene da un magento 1
     *
     * @param array $recordAttributesData
     * @return array
     */
    private function upgradeCustomerHash($recordAttributesData)
    {
    	// adatto perchè tanto ho già hash separato da tutti gli altri dati e quindi lo rimetto in un array
    	$a = $recordAttributesData;
    	$recordAttributesData = array();
    	$recordAttributesData['password_hash'] = $a;
    	
        if (strlen($a) && isset($recordAttributesData['password_hash'])) {
            $hash = $this->explodePasswordHash($recordAttributesData['password_hash']);

            if (strlen($hash[self::PASSWORD_HASH]) == 32) {
                $recordAttributesData['password_hash'] = implode(
                    ':',
                    [$hash[self::PASSWORD_HASH], $hash[self::PASSWORD_SALT], '0']
                );
            } elseif (strlen($hash[self::PASSWORD_HASH]) == 64) {
                $recordAttributesData['password_hash'] = implode(
                    ':',
                    [$hash[self::PASSWORD_HASH], $hash[self::PASSWORD_SALT], '1']
                );
            }
        }

        return $recordAttributesData['password_hash'];
    }

    /**
     * @param string $passwordHash
     * @return array
     */
    private function explodePasswordHash($passwordHash)
    {
        $explodedPassword = explode(':', $passwordHash, 2);
        $explodedPassword[self::PASSWORD_SALT] = isset($explodedPassword[self::PASSWORD_SALT])
            ? $explodedPassword[self::PASSWORD_SALT]
            : ''
        ;
        return $explodedPassword;
    }

	// il region ID dei 2 magento non sono uguali e quindi con la sigla isocode2 del paese e della provincia, mi cerco un ID numerocio se presente, altrimenti restiituisco 0
	public function getCorrispondenteRegionId($country_id, $region_code = '', $region_name = '') {
		$mysqli = $this->getMysqliConnection();
		$db = $this->db;
		$q = '';
		if ( !is_null($region_code) && strlen($region_code) )
			$q = "SELECT * FROM `".$db['name']."`.`".$db['pref']."directory_country_region` WHERE `country_id` = '$country_id' AND `code` = '$region_code' ORDER BY `region_id` DESC;";
		elseif ( !is_null($region_name) && strlen($region_name) )
			$q = "SELECT * FROM `".$db['name']."`.`".$db['pref']."directory_country_region` WHERE `country_id` = '$country_id' AND `default_name` = '$region_name' ORDER BY `region_id` DESC;";

		if ( !strlen($q) )
			return null;
			
		$results = $mysqli->query($q);
		
		if ($row = $results->fetch_assoc()) {
			//$row = $results->fetch_assoc();
			return (int) $row['region_id'];
		} else
			return null;
	
	}

	public function registraImportCustomer() {
		$mysqli = $this->getMysqliConnection();
		$db = $this->db;
	
		$q = "SELECT * FROM `".$db['name']."`.`".$db['pref']."clienti_importati` WHERE `entity_id_old` = '".$this->data_customer['entity_id_old']."' 
		
		
		ORDER BY `entity_id_old` DESC;"; //OR `email` = '".$mysqli->escape_string($this->data_customer['email'])."' 
		
		$results = $mysqli->query($q);
		if ( $row = $results->fetch_assoc() ) { // se è già presente aggiorno
			$id = $row['id'];
			$entity_id_new = is_object($this->cliente_importato) && $this->cliente_importato->getId() ? $this->cliente_importato->getId() : 0;
			$q = "UPDATE `".$db['name']."`.`".$db['pref']."clienti_importati` SET `aggiornata` = 1, `email` = '".$mysqli->escape_string($this->data_customer['email'])."', `entity_id_new` = '$entity_id_new' WHERE `".$db['name']."`.`".$db['pref']."clienti_importati`.`id` = '$id';";
			$mysqli->query($q);
		} else {
			$entity_id_new = is_object($this->cliente_importato) && $this->cliente_importato->getId() ? $this->cliente_importato->getId() : 0;
			$q = "INSERT INTO `".$db['name']."`.`".$db['pref']."clienti_importati` (`id`, `entity_id_old`, `entity_id_new`, `email`, `aggiornata`) VALUES (NULL, '".$this->data_customer['entity_id_old']."', '$entity_id_new', '".$mysqli->escape_string($this->data_customer['email'])."', '1');";
			$mysqli->query($q);
		}
	}

	public function resetImportCustomers() {
		$mysqli = $this->getMysqliConnection();
		$db = $this->db;
		
		// Aggiorna tutte le righe impostando il valore della colonna `aggiornata` a 0
    $q = "UPDATE `" . $db['name'] . "`.`" . $db['pref'] . "clienti_importati` SET `aggiornata` = 0;";
    
    if ($mysqli->query($q) === TRUE) {
        return ['success' => true, 'message' => __('Tutti i clienti importati sono stati resettati.')];
    } else {
        return ['success' => false, 'message' => __('Errore durante il reset dei clienti importati: ' . $e->getMessage())];
    }
	}

}
?>
