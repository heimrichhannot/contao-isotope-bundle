<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Command;

use Contao\CoreBundle\Command\AbstractLockedCommand;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Model\Collection;
use HeimrichHannot\IsotopeBundle\Manager\ProductDataManager;
use HeimrichHannot\IsotopeBundle\Model\ProductDataModel;
use HeimrichHannot\IsotopeBundle\Model\ProductModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpgradeCommand extends AbstractLockedCommand
{
    protected static $defaultName = 'huh:isotope:upgrade';

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;
    /**
     * @var ProductDataManager
     */
    private $productDataManager;

    public function __construct(ContaoFrameworkInterface $framework, ProductDataManager $productDataManager)
    {
        $this->framework = $framework;
        $this->productDataManager = $productDataManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('huh:isotope:upgrade')
            ->setDescription('Setup Isotope Bundle product data from older bundle version or module install.')
            ->setHelp('This command import data from product table to product data table. This must be done when upgrading from older bundle version or module.')
            ->addOption(
                'overwriteExistingEntries',
                'o',
                InputOption::VALUE_NONE,
                'Also update data of existing entries. Attention: all existing data will be overwritten!')
        ;
    }

    /**
     * Executes the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function executeLocked(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Starting import product data.');
        $this->framework->initialize();
//        $output->writeln("Starting import product data.");
        /** @var ProductDataModel[]|Collection|null $products */
        $products = $this->framework->getAdapter(ProductModel::class)->findAll();

        $io->newLine();
        $io->progressStart($products->count());
        $dataAdded = 0;
        $dataUpdated = 0;
        $dataSkipped = 0;
        foreach ($products as $product) {
            $io->progressAdvance();
            /** @var ProductDataModel $productData */
            $productData = $this->framework->getAdapter(ProductDataModel::class)->findByPid($product->id);
            if ($productData and !$input->getOption('overwriteExistingEntries')) {
                ++$dataSkipped;
                continue;
            }
            if (!$productData) {
                ++$dataAdded;
                $productData = new ProductDataModel();
                $productData->pid = $product->id;
                $productData->tstamp = $productData->dateAdded = time();
            } else {
                ++$dataUpdated;
            }
            $dataFields = $this->productDataManager->getProductDataFields();
            foreach ($dataFields as $key => $value) {
                $productData->$key = $product->$key;
            }
            $productData->tstamp = time();
            $productData->save();
        }
        $io->progressFinish();

        $io->newLine();
        $io->section('Result:');

        $io->table(['ProductData', 'Count'], [
            ['Added', $dataAdded],
            ['Updated', $dataUpdated],
            ['Skipped', $dataSkipped],
        ]);

        $io->success('Upgrade command finished.');

        return 0;
    }
}
