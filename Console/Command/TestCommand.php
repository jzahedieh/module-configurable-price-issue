<?php
namespace Jzahedieh\ConfigurablePriceIssue\Console\Command;

class TestCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var \Magento\Framework\App\ObjectManagerFactory
     */
    private $objectManagerFactory;

    public function __construct(
        \Magento\Framework\App\ObjectManagerFactory $objectManagerFactory
    ) {
        $this->objectManagerFactory = $objectManagerFactory;

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
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        $output->writeln("<info>Test Script Started.</info>");

        //if this is not here I get: Area code not set: Area code must be set before starting a session.
        session_start();

        $model = $this->getObjectManager()->get(TestFunctionality::class);
        $price = $model->resolvePrice(388307);

        $output->writeln("<info>The price is: $price </info>");
        $output->writeln("<info>Test Script Finished.</info>");
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
        $omParams[\Magento\Store\Model\StoreManager::PARAM_RUN_CODE] = 'gb';
        $omParams[\Magento\Store\Model\Store::CUSTOM_ENTRY_POINT_PARAM] = true;
        return $this->objectManagerFactory->create($omParams);
    }

}