<?php

namespace N98\Magento\Command\System;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

class InfoCommand extends AbstractMagentoCommand
{
    /**
     * @var array
     */
    protected $infos = [];

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Eav\Model\Entity\AttributeFactory
     */
    protected $attributeFactory;

    /**
     * @var \Magento\Framework\App\Cache\Type\FrontendPool
     */
    protected $frontendPool;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    protected $deploymentConfig;

    protected function configure()
    {
        $this
            ->setName('sys:info')
            ->setDescription('Prints infos about the current magento system.')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
        ;
    }

    /**
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory
     * @param \Magento\Framework\App\Cache\Type\FrontendPool $frontendPool
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     */
    public function inject(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Framework\App\Cache\Type\FrontendPool $frontendPool,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\Module\ModuleListInterface $moduleList

    ) {
        $this->customerFactory = $customerFactory;
        $this->productFactory = $productFactory;
        $this->categoryFactory = $categoryFactory;
        $this->attributeFactory = $attributeFactory;
        $this->frontendPool = $frontendPool;
        $this->deploymentConfig = $deploymentConfig;
        $this->moduleList = $moduleList;
    }

    public function hasInfo()
    {
        return ! empty($this->infos);
    }

    public function getInfo($key = null)
    {
        if (is_null($key)) {
            return $this->infos;
        }

        return isset($this->infos[$key]) ? $this->infos[$key] : null;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('format') == null) {
            $this->writeSection($output, 'Magento System Information');
        }

        $this->addVersionInfo();
        $this->addDeploymentInfo();
        $this->addCacheInfos();
        $this->addVendors();
        $this->addAttributeCount();
        $this->addCustomerCount();
        $this->addCategoryCount();
        $this->addProductCount();

        $table = array();
        foreach ($this->infos as $key => $value) {
            $table[] = array($key, $value);
        }

        $this->getHelper('table')
            ->setHeaders(array('name', 'value'))
            ->renderByFormat($output, $table, $input->getOption('format'));
    }

    /**
     * @todo there is also the product repository API...?!
     */
    protected function addProductCount()
    {
        $this->infos['Product Count'] = $this->productFactory
                                             ->create()
                                             ->getCollection()
                                             ->getSize();
    }

    protected function addCustomerCount()
    {
        $this->infos['Customer Count'] = $this->customerFactory->create()
                                              ->getCollection()
                                              ->getSize();
    }

    protected function addCategoryCount()
    {
        $this->infos['Category Count'] = $this->categoryFactory
                                              ->create()
                                              ->getCollection()
                                              ->getSize();
    }

    protected function addAttributeCount()
    {
        $this->infos['Attribute Count'] = $this->attributeFactory
                                               ->create()
                                               ->getCollection()
                                               ->getSize();
    }

    protected function addCacheInfos()
    {
        $cachePool = $this->frontendPool;

        $this->infos['Cache Backend'] = get_class($cachePool->get('config')->getBackend());

        switch (get_class($cachePool->get('config')->getBackend())) {
            case 'Zend_Cache_Backend_File':
            case 'Cm_Cache_Backend_File':
                // @TODO Where are the cache options?
                //$cacheDir = $cachePool->get('config')->getBackend()->getOptions()->getCacheDir();
                //$this->infos['Cache Directory'] = $cacheDir;
                break;

            default:
        }
    }

    protected function addDeploymentInfo()
    {
        $this->infos['Session'] = $this->deploymentConfig->get('session/save');
        $this->infos['Crypt Key'] = $this->deploymentConfig->get('crypt/key');
        $this->infos['Install Date'] = $this->deploymentConfig->get('install/date');
    }

    protected function addVersionInfo()
    {
        $this->infos['Version'] = \Magento\Framework\AppInterface::VERSION;
        $this->infos['Edition'] = 'Community'; // @TODO Where can i obtain this info?
    }

    protected function addVendors()
    {
        $vendors = [];

        $moduleList = $this->moduleList->getAll();

        foreach ($moduleList as $moduleName => $info) {
            // First index is (probably always) vendor
            $moduleNameData = explode('_', $moduleName);

            if (isset($moduleNameData[0])) {
                $vendors[] = $moduleNameData[0];
            }
        }

        $this->infos['Vendors'] = implode(', ', array_unique($vendors));
    }
}