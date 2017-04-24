<?php

namespace Draw\SwaggerBundle\Extractor;

use Doctrine\Common\Annotations\Reader;
use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Extraction\ExtractorInterface;
use Draw\Swagger\Schema\Operation;
use Draw\Swagger\Schema\PathItem;
use Draw\Swagger\Schema\Swagger as SwaggerSchema;
use Draw\Swagger\Schema\Tag;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class SymfonyContainerSwaggerExtractor implements ExtractorInterface
{
    public function __construct(Reader $reader)
    {
        $this->annotationReader = $reader;
    }

    /**
     * Return if the extractor can extract the requested data or not.
     *
     * @param $source
     * @param $type
     * @param ExtractionContextInterface $extractionContext
     * @return boolean
     */
    public function canExtract($source, $type, ExtractionContextInterface $extractionContext)
    {
        if (!$source instanceof ContainerInterface) {
            return false;
        }

        if (!$type instanceof SwaggerSchema) {
            return false;
        }

        return true;
    }

    /**
     * Extract the requested data.
     *
     * The system is a incrementing extraction system. A extractor can be call before you and you must complete the
     * extraction.
     *
     * @param ContainerInterface $source
     * @param SwaggerSchema $type
     * @param ExtractionContextInterface $extractionContext
     */
    public function extract($source, $type, ExtractionContextInterface $extractionContext)
    {
        if (!$this->canExtract($source, $type, $extractionContext)) {
            throw new ExtractionImpossibleException();
        }

        $this->triggerRouteExtraction($source, $type, $extractionContext);
    }

    private function triggerRouteExtraction(
        ContainerInterface $source,
        SwaggerSchema $schema,
        ExtractionContextInterface $extractionContext
    ) {
        $router = $source->get('router');

        foreach ($router->getRouteCollection() as $operationId => $route) {
            /* @var \Symfony\Component\Routing\Route $route */
            if(!($path = $route->getPath())) {
                continue;
            }

            $controller = explode('::', $route->getDefault('_controller'));

            if(count($controller) != 2) {
                $controllerAsService = explode(':', $route->getDefault('_controller'));

                if (count($controllerAsService) === 2) {
                    $method = $controllerAsService[1];
                    $class = get_class($source->get($controllerAsService[0]));
                } else {
                    continue;
                }
            } else {
                list($class, $method) = $controller;
            }


            $reflectionMethod = new \ReflectionMethod($class, $method);

            if(!$this->isSwaggerRoute($route, $reflectionMethod)) {
                continue;
            }

            $operation = new Operation();

            $operation->operationId = $operationId;

            $extractionContext->getSwagger()->extract($route, $operation, $extractionContext);
            $extractionContext->getSwagger()->extract($reflectionMethod, $operation, $extractionContext);

            if(!isset($schema->paths[$path])) {
                $schema->paths[$path] = new PathItem();
            }

            $pathItem = $schema->paths[$path];

            foreach($route->getMethods() as $method) {
                $pathItem->{strtolower($method)} = $operation;
            }
        }
    }

    private function isSwaggerRoute(Route $route, \ReflectionMethod $method)
    {
        if ($route->getDefault('_swagger')) {
            return true;
        }

        foreach($this->annotationReader->getMethodAnnotations($method) as $annotation) {
            if($annotation instanceof Tag) {
                return true;
            }
        }

        return false;
    }
}