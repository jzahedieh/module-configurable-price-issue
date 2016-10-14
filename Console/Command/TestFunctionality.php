<?php
namespace Jzahedieh\ConfigurablePriceIssue\Console\Command;

class TestFunctionality
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

    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Catalog\Model\ResourceModel\Product\LinkedProductSelectBuilderInterface $linkedProductSelectBuilder,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\ConfigurableProduct\Pricing\Price\FinalPriceResolver $priceResolver
    ) {
        /**
         * Breaks if set to frontend, works if set to anything else.
         */
        $state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);

        $this->resource = $resourceConnection;
        $this->linkedProductSelectBuilder = $linkedProductSelectBuilder;
        $this->priceResolver = $priceResolver;

        $this->collectionFactory = $collectionFactory;
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

}