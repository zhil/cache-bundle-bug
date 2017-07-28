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
            $i = $this->countEntities();
            if (($i - $currentEntitiesCount) > 200) {
                $output->writeln(sprintf('Loaded <error>%s</error> entities - too much', $i - $currentEntitiesCount));
            } else {
                $output->writeln(sprintf('Loaded <info>%s</info> entities.', $i - $currentEntitiesCount));
            }
            $currentEntitiesCount = $i;
        }

        $entitiesLimit = [
            Vendor::class => 10
        ];

        foreach ($entitiesLimit as $class => $limit) {
            $this->unloadEntities($class,$limit);
        }

        $fixturesContext = FixtureGenerationContext::create()
            ->setMaximumRecursion(1)
            ->setSkipNotInitializedCollections(true);
        $output->write('Generating dump ...');
        $yaml = $this->getContainer()->get('trappar_alice_generator.fixture_generator')->generateYaml(
            $this->getEntities($output),
            $fixturesContext
        );
        file_put_contents($input->getArgument('file'), PatchAliceGenerator::patchIssue12($yaml));
        $output->writeln('Done!');
    }

    private function countEntities()
    {
        $count = 0;
        foreach ($this->getContainer()->get('doctrine.orm.default_entity_manager')->getUnitOfWork()->getIdentityMap() as $entityType) {
            $count += count($entityType);
        }
        return $count;
    }

    private function getEntities(OutputInterface $output = null)
    {
        $entities = [];
        foreach ($this->getContainer()->get('doctrine.orm.default_entity_manager')->getUnitOfWork()->getIdentityMap() as $class=>$entityType) {
            if($output) {
                $output->writeln(sprintf('%s - %s',$class,count($entityType)));
            }
            $entities = array_merge($entities, $entityType);
        }
        return $entities;
    }

    private function unloadEntities($class, $allowedCount)
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $identityMap = $em->getUnitOfWork()->getIdentityMap();
        $entities = $identityMap[$class];
        if(count($entities) > $allowedCount) {
            foreach (array_slice($entities,$allowedCount) as $entity) {
                $em->detach($entity);
            }
            return $allowedCount;
        } else {
            return count($entities);
        }
    }
}