<?php
namespace Jzahedieh\ConfigurablePriceIssue\Console\Command;

use Symfony\Component\Console\Command\Command;

class TestCommand extends \Symfony\Component\Console\Command\Command
{

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\LinkedProductSelectBuilderInterface
     */
    private $linkedProductSelectBuilder;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var \Magento\ConfigurableProduct\Pricing\Price\PriceResolverInterface
     */
    private $priceResolver;

    /**
     * @var \Magento\Framework\App\ObjectManagerFactory
     */
    private $objectManagerFactory;

    public function __construct(
        \Magento\Framework\App\ObjectManagerFactory $objectManagerFactory,
        \Magento\Catalog\Model\ResourceModel\Product\LinkedProductSelectBuilderInterface $linkedProductSelectBuilder,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\ConfigurableProduct\Pricing\Price\FinalPriceResolver $priceResolver
    ) {

        $this->objectManagerFactory = $objectManagerFactory;

        $this->resource = $resourceConnection;
        $this->linkedProductSelectBuilder = $linkedProductSelectBuilder;
        $this->priceResolver = $priceResolver;

        // This breaks
        $this->collectionFactory = $collectionFactory;

        // This works
        $this->collectionFactory = $this->getObjectManager()->create(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class);

        parent::__construct();
    }

    /**
     * Execute test command which is attempting to mimic following behaviour:
     * @see \Magento\ConfigurableProduct\Pricing\Price\ConfigurablePriceResolver::resolvePrice()
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $output->writeln("<info>Test Script Started.</info>");

        $price = $this->resolvePrice(388307);

        $output->writeln("<info>The price is: $price </info>");
        $output->writeln("<info>Test Script Finished.</info>");
    }

    /**
     * Cut down version of the method:
     * @see \Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProvider::getProducts()
     *
     * Use only the "Safe" method whatever that means as this is what is being call on the frontend.
     * Break out the linked product builder query so can inspect.
     *
     * @param int $productId
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProducts($productId)
    {
        $query = '(' . implode(') UNION (', $this->linkedProductSelectBuilder->build($productId)) . ')';
        $productIds = $this->resource->getConnection()->fetchCol(
            $query
        );

        // returns random configurable children each time
        var_dump($productIds);

        $collection = $this->collectionFactory->create()
            ->addAttributeToSelect(['price', 'special_price'])
            ->addIdFilter($productIds);

        return $collection;
    }

    /**
     * Cut version of method
     * @see \Magento\ConfigurableProduct\Pricing\Price\ConfigurablePriceResolver::resolvePrice()
     *
     * @param int $productId
     * @return float
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resolvePrice($productId)
    {
        $price = null;
        $products = $this->getProducts($productId);

        foreach ($products as $subProduct) {
            $productPrice = $this->priceResolver->resolvePrice($subProduct);
            $price = $price ? min($price, $productPrice) : $productPrice;
        }
        if ($price === null) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Configurable product "%1" does not have sub-products', $productId)
            );
        }

        return (float)$price;
    }

    /**
     * Setup console command.
     */
    protected function configure()
    {
        $this->setName('cpi:test')
            ->setDescription('Used for debugging issue..');

        parent::configure();
    }

    /**
     * Create fresh object manager
     *
     * @return \Magento\Framework\ObjectManagerInterface
     */
    private function getObjectManager()
    {
        $omParams = $_SERVER;
        $omParams[\Magento\Store\Model\StoreManager::PARAM_RUN_CODE] = 'admin';
        $omParams[\Magento\Store\Model\Store::CUSTOM_ENTRY_POINT_PARAM] = true;
        return $this->objectManagerFactory->create([]);
    }

}