<?php

namespace ondrs\Sauto;

use ondrs\Sauto\Structs\EquipmentErrorItem;
use ondrs\Sauto\Structs\ErrorItem;

class Exception extends \Exception
{

}


class ImageNotExistsException extends Exception
{

    /**
     * @param string $path
     * @return ImageNotExistsException
     */
    public static function fromFilePath($path)
    {
        return new self("Image '$path' does not exists or is corrupted.");
    }
}


class SautoApiException extends Exception
{

    /** @var ErrorItem[] */
    private $errorItems = [];

    /** @var EquipmentErrorItem[] */
    private $equipmentErrorItems;

    /** @var string */
    private $errorMessage;

    private $response = [];

    /**
     * @param array $response
     * @return SautoApiException
     */
    public static function fromResponse(array $response)
    {
        $exception = new self($response['status_message'], $response['status']);
        $exception->response = $response;

        if (array_key_exists('output', $response)) {

            if (array_key_exists('error', $response['output'])) {
                $exception->errorMessage = $response['output']['error'];
            }

            if (array_key_exists('error_items', $response['output']) && count($response['output']['error_items'])) {
                $exception->errorItems = array_map(function ($i) {
                    return new ErrorItem($i);
                }, $response['output']['error_items']);
            }

            if (array_key_exists('error_equipment', $response['output']) && count($response['output']['error_equipment'])) {
                $exception->equipmentErrorItems = array_map(function ($i) {
                    return new EquipmentErrorItem($i);
                }, $response['output']['error_equipment']);
            }
        }

        return $exception;
    }


    /**
     * @return Structs\ErrorItem[]
     */
    public function getErrorItems()
    {
        return $this->errorItems;
    }


    /**
     * @return Structs\EquipmentErrorItem[]
     */
    public function getEquipmentErrorItems()
    {
        return $this->equipmentErrorItems;
    }


    /**
     * @return Structs\EquipmentErrorItem[]
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }


    /**
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }

}
