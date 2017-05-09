<?php

namespace Draw\SwaggerBundle\Extractor;

use Draw\Swagger\Schema\Response;
use Draw\Swagger\Schema\Schema;
use Kfz24\Commons\ServiceCallBundle\ServiceResponse\ServiceResponseConstants;
use Kfz24\Library\Exception\Swagger\AnnotationException;
use phpDocumentor\Reflection\DocBlock\Tags\Property;

/**
 * @Annotation
 */
class ServiceResponseAnnotation extends Response
{

    const SWG_KEY_RESULTS = 'results';

    const DEFAULT_RESPONSE_CODE = '200';
    const DEFAULT_DESCRIPTION = 'Service response';

    /**
     * @var array
     */
    public $result;

    /**
     * @param array $properties
     */
    public function __construct($properties = [])
    {
        $this->response = static::DEFAULT_RESPONSE_CODE;
        $this->description = static::DEFAULT_DESCRIPTION;

        $this->schema = $this->createSchema($properties);

        unset($properties[self::SWG_KEY_RESULTS]);
    }

    /**
     * @param $properties
     *
     * @return \Draw\Swagger\Schema\Schema
     */
    protected function createSchema($properties)
    {
        $schema = new Schema();
        $schema->type = 'object';
        $schema->properties = $this->createProperties($properties);
        $schema->required = [
            ServiceResponseConstants::KEY_STATUS,
            ServiceResponseConstants::KEY_MESSAGES,
        ];

        return $schema;
    }

    /**
     * @param array $properties
     * @return \Swagger\Annotations\Property[]
     */
    protected function createProperties($properties)
    {
        $data = [
            ServiceResponseConstants::KEY_STATUS => $this->createPropertyStatus(),
            ServiceResponseConstants::KEY_MESSAGES => $this->createPropertyMessages(),
        ];

        if (!empty($properties)) {
            $data[ServiceResponseConstants::KEY_RESULT] = $this->createPropertyResult($properties);
        }

        return $data;
    }

    /**
     * @return \Draw\Swagger\Schema\Schema
     */
    protected function createPropertyStatus()
    {
        $statusSchema = new Schema();
        $statusSchema->type = 'string';

        return $statusSchema;
    }

    /**
     * @return \Draw\Swagger\Schema\Schema
     */
    protected function createPropertyMessages()
    {
        $messageSchema = new Schema();
        $messageSchema->type = 'object';
        $messageSchema->ref = '#/definitions/outgoing_message';

        $messagesSchema = new Schema();
        $messagesSchema->type = 'array';
        $messagesSchema->items = $messagesSchema;

        return $messagesSchema;
    }

    /**
     * @param $properties
     *
     * @return \Draw\Swagger\Schema\Schema
     */
    protected function createPropertyResult($properties)
    {
        if (isset($properties[self::SWG_KEY_RESULTS])) {
            $statusSchema = new Schema();
            $statusSchema->type = 'object';
//            $statusSchema->
//            $statusSchema->ref = '#/definitions/' . $properties[self::SWG_KEY_RESULTS];

            return $statusSchema;

//            if (!is_array($properties[self::SWG_KEY_RESULTS])) {
//                throw new AnnotationException(
//                    sprintf('Swagger annotation "%s" has to be an array!', self::SWG_KEY_RESULTS)
//                );
//            }
//
//            $resultsConfig = $this->formatResultConfig($properties[self::SWG_KEY_RESULTS]);
//
//            foreach ($resultsConfig as $key => $definitionClassName) {
//                $resultProperties[] = new Property([
//                    'property' => $key,
//                    'ref' => '#/definitions/' . $definitionClassName,
//                ]);
//            }
        }
//
//        return new Property(
//            [
//                'property' => ServiceResponseConstants::KEY_RESULT,
//                'properties' => $resultProperties,
//            ]
//        );
    }

    /**
     * We allow mixed configs (short and explicit), like:
     *   results={"wishlist_item", "foo": "bar"}
     *
     * If only 1 value in config, means name and className are identical and can be omitted
     *
     * Will result in:
     *   wishlist_items => #/definitions/wishlist_items
     *   foo            => #/definitions/bar
     *
     * @param array $resultsConfig
     * @return array
     */
    protected function formatResultConfig(array $resultsConfig)
    {
        foreach ($resultsConfig as $key => $value) {
            if (is_numeric($key)) {
                $resultsConfig[$value] = $value;
                unset($resultsConfig[$key]);
            }
        }

        return $resultsConfig;
    }

    /**
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }
}
