<?php

ini_set('display_errors',1);
ini_set('display_starup_error',1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';
require_once './mapRoute.php';

session_start();

$dotenv = Dotenv\Dotenv::create(__DIR__ . '/..');
$dotenv->load();

use Illuminate\Database\Capsule\Manager as Capsule;
use Aura\Router\RouterContainer;

$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => getenv('DB_DRIVER'),
    'host'      => getenv('DB_HOST'),
    'database'  => getenv('DB_NAME'),
    'username'  => getenv('DB_USER'),
    'password'  => getenv('DB_PASS'),
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();
// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$capsule->bootEloquent();

if(!$route){
    //si no se encuentra una ruta lo redirecciona dediante la variable $controllerName para que guarde la ruta de una pagina con diseño 404
    $controllerName = 'App\Controllers\NoRouteController';
    $actionName = 'getNoRoute';
    $controller = new $controllerName;
    $response = $controller->$actionName($request);
    echo $response->getBody();
}else{
    $handlerData = $route->handler;
    $controllerName = $handlerData['controller'];
    $actionName = $handlerData['action'];
    $needsAuth = $handlerData['auth'] ?? false;
    $sessionUserId = $_SESSION['userId'] ?? null;
    $sessionUserPermission = $_SESSION['userLicense'] ?? null;
    $permissions = $handlerData['license'] ?? null;
    $getLogout = false; 
    $thereIsPermission=false;

    if ($needsAuth && !$sessionUserId) {
      //si la pagina que quiere acceder solo pueden ingresar usuarios logeados y ademas No hay una session activa entonces lo redirecciona al login
      $controllerName = 'App\Controllers\AuthController';
      $actionName = 'getLogout';
      $getLogout=true; 
    }

    /*Primero evalua si ese mapRoute tiene permisos especiificos y luego evalua si rolId del usuario es uno de esos permisos permitidos y si el usuario no tiene uno de esos roles, lo lleva para el index donde evalua el rol que tiene y le da su backoffice adecuado a su permiso*/
    
    if ($permissions and !$getLogout) {

        foreach ($permissions as $permission) {
            if (in_array($permission, $sessionUserPermission)) {
                $thereIsPermission = true;
            }    
        }
        if (!$thereIsPermission) {
            //var_dump($sessionUserPermission);
            //echo " el permiso requerido es: ";
            //var_dump($permissions);
            $controllerName = 'App\Controllers\IndexController';
            $actionName = 'indexAction';
        }   
        
    }


    $controller = new $controllerName;
    $response = $controller->$actionName($request);

    foreach ($response->getHeaders() as $name => $values) {
      foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
      }
    }
    http_response_code($response->getStatusCode());

    echo $response->getBody();
}



?>