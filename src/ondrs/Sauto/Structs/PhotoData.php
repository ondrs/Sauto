<?php


namespace ondrs\Sauto\Structs;


use ondrs\Sauto\ImageNotExistsException;
use fXmlRpc\Value\Base64;

class PhotoData extends BaseStruct
{

    public $photo_id = 0;
    public $main;
    public $alt;
    public $client_photo_id;
    // public $b64;


    /**
     * @param string $path
     * @return string
     * @throws ImageNotExistsException
     */
    public function loadImage($path)
    {
        if (!@getimagesize($path)) {
            throw ImageNotExistsException::fromFilePath($path);
        }

        $data = file_get_contents($path);
        $this->b64 = Base64::serialize($data);

        return $this->b64;
    }
}
