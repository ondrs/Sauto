<?php


namespace ondrs\Sauto;


use ondrs\Sauto\Structs\CarData;
use ondrs\Sauto\Structs\EquipmentErrorItem;
use ondrs\Sauto\Structs\PhotoData;
use ondrs\Sauto\Structs\ErrorItem;
use ondrs\Sauto\Structs\QuickCarData;
use ondrs\Sauto\Structs\QuickPhotoData;
use fXmlRpc\Client;

class SautoApi
{

    /** @var string */
    private $swKey;

    /** @var string */
    private $sessionId;


    /**
     * SautoApi constructor.
     * @param string $swKey
     * @param Client|NULL $client
     */
    public function __construct($swKey, Client $client = NULL)
    {
        $this->swKey = $swKey;
        $this->client = $client ?: new Client('http://import.sauto.cz/RPC2');
    }


    /**
     * @param array $response
     * @return bool
     * @throws SautoApiException
     */
    public static function validateResponse(array $response)
    {
        $allowed = [200, 210];

        if (!in_array((int)$response['status'], $allowed)) {
            throw SautoApiException::fromResponse($response);
        }

        return TRUE;
    }


    /**
     * @param $method
     * @param array $params
     * @return mixed
     * @throws SautoApiException
     */
    public function call($method, $params = [])
    {
        $response = $this->client->call($method, $params);

        self::validateResponse($response);

        return array_key_exists('output', $response)
            ? $response['output']
            : $response['status_message'];
    }


    /**
     * @internal
     * @param string $login
     * @return string
     */
    public function getHash($login)
    {
        return $this->call('getHash', ['login' => $login]);
    }


    /**
     * @param string $login
     * @param string $password
     * @return mixed
     */
    public function login($login, $password)
    {
        $hashOutput = $this->getHash($login);

        $result = $this->call('login', [
            'session_id' => $hashOutput['session_id'],
            'password' => md5(md5($password) . $hashOutput['hash_key']),
            'software_key' => $this->swKey,
        ]);

        $this->sessionId = $hashOutput['session_id'];

        return $result;
    }


    /**
     * @return string
     */
    public function logout()
    {
        $result = $this->call('logout', ['session_id' => $this->sessionId]);
        $this->sessionId = NULL;

        return $result;
    }


    /**
     * @return string
     */
    public function version()
    {
        return $this->call('version')['version'];
    }


    /**
     * @param CarData $carData
     * @return int
     */
    public function addEditCar(CarData $carData)
    {
        $output = $this->call('addEditCar', [
            'session_id' => $this->sessionId,
            'car_data' => $carData,
        ]);

        return (int)$output['car_id'];
    }


    /**
     * @param int $carId
     * @return CarData
     */
    public function getCar($carId)
    {
        $output = $this->call('getCar', [
            'session_id' => $this->sessionId,
            'car_id' => (int)$carId
        ]);

        return new CarData($output);
    }


    /**
     * @param string $customId
     * @return int
     */
    public function getCarId($customId)
    {
        $output = $this->call('getCarId', [
            'session_id' => $this->sessionId,
            'custom_id' => $customId,
        ]);

        return (int)$output['car_id'];
    }


    /**
     * @param int $carId
     * @return string
     */
    public function delCar($carId)
    {
        return $this->call('delCar', [
            'session_id' => $this->sessionId,
            'car_id' => (int)$carId,
        ]);
    }


    /**
     * @param string $all
     * @return QuickCarData[]
     */
    public function listOfCars($all = 'all')
    {
        $output = $this->call('listOfCars', [
            'session_id' => $this->sessionId,
            'all' => $all,
        ]);

        return array_map(function ($i) {
            return new QuickCarData($i);
        }, $output['list_of_cars']);
    }


    /**
     * @param $carId
     * @param PhotoData $photoData
     * @return int
     */
    public function addEditPhoto($carId, PhotoData $photoData)
    {
        // API will thrown an error if b64 is sent as NULL
        // the property has to be removed completely
        if ($photoData->b64 === NULL) {
            unset($photoData->b64);
        }

        $output = $this->call('addEditPhoto', [
            'session_id' => $this->sessionId,
            'car_id' => (int)$carId,
            'photo_data' => $photoData,
        ]);
        
        return (int)$output['photo_id'];
    }


    /**
     * @param int $photoId
     * @return string
     */
    public function delPhoto($photoId)
    {
        return $this->call('delPhoto', [
            'session_id' => $this->sessionId,
            'photo_id' => (int)$photoId,
        ]);
    }


    /**
     * @param int $carId
     * @param string $clientPhotoId
     * @return int
     */
    public function getPhotoId($carId, $clientPhotoId)
    {
        $output = $this->call('getPhotoId', [
            'session_id' => $this->sessionId,
            'car_id' => (int)$carId,
            'client_photo_id' => $clientPhotoId,
        ]);

        return (int)$output['photo_id'];
    }


    /**
     * @param int $carId
     * @return QuickPhotoData[]
     */
    public function listOfPhotos($carId)
    {
        $output = $this->call('listOfPhotos', [
            'session_id' => $this->sessionId,
            'car_id' => (int)$carId,
        ]);

        $photos = array_map(function ($i) {
            return new QuickPhotoData($i);
        }, $output['list_of_photos']);

        usort($photos, function(QuickPhotoData $a, QuickPhotoData $b) {
            return $a->main === $b->main
                ? 0
                : $a->main < $b->main
                    ? -1
                    : 1;
        });

        return $photos;
    }


    /**
     * @param int $carId
     * @param array $equipment
     */
    public function addEquipment($carId, array $equipment)
    {
        $this->call('addEquipment', [
            'session_id' => $this->sessionId,
            'car_id' => (int)$carId,
            'equipment' => $equipment,
        ]);
    }


    /**
     * @param int $carId
     * @return int[]
     */
    public function listOfEquipment($carId)
    {
        $output = $this->call('listOfEquipment', [
            'session_id' => $this->sessionId,
            'car_id' => (int)$carId,
        ]);

        return $output['equipment'];
    }
}
