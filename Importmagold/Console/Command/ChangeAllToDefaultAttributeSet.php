<?php
namespace LM\Importmagold\Console\Command;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeAllToDefaultAttributeSet extends Command
{
    /** @var State */
    private $state;

    /** @var CollectionFactory */
    private $productCollectionFactory;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    const DEFAULT_ATTRIBUTE_SET_ID = 4;

    public function __construct(
        State $state
    ) {
        $this->state = $state;

        $objectManager = ObjectManager::getInstance();
        $this->productCollectionFactory = $objectManager->get(CollectionFactory::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('lm:importmagold:set-attribute-default')
            ->setDescription('Set all products to default attribute set (ID 4)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode('adminhtml');
        } catch (\Exception $e) {
            // area code già settato
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');

        $changed = 0;
        foreach ($collection as $product) {
            if ((int)$product->getAttributeSetId() !== self::DEFAULT_ATTRIBUTE_SET_ID) {
                $product->setAttributeSetId(self::DEFAULT_ATTRIBUTE_SET_ID);
                $this->productRepository->save($product);
                $output->writeln("<info>Prodotto aggiornato: {$product->getSku()}</info>");
                $changed++;
            }
        }

        $output->writeln("<comment>Totale prodotti aggiornati: $changed</comment>");
        return 0;
    }
}

