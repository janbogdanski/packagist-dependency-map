<?php

namespace Blog;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use MongoDB;

class GenerateD3NodesCommand extends Command
{
    protected function configure()
    {
 $this
        // the name of the command (the part after "bin/console")
        ->setName('generate:d3:nodes:for:packagist');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Dumping nodes from MongoDB");
        
        $dotenv = new Dotenv();
$dotenv->load('.env');

        
        $nodes = [];
        $links = [];
        
        $user = getenv('MONGO_USER');
        $pass = getenv('MONGO_PASS');
        $host = getenv('MONGO_HOST');
        
        $collection = (new MongoDB\Client("mongodb+srv://{$user}:{$pass}@{$host}"))->github_bck->packageDetailsNew;

//$documents = $collection->findOne(['name' => 'symfony/symfony']);
//        $documents = $collection->find(['downloads.total' => ['$gt' => 100000]]);
        $documents = $collection->find(['name' => 'symfony/symfony']);
        $documents = $collection->aggregate(
        [
            [
                '$match' => [
                    'name' => [
                        '$in' => ['symfony/symfony']]]
            ],
        
       [
                '$graphLookup' => [
                    'from' => "packageDetailsNew",
                    'startWith' => 'version.require',
                    'connectFromField' => 'version.require',
		            'connectToField' => 'name',
                    'as' => 'packagesHierarchy'
                ]
            ]
        ]
        );
        
    
      //  `db.packageDetails.find({}).sort({"downloads.total": -1}).limit(10)`

        foreach ($documents as $document) {
            print_r($document);
            $name = $document['name'];
            if(!isset($document['version']['require']) || empty((array)$document['version']['require']))
            {
                $output->writeln('package does not have required. '. $name);
                    continue;
            }
            $req = $document['version']['require'];
            
            $nodes[] = ['id' => $name];
            foreach ($req as $required) {

                $links[] = [
                'source' => $name,
                'target' => $required,
                'value' => 1,
            
            ];
                
            }
            
        }
        echo (count($nodes)), ' ', count($links);
    
        $result = [
            'nodes' => $nodes,
            'links' => $links,
        ];
        
        file_put_contents('out.json', json_encode($result));
    }
}