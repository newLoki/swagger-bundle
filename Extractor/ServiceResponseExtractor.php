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
use Kfz24\Library\Swagger\Annotations\ServiceResponse;
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

        if (!$this->getServiceResponse($source)) {
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

        $serviceResponse = $this->getServiceResponse($source);

//        /* @var $returnTag \phpDocumentor\Reflection\DocBlock\Tags\Return_ */
        $response = $serviceResponse;
//        $response->schema = $responseSchema = new Schema();
//        $response->schema->type = 'object';
        $type->responses[200] = $response;
        $serviceResponse->getResult();

        $extractionContext->getRootSchema()->addDefinition($serviceResponse->getResult(), $refSchema = new Schema());
//        $refSchema->type = "object";

//        $type->responses[] = $serviceResponse;
//        $subContext = $extractionContext->createSubContext();
//        $subContext->setParameter('direction', 'out');
//        $subContext->setParameter('out-model-context', ['kfz24_service_response']);

        $extractionContext->getSwagger()->extract($serviceResponse, $type, $subContext);
    }

    /**
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return ServiceResponseAnnotation
     */
    private function getServiceResponse(ReflectionMethod $reflectionMethod)
    {
        $methodAnnotations = $this->annotationReader->getMethodAnnotations($reflectionMethod);

        foreach ($methodAnnotations as $annotation) {
            if ($annotation instanceof ServiceResponseAnnotation) {
                return $annotation;
            }
        }

        return null;
    }
}
