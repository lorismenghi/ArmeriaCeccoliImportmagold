<?php

namespace LM\Importmagold\Controller\Adminhtml\Mexal;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use LM\Importmagold\Model\ImportatoreProduct;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Catalog\Api\CategoryListInterface;

class Gruppimerceologici extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    
    protected $importatoreProduct;

	protected $bootstrap;
	protected $objectManager;
	
	protected $categoryRepository;
	protected $searchCriteriaBuilder;
	protected $filterBuilder;
	protected $logger;
	protected $categoryList;
	protected $registry;


    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->bootstrap = Bootstrap::create(BP, $_SERVER);
        $this->objectManager = $this->bootstrap->getObjectManager();
        $this->importatoreProduct = new ImportatoreProduct();
        $this->logger = $this->objectManager->get('Psr\Log\LoggerInterface');
        $this->categoryRepository = $this->objectManager->get('Magento\Catalog\Api\CategoryRepositoryInterface');
        $this->searchCriteriaBuilder = $this->objectManager->get('Magento\Framework\Api\SearchCriteriaBuilder');
		$this->filterBuilder = $this->objectManager->get('Magento\Framework\Api\FilterBuilder');
		$this->categoryList = $this->objectManager->get('Magento\Catalog\Api\CategoryListInterface');
        //$this->registry = $this->objectManager->get('Magento\Framework\Registry');
        //$this->registry->register('isSecureArea', true);
		
    }

    /**
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
		$resultPage = $this->resultPageFactory->create();
		$resultPage->getConfig()->getTitle()->prepend(__('Mexal'));
		$block = $resultPage->getLayout()->getBlock('lm_importmagold_mexal');
		$response = $block->getApiGruppiMerceologiciResponse();
		$response_associativa = $block->getArrayRispostaGruppiMerceologiciRielaborati();
		$codiciMexalRicevuti = [];
		
		$output = "";
		$store = $this->importatoreProduct->storeManagerInterface->getStore();
		foreach($response_associativa as $dati_categoria) {
			$codice_categoria_mexal = $dati_categoria['codice'];
			$codiciMexalRicevuti[] = $codice_categoria_mexal;
			$codice_categoria_parent_mexal = $dati_categoria['cod_grp_merc'];
			$nome_categoria_mexal = $dati_categoria['descrizione'];
			$nome_categoria_parent_mexal = '';
			if ( strlen($codice_categoria_parent_mexal) && array_key_exists($codice_categoria_parent_mexal, $response_associativa) )
				$nome_categoria_parent_mexal = $response_associativa[$codice_categoria_parent_mexal]['descrizione'];
			
			$output .= "\n<br><br>codice_categoria_parent_mexal:$codice_categoria_parent_mexal ($nome_categoria_parent_mexal) - codice_categoria_mexal:$codice_categoria_mexal ($nome_categoria_mexal)";
			//echo "\n<br><br>--------------------- codice_categoria_parent_mexal:$codice_categoria_parent_mexal ($nome_categoria_parent_mexal) - codice_categoria_mexal:$codice_categoria_mexal ($nome_categoria_mexal)";
			
			$category_id = $this->importatoreProduct->getCategoryIdByCodeMexal($codice_categoria_mexal);
			// non cerco piu la categoria per nome, troppo imprecisa come cosa
			//if ( !$category_id )
				//$category_id = $this->importatoreProduct->getCategoryIdByName($codice_categoria_mexal);
			//$output .= "\n<br>Category_id esistente:$category_id @@@@@";
			//echo "\n<br>@@@@@@@@@@@@@@@@@@@@@@@@ category_id esistente:$category_id @@@@@";
			if (!$category_id) {
				// se non trova id in magento, la devo creare
				
				//$output .= "\n<br>Non trova id in magento, la devo creare!";
				//echo "\n<br>non trova id in magento, la devo creare! ";
				if ( strlen($codice_categoria_parent_mexal) ) {
					$parent_category_id = $this->importatoreProduct->getCategoryIdByCodeMexal($codice_categoria_parent_mexal);
					if ( !$parent_category_id )
						$parent_category_id = $store->getRootCategoryId();
				} else {
					$parent_category_id = $store->getRootCategoryId();
				}
				if ( !strlen($codice_categoria_parent_mexal) || $parent_category_id != $store->getRootCategoryId() ) {
					$output .= " \n<br>CREO NUOVA categoria codice_categoria_mexal:$codice_categoria_mexal parent_category_id:$parent_category_id #";
					$newCategory = $this->importatoreProduct->categoryFactory->create();
					//$newCategory->setStoreId($store->getId());
					$newCategory->setStoreId($this->importatoreProduct::ID_STORE_DEFAULT);
					$newCategory->setName($dati_categoria['descrizione']);
					$active = $dati_categoria['stato'] == 'N' ? true : false;
					$newCategory->setIsActive($active);
					$newCategory->setParentId($parent_category_id);
					$newCategory->setData('mexal__cod_grp_merc', $codice_categoria_mexal);
					//$newCategory->setPath($this->importatoreProduct->categoryRepository->get($parent_category_id)->getPath()); //mi scazza tutti i path
					// Generate a unique URL key
					//$urlKey = $this->importatoreProduct->generateUniqueUrlKeyCategory($dati_categoria['descrizione'], $store->getId());
					
					$nome_raw = $dati_categoria['descrizione'];
					$nome_sanitizzato = preg_replace('/[^a-zA-Z0-9]+/', '-', $nome_raw); // sostituisce tutti i caratteri non validi con '-'
					$nome_sanitizzato = trim($nome_sanitizzato, '-');

					if (empty($nome_sanitizzato)) {
						$nome_sanitizzato = 'categoria-' . $codice_categoria_mexal;
					}

					$urlKey = $this->importatoreProduct->generateUniqueUrlKeyCategory($nome_sanitizzato, $store->getId());

					
					
					$newCategory->setUrlKey($urlKey);
					// Save the new category
					$this->importatoreProduct->categoryRepository->save($newCategory);
				}

			} else {
				//$output .= " \n<br>AGGIORNO stato '".$dati_categoria['stato']."' categoria codice_categoria_mexal:$codice_categoria_mexal #";
				$category = $this->importatoreProduct->getCategory($category_id);
				$category->setStoreId($this->importatoreProduct::ID_STORE_DEFAULT);
				
				$category->load($category->getId());
				$active = $dati_categoria['stato'] == 'N' ? true : false;
				if ( $category->getIsActive() != $active || $dati_categoria['descrizione'] != $category->getName() ) {
					$output .= " \n<br>ID_STORE_DEFAULT AGGIORNO stato '{$dati_categoria['stato']}' categoria codice_categoria_mexal: $codice_categoria_mexal #";
					$category->setIsActive($active);
					$category->setName($dati_categoria['descrizione']);
					// Save the category
					//$this->importatoreProduct->categoryRepository->save($category);

					// ✅ Verifica e imposta url_key valido se mancante o errato
					$urlKey = $category->getUrlKey();
					$nome_raw = $category->getName(); // o $dati_categoria['descrizione']
					$nome_sanitizzato = preg_replace('/[^a-zA-Z0-9]+/', '-', $nome_raw);
					$nome_sanitizzato = strtolower(trim($nome_sanitizzato, '-'));

					if (empty($nome_sanitizzato)) {
						$nome_sanitizzato = 'categoria-' . $codice_categoria_mexal;
					}


					$reserved = ['admin', 'soap', 'rest', 'graphql', 'standard', 'amministrazione2021'];

					if (empty($urlKey) || preg_match('/[^a-z0-9\-]/', $urlKey) || in_array($urlKey, $reserved)) {
						$urlKey = $this->importatoreProduct->generateUniqueUrlKeyCategory($nome_sanitizzato, $store->getId());
						$category->setUrlKey($urlKey);
					}

					//$category->save();
					
					try {
						$category->save();
					} catch (\Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException $e) {
						// 🔁 Retry con url_key accodato a timestamp se il path è già occupato
						$originalUrlKey = $category->getUrlKey();
						$newUrlKey = $originalUrlKey . '-' . time();

						$category->setUrlKey($newUrlKey);

						try {
							//$this->importatoreProduct->categoryRepository->save($category);
							$category->save();
							$output .= "\n<br>🔁 RITENTATO salvataggio con url_key: $newUrlKey";
						} catch (\Exception $e2) {
							$output .= "\n<br>❌ Fallito anche il secondo salvataggio categoria ID $codice_categoria_mexal: " . $e2->getMessage();
						}
					} catch (\Exception $e) {
						$output .= "\n<br>❌ Errore generico salvataggio categoria ID $codice_categoria_mexal: " . $e->getMessage();
					}
					
					
					
					
				} else {
					$output .= " \n<br>ID_STORE_DEFAULT NON AGGIORNO stato o nome '{$dati_categoria['stato']}' categoria codice_categoria_mexal: $codice_categoria_mexal #";
				}

				// Ripristino dei valori per lo Store View specifico (Store ID != 0)
				$category = $this->importatoreProduct->getCategory($category_id);
				$category->setStoreId($this->importatoreProduct::ID_STORE_IT);
				$category->load($category->getId());
				if ( $category->getIsActive() != $active ) {
				
					$output .= " \n<br>ID_STORE_IT ANNULLO stato '{$dati_categoria['stato']}' categoria codice_categoria_mexal: $codice_categoria_mexal #";
					$category->setIsActive(null); // Resetta il valore a livello store view
					$category->setData('mexal__cod_grp_merc', null); // Resetta anche questo attributo
					$category->setName(null);
					$category->setData('include_in_menu', null);
					//$this->importatoreProduct->categoryRepository->save($category); // non rimette a null
					$category->save();
				}
				
				// solo per sviluppo
				//$output .= " \n<br>CANCELLO categoria codice_categoria_mexal:$codice_categoria_mexal #";
				//$category->delete();
			}
		}
		$output .= " </br>######### INIZIO CANCELLAZIONE ######### </br>";
		// ✅ FASE FINALE: Cancellazione categorie obsolete
		if (empty($codiciMexalRicevuti)) {
			$this->logger->error("❌ ERRORE: Nessun codice categoria ricevuto da Mexal. Interrotto per evitare eliminazioni massive.");
			$output .= "❌ ERRORE: Nessun codice categoria ricevuto da Mexal. Interrotto per evitare eliminazioni massive.";
			return;
		}
		$output .= " </br>######### codiciMexalRicevuti ".count($codiciMexalRicevuti)." ######### </br>";
		//try {
			$notNullFilter = $this->filterBuilder
				->setField('mexal__cod_grp_merc')
				->setConditionType('notnull')
				->create();

			$notEmptyFilter = $this->filterBuilder
				->setField('mexal__cod_grp_merc')
				->setConditionType('neq')
				->setValue('')
				->create();
				
			$filterGroup = new FilterGroup();
			$filterGroup->setFilters([$notNullFilter, $notEmptyFilter]);

			$searchCriteria = $this->searchCriteriaBuilder
				->addFilters([$notNullFilter])
				->addFilters([$notEmptyFilter])
				->create();

			$categorie = $this->categoryList->getList($searchCriteria)->getItems();

			foreach ($categorie as $categoria) {
				$codice = $categoria->getData('mexal__cod_grp_merc');

				if (!in_array($codice, $codiciMexalRicevuti)) {
				    $id = $categoria->getId();
				    $name = $categoria->getName();

				    $this->logger->warning("🗑️ Eliminazione categoria obsoleta [ID: $id, Nome: $name, Codice Mexal: $codice]");
				    $output .= "</br>🗑️ Eliminazione categoria obsoleta [ID: $id, Nome: $name, Codice Mexal: $codice]";

					$this->importatoreProduct->deleteCategoriaById($id);

					/*
				    try {
				        //$this->categoryRepository->delete($categoria);
				        //$this->deleteCategoriaForzata($categoria);
				        $category->delete();
				    } catch (\Exception $e) {
				        $this->logger->error("⚠️ Errore nella cancellazione della categoria [ID: $id]: " . $e->getMessage());
				        $output .= "\n<br>⚠️ Errore nella cancellazione della categoria [ID: $id]: " . $e->getMessage();
				    }
				    */
				}
			}

		//} catch (\Exception $e) {
			//$this->logger->error("❌ Errore nel controllo categorie obsolete: " . $e->getMessage());
		//}





		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('response_associativa', $response_associativa);
		$resultPage->getLayout()->getBlock('lm_importmagold_mexal')->setData('output', $output);
		return $resultPage;
    }

	protected function deleteCategoriaForzata(\Magento\Catalog\Api\Data\CategoryInterface $categoria)
	{
		$categoriaId = $categoria->getId();
		$categoriaName = $categoria->getName();

		try {
		    // 🔁 Step 1: Cancella ricorsivamente i figli
		    $children = $categoria->getChildrenCategories();
		    foreach ($children as $childCategoria) {
		        $this->deleteCategoriaForzata($childCategoria);
		    }

		    // 🔄 Step 2: Scollega tutti i prodotti (rimuove relazioni)
		    $productIds = $categoria->getProductCollection()->getAllIds();
		    if (!empty($productIds)) {
		        $categoria->setPostedProducts([]);
		        $categoria->setProductLinkData([]);
		        $this->categoryRepository->save($categoria);
		        $this->logger->info("🔗 Rimossi " . count($productIds) . " prodotti da categoria ID $categoriaId");
		    }

		    // 🛑 Step 3: Verifica se è root o sistema
		    if ($categoria->getParentId() == 0 || $categoriaId == 1) {
		        $this->logger->warning("❌ Impossibile eliminare root category ID $categoriaId ($categoriaName)");
		        return;
		    }

		    // 🗑️ Step 4: Elimina la categoria
		    $this->categoryRepository->delete($categoria);
		    $this->logger->info("✅ Categoria forzatamente eliminata: [ID $categoriaId - $categoriaName]");

		} catch (\Exception $e) {
		    $this->logger->error("⚠️ Errore nella cancellazione forzata della categoria ID $categoriaId: " . $e->getMessage());
		}
	}


}

