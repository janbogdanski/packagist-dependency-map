<?php

namespace App;

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
//        $documents = $collection->find(['name' => 'symfony/symfony']);
        $documents = $collection->aggregate(
            [
                [
                    '$match' => [
                        'name' => [
                            '$in' => ['symfony/symfony']]]
                ],

                [
                    '$graphLookup' => [
                        'from' => 'packageDetailsNew',
                        'startWith' => '$version.require',
                        'connectFromField' => 'version.require',
                        'connectToField' => 'name',
                        'as' => 'packagesHierarchy'
                    ]
                ]
            ]
        );


        //  `db.packageDetails.find({}).sort({"downloads.total": -1}).limit(10)`

        foreach ($documents as $document) {
//            print_r($document);
//
//            die();

            $name = $document['name'];
            if(!isset($document['version']['require']) || empty((array)$document['version']['require']))
            {
                $output->writeln('package does not have required. '. $name);
                continue;
            }
            $req = $document['version']['require'];

            $nodes[$name] = ['id' => $name, 'group' => 1];
            foreach ($req as $required) {
                if(strpos($required, '/') === false) {
                    continue;
                }

                $links[$name.$required] = [
                    'source' => $name,
                    'target' => $required,
                    'value' => 1,
                ];
            }

            foreach ($document['packagesHierarchy'] as $dependency) {
//                print_r($dependency);
                $dependencyName = $dependency['name'];
                $nodes[$dependencyName] = ['id' => $dependencyName, 'group' => 5];

                if(!isset($dependency['version']['require']) || empty((array)$dependency['version']['require']))
                {
                    $output->writeln('package does not have required. '. $dependencyName);
                    continue;
                }
                $req = $dependency['version']['require'];

                foreach ($req as $required) {
                    if(strpos($required, '/') === false) {
                        continue;
                    }

                    $links[$dependencyName.$required] = [
                        'source' => $dependencyName,
                        'target' => $required,
                        'value' => 1,
                    ];
                }
            }
        }
        echo (count($nodes)), ' ', count($links);

        $result = [
            'nodes' => array_values($nodes),
            'links' => array_values($links),
        ];

        file_put_contents('out.json', json_encode($result, JSON_PRETTY_PRINT));
    }
}