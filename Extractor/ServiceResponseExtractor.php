<?php

namespace Draw\SwaggerBundle\Extractor;

use Doctrine\Common\Annotations\Reader;
use Draw\DrawBundle\Serializer\GroupHierarchy;
use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Extraction\ExtractorInterface;
use Draw\Swagger\Schema\Operation;
use Draw\Swagger\Schema\Response;
use Draw\Swagger\Schema\Schema;
use FOS\RestBundle\Controller\Annotations\View;
use JMS\Serializer\Exclusion\GroupsExclusionStrategy;
use Kfz24\Commons\ServiceCallBundle\ServiceResponse\OutgoingServiceResponse;
use Kfz24\Commons\ServiceCallBundle\ServiceResponse\OutgoingServiceResponseInterface;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use ReflectionMethod;

class ServiceResponseExtractor implements ExtractorInterface
{
    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * @var GroupHierarchy
     */
    private $groupHierarchy;

    public function __construct(Reader $reader, GroupHierarchy $groupHierarchy)
    {
        $this->annotationReader = $reader;
        $this->groupHierarchy = $groupHierarchy;
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
        if (!$source instanceof ReflectionMethod) {
            return false;
        }

        if (!$type instanceof Operation) {
            return false;
        }

        if (!$this->isServiceResponse($source)) {
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
     * @param \ReflectionMethod $source
     * @param Operation $type
     * @param ExtractionContextInterface $extractionContext
     */
    public function extract($source, $type, ExtractionContextInterface $extractionContext)
    {
        if (!$this->canExtract($source, $type, $extractionContext)) {
            throw new ExtractionImpossibleException();
        }

        $factory = DocBlockFactory::createInstance();
        $docBlock = $factory->create($source->getDocComment());
        $returnTags = $docBlock->getTagsByName('return');

        if (!isset($returnTags[0])) {
            return false;
        }

        $returnTag = $returnTags[0];
        /* @var $returnTag \phpDocumentor\Reflection\DocBlock\Tags\Return_ */
        $response = new Response();
        $response->schema = $responseSchema = new Schema();
        $response->description = $returnTag->getDescription();
        $response->schema->type = 'object';
        $type->responses[200] = $response;

        $subContext = $extractionContext->createSubContext();
        $subContext->setParameter('direction', 'out');

//            $serviceResponse = new \ReflectionClass(OutgoingServiceResponse::class);
//            $property = $serviceResponse->getProperty('result');
//            $property->setAccessible(true);
//            $property->setValue($serviceResponse, new JsonResult(['gabba' => null]));
        $subContext->setParameter('out-model-context', ['kfz24_service_response']);
        $extractionContext->getSwagger()->extract(OutgoingServiceResponse::class, $responseSchema, $subContext);

        //        $factory = DocBlockFactory::createInstance();
//        $docBlock = $factory->create($source->getDocComment());
//        /* @var $returnTag \phpDocumentor\Reflection\DocBlock\Tags\Return_ */
//        $returnTag = $docBlock->getTagsByName('return')[0];
//
//        $response = new Response();
//        $response->schema = $responseSchema = new Schema();
//        $response->description = $returnTag->getDescription();
//        $responseSchema->type = 'object';
//        $type->responses[200] = $response;
//
//        $subContext = $extractionContext->createSubContext();
//        $subContext->setParameter('direction', 'out');
//        $serviceResponse = $this->getServiceResponse($source);
//
//
//        $serializerGroups = $serviceResponse->getSerializerGroups();
//        $modelContext = $subContext->getParameter('out-model-context', []);
//        $modelContext['serializer-groups'] = $serializerGroups;
//        $groups = [];

//        if($view = $this->getServiceResponse($source)) {
//            $groups = $view->getSerializerGroups();
//        }
//
//        if (!empty($groups)) {
//            dump($groups);
//        }

//        if(empty($groups)) {
//            $groups = array(GroupsExclusionStrategy::DEFAULT_GROUP);
//        }
//
//        $subContext->setParameter('out-model-context', $modelContext);
//
//        $extractionContext->getSwagger()->extract($returnTag->getType(), $responseSchema, $subContext);
//        $groups = [];
//
//        if($view = $this->getServiceResponse($source)) {
//            $groups = $view->getSerializerGroups();
//        }
//
//        if(empty($groups)) {
//            $groups = array(GroupsExclusionStrategy::DEFAULT_GROUP);
//        }
//
//        $groups = $this->groupHierarchy->getReachableGroups($groups);
//
//        $modelContext = $extractionContext->getParameter('out-model-context', []);
//        $modelContext['serializer-groups'] = $groups;
//        $extractionContext->setParameter('out-model-context', $modelContext);
    }

    /**
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return View|null
     */
    private function getServiceResponse(ReflectionMethod $reflectionMethod)
    {
        $outgoingServiceResponse = $this->annotationReader->getMethodAnnotations($reflectionMethod);
        foreach ($outgoingServiceResponse as $item) {
            if ($item instanceof View) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return bool
     */
    private function isServiceResponse(ReflectionMethod $reflectionMethod)
    {
        $factory = DocBlockFactory::createInstance();
        $docBlock = $factory->create($reflectionMethod->getDocComment());
        $returnTags = $docBlock->getTagsByName('return');

        if (!isset($returnTags[0])) {
            return false;
        }

        $returnTag = $returnTags[0];
        $returnClass = trim((string) $returnTag);
        $returnClass = trim($returnClass, '\\');

        if ($returnClass === OutgoingServiceResponse::class) {
            return true;
        }

        if ($returnClass === OutgoingServiceResponseInterface::class) {
            return true;
        }

        return false;
    }
}
