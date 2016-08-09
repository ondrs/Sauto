Sauto
==============

Unofficial Sauto API PHP wrapper


Instalation
-----

composer.json

    "ondrs/sauto": "0.1.0"

Usage
-----

Create a new Sauto instance and pass your software key as a parametr

        $sauto = new ondrs\Sauto\SautoApi('sauto.swKey')

Login

    $sauto->login('login', 'password');
    
Use methods according to the [API](http://www.sauto.cz/documents/xmlrpcImport.pdf) 

    $sauto->listOfCars();   // returns QuickCarData[]
    
Create/update vehicles

    $car = new CarData([
        'kind_id' => 1,
        'manufacturer_id' => 100,
        // etc ...
    ]);
    
    $carId = $sauto->addEditCar($car);  // returns int
    
    
And logout after you are done
 
    $sauto->logout();
