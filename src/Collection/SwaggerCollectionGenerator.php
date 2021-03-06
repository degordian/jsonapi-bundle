<?php
declare(strict_types=1);

namespace Bornfight\JsonApiBundle\Collection;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Bornfight\JsonApiBundle\Collection\Swagger\Attributes;
use Bornfight\JsonApiBundle\Collection\Swagger\Paths;
use Bornfight\JsonApiBundle\Collection\Swagger\Swagger;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Yaml\Yaml;

class SwaggerCollectionGenerator extends CollectionGeneratorAbstract
{
    /** @var Swagger */
    private $swagger;
    /** @var Attributes */
    private $fields;

    const SWAGGER_PATH = 'collections/%s.yaml';
    const SWAGGER_TEMPLATE_PATH = __DIR__.'/../Resources/skeleton/swagger.yaml';

    public function generateCollection(ClassMetadata $classMetadata, string $entityName, string $route): string
    {
        $this->swagger = new Swagger($this->loadOldCollection());

        $this->fields = Attributes::parse($classMetadata);

        $this->setDefinitions($entityName);
        $this->generateAllPaths($entityName, $route);

        $this->fileManager->dumpFile(sprintf(self::SWAGGER_PATH, Str::asCommand($entityName)), Yaml::dump($this->swagger->toArray(), 20, 2));

        return self::SWAGGER_PATH;
    }

    private function generateAllPaths(string $entityName, string $route): void
    {
        $paths = Paths::buildPaths($this->getActionsList($entityName), $entityName, $route, $this->fields);

        foreach ($paths as $path => $content) {
            $this->swagger->addPath($path, $content);
        }
    }

    private function setDefinitions(string $entityName): void
    {
        $this->swagger->addDefinition($entityName, $this->fields->getFieldsSchema());

        foreach ($this->fields->getRelationsSchemas() as $name => $schema) {
            $this->swagger->addDefinition($name, $schema);
        }
    }

    private function loadOldCollection(): array
    {
        if (file_exists($this->rootDirectory.'/'.self::SWAGGER_PATH)) {
            $file = $this->rootDirectory.'/'.self::SWAGGER_PATH;
        } else {
            $file = self::SWAGGER_TEMPLATE_PATH;
        }

        return Yaml::parseFile($file);
    }
}
