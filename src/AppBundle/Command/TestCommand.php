<?php

namespace AppBundle\Command;

use Vido\CoreBundle\Entity\Vendor;
use Vido\ErcBundle\Tests\PatchAliceGenerator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Client;
use Trappar\AliceGenerator\FixtureGenerationContext;

class TestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:test')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Serialization of 'Closure' is not allowed
        $testUrls = [
            '/',
        ];
        $currentEntitiesCount = 0;
        foreach ($testUrls as $url) {
            $client = new Client($this->getApplication()->getKernel());
            $output->writeln(sprintf('Fetching <comment>%s</comment>', $url));
            $client->request('GET', $url);
        }
    }
}