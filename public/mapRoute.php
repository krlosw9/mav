<?php

ini_set('display_errors',1);
ini_set('display_starup_error',1);
error_reporting(E_ALL);

//require_once '../vendor/autoload.php';
use Aura\Router\RouterContainer;
$routerContainer = new RouterContainer();

$request = Zend\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$admin=1; $manager=2; $secretary=3; $checking=4; $conductor=5;
$subdomain='/mav';

$map = $routerContainer->getMap();
//Ruta raiz o index
$map->get('index', $subdomain.'/', [
        'controller' => 'App\Controllers\IndexController',
        'action' => 'indexAction',
        'auth' => true
]);


//Rutas Personas
$map->get('getAddPersonas', $subdomain.'/peopleadd', [
        'controller' => 'App\Controllers\PersonasController',
        'action' => 'getAddPersonas',
        'auth' => true,
        'license' => ['peopleadd']
]);
$map->post('postAddPersonas', $subdomain.'/peopleadd', [
        'controller' => 'App\Controllers\PersonasController',
        'action' => 'postAddPersonas',
        'auth' => true,
        'license' => ['peopleadd']
]);
$map->get('getListPersonas', $subdomain.'/peoplelist', [
        'controller' => 'App\Controllers\PersonasController',
        'action' => 'getListPersonas',
        'auth' => true,
        'license' => ['peoplelist']
]);
$map->post('postBuscarPersonas', $subdomain.'/peoplesearch', [
        'controller' => 'App\Controllers\PersonasController',
        'action' => 'postBusquedaPersonas',
        'auth' => true,
        'license' => ['peoplelist']
]);
$map->get('getBuscarPersonas', $subdomain.'/peoplesearch', [
        'controller' => 'App\Controllers\PersonasController',
        'action' => 'postBusquedaPersonas',
        'auth' => true,
        'license' => ['peoplelist']
]);
$map->post('postDelPersonas', $subdomain.'/peopledel', [
        'controller' => 'App\Controllers\PersonasController',
        'action' => 'postUpdDelPersonas',
        'auth' => true,
        'license' => ['peopleupd', 'peopledel', 'documentslist', 'licenselist']
]);
$map->post('postUpdatePersonas', $subdomain.'/peopleupdate', [
        'controller' => 'App\Controllers\PersonasController',
        'action' => 'postUpdatePersonas',
        'auth' => true,
        'license' => ['peopleupd']
]);

//Rutas PersonaDocumentos
$map->get('getAddDocumentos', $subdomain.'/documentsadd', [
        'controller' => 'App\Controllers\PersonaDocumentosController',
        'action' => 'getAddDocumentos',
        'auth' => true,
        'license' => ['documentsadd']
]);
$map->post('postAddDocumentos', $subdomain.'/documentsadd', [
        'controller' => 'App\Controllers\PersonaDocumentosController',
        'action' => 'postAddDocumentos',
        'auth' => true,
        'license' => ['documentsadd']
]);
$map->get('getListDocumentos', $subdomain.'/documentslist', [
        'controller' => 'App\Controllers\PersonaDocumentosController',
        'action' => 'getListDocumentos',
        'auth' => true,
        'license' => ['documentslist']
]);
$map->post('postBuscarDocumentos', $subdomain.'/documentssearch', [
        'controller' => 'App\Controllers\PersonaDocumentosController',
        'action' => 'postBusquedaDocumentos',
        'auth' => true,
        'license' => ['documentslist']
]);
$map->get('getBuscarDocumentos', $subdomain.'/documentssearch', [
        'controller' => 'App\Controllers\PersonaDocumentosController',
        'action' => 'postBusquedaDocumentos',
        'auth' => true,
        'license' => ['documentslist']
]);
$map->post('postDelDocumentos', $subdomain.'/documentsdel', [
        'controller' => 'App\Controllers\PersonaDocumentosController',
        'action' => 'postUpdDelDocumentos',
        'auth' => true,
        'license' => ['documentsdel', 'documentsupdate']
]);
$map->post('postUpdateDocumentos', $subdomain.'/documentsupdate', [
        'controller' => 'App\Controllers\PersonaDocumentosController',
        'action' => 'postUpdateDocumentos',
        'auth' => true,
        'license' => ['documentsupdate']
]);


//Rutas PersonaLicencias
$map->get('getAddLicencias', $subdomain.'/licenseadd', [
        'controller' => 'App\Controllers\PersonaLicenciasController',
        'action' => 'getAddLicencias',
        'auth' => true,
        'license' => ['licenseadd']
]);
$map->post('postAddLicencias', $subdomain.'/licenseadd', [
        'controller' => 'App\Controllers\PersonaLicenciasController',
        'action' => 'postAddLicencias',
        'auth' => true,
        'license' => ['licenseadd']
]);
$map->get('getListLicencias', $subdomain.'/licenselist', [
        'controller' => 'App\Controllers\PersonaLicenciasController',
        'action' => 'getListLicencias',
        'auth' => true,
        'license' => ['licenselist']
]);
$map->post('postBuscarLicencias', $subdomain.'/licensesearch', [
        'controller' => 'App\Controllers\PersonaLicenciasController',
        'action' => 'postBusquedaLicencias',
        'auth' => true,
        'license' => ['licenselist']
]);
$map->get('getBuscarLicencias', $subdomain.'/licensesearch', [
        'controller' => 'App\Controllers\PersonaLicenciasController',
        'action' => 'postBusquedaLicencias',
        'auth' => true,
        'license' => ['licenselist']
]);
$map->post('postDelLicencias', $subdomain.'/licensedel', [
        'controller' => 'App\Controllers\PersonaLicenciasController',
        'action' => 'postUpdDelLicencias',
        'auth' => true,
        'license' => ['licensedel','licenseupdate']
]);
$map->post('postUpdateLicencias', $subdomain.'/licenseupdate', [
        'controller' => 'App\Controllers\PersonaLicenciasController',
        'action' => 'postUpdateLicencias',
        'auth' => true,
        'license' => ['licenseupdate']
]);


//Rutas PersonaRh
$map->get('getAddRh', $subdomain.'/rhadd', [
        'controller' => 'App\Controllers\PersonasRhController',
        'action' => 'getAddRh',
        'auth' => true,
        'license' => ['rhadd']
]);
$map->post('postAddRh', $subdomain.'/rhadd', [
        'controller' => 'App\Controllers\PersonasRhController',
        'action' => 'postAddRh',
        'auth' => true,
        'license' => ['rhadd']
]);
$map->get('getListRh', $subdomain.'/rhlist', [
        'controller' => 'App\Controllers\PersonasRhController',
        'action' => 'getListRh',
        'auth' => true,
        'license' => ['rhlist']
]);
$map->post('postBuscarRh', $subdomain.'/rhsearch', [
        'controller' => 'App\Controllers\PersonasRhController',
        'action' => 'postBusquedaRh',
        'auth' => true,
        'license' => ['rhlist']
]);
$map->post('postDelRh', $subdomain.'/rhdel', [
        'controller' => 'App\Controllers\PersonasRhController',
        'action' => 'postUpdDelRh',
        'auth' => true,
        'license' => ['rhdel','rhupdate']
]);
$map->post('postUpdateRh', $subdomain.'/rhupdate', [
        'controller' => 'App\Controllers\PersonasRhController',
        'action' => 'postUpdateRh',
        'auth' => true,
        'license' => ['rhupdate']
]);





//Rutas Alistamientos
$map->get('getAddAlistamientos', $subdomain.'/checkadd', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'getAddAlistamientos',
        'auth' => true,
        'license' => ['checkadd']
]);
$map->post('postAddAlistamientos', $subdomain.'/checkadd', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'postAddAlistamientos',
        'auth' => true,
        'license' => ['checkadd']
]);
$map->get('getListAlistamientos', $subdomain.'/checklist', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'getListAlistamientos',
        'auth' => true,
        'license' => ['checklist']
]);
$map->post('postBuscarAlistamientos', $subdomain.'/checksearch', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'postBusquedaAlistamientos',
        'auth' => true,
        'license' => ['checklist']
]);
$map->get('getBuscarAlistamientos', $subdomain.'/checksearch', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'postBusquedaAlistamientos',
        'auth' => true,
        'license' => ['checklist']
]);
$map->post('postDelAlistamientos', $subdomain.'/checkdel', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'postUpdDelAlistamientos',
        'auth' => true,
        'license' => ['checkdel','checkupdate']
]);
$map->post('postUpdateAlistamientos', $subdomain.'/checkupdate', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'postUpdateAlistamientos',
        'auth' => true,
        'license' => ['checkupdate']
]);



//Rutas Vehiculos
$map->get('getAddVehiculos', $subdomain.'/vehicleadd', [
        'controller' => 'App\Controllers\VehiculosController',
        'action' => 'getAddVehiculos',
        'auth' => true,
        'license' => ['vehicleadd']
]);
$map->post('postAddVehiculos', $subdomain.'/vehicleadd', [
        'controller' => 'App\Controllers\VehiculosController',
        'action' => 'postAddVehiculos',
        'auth' => true,
        'license' => ['vehicleadd']
]);
$map->get('getListVehiculos', $subdomain.'/vehiclelist', [
        'controller' => 'App\Controllers\VehiculosController',
        'action' => 'getListVehiculos',
        'auth' => true,
        'license' => ['vehiclelist']
]);
$map->post('postBuscarVehiculos', $subdomain.'/vehiclesearch', [
        'controller' => 'App\Controllers\VehiculosController',
        'action' => 'postBusquedaVehiculos',
        'auth' => true,
        'license' => ['vehiclelist']
]);
$map->get('getBuscarVehiculos', $subdomain.'/vehiclesearch', [
        'controller' => 'App\Controllers\VehiculosController',
        'action' => 'postBusquedaVehiculos',
        'auth' => true,
        'license' => ['vehiclelist']
]);
$map->post('postDelVehiculos', $subdomain.'/vehicledel', [
        'controller' => 'App\Controllers\VehiculosController',
        'action' => 'postUpdDelVehiculos',
        'auth' => true,
        'license' => ['vehicledel', 'vehicleupdate', 'vehicledoclist', 'vehiclepeoplelist']
]);
$map->post('postUpdateVehiculos', $subdomain.'/vehicleupdate', [
        'controller' => 'App\Controllers\VehiculosController',
        'action' => 'postUpdateVehiculos',
        'auth' => true,
        'license' => ['vehicleupdate']
]);


//Rutas vehiculoDocumentos
$map->get('getAddVehiculoDocumentos', $subdomain.'/vehicledocadd', [
        'controller' => 'App\Controllers\VehiculoDocumentosController',
        'action' => 'getAddDocumentos',
        'auth' => true,
        'license' => ['vehicledocadd']
]);
$map->post('postAddVehiculoDocumentos', $subdomain.'/vehicledocadd', [
        'controller' => 'App\Controllers\VehiculoDocumentosController',
        'action' => 'postAddDocumentos',
        'auth' => true,
        'license' => ['vehicledocadd']
]);
$map->get('getListVehiculoDocumentos', $subdomain.'/vehicledoclist', [
        'controller' => 'App\Controllers\VehiculoDocumentosController',
        'action' => 'getListDocumentos',
        'auth' => true,
        'license' => ['vehicledoclist']
]);
$map->post('postListVehiculoDocumentos', $subdomain.'/vehicledoclist', [
        'controller' => 'App\Controllers\VehiculoDocumentosController',
        'action' => 'getListDocumentos',
        'auth' => true,
        'license' => ['vehicledoclist']
]);
$map->post('postBuscarVehiculoDocumentos', $subdomain.'/vehicledocsearch', [
        'controller' => 'App\Controllers\VehiculoDocumentosController',
        'action' => 'postBusquedaDocumentos',
        'auth' => true,
        'license' => ['vehicledoclist']
]);
$map->get('getBuscarVehiculoDocumentos', $subdomain.'/vehicledocsearch', [
        'controller' => 'App\Controllers\VehiculoDocumentosController',
        'action' => 'postBusquedaDocumentos',
        'auth' => true,
        'license' => ['vehicledoclist']
]);
$map->post('postDelVehiculoDocumentos', $subdomain.'/vehicledocdel', [
        'controller' => 'App\Controllers\VehiculoDocumentosController',
        'action' => 'postDelDocumentos',
        'auth' => true,
        'license' => ['vehicledocdel']
]);
$map->post('postUpdVehiculoDocumentos', $subdomain.'/vehicledocupd', [
        'controller' => 'App\Controllers\VehiculoDocumentosController',
        'action' => 'postUpdDocumentos',
        'auth' => true,
        'license' => ['vehicledocupdate']
]);
$map->post('postUpdateVehiculoDocumentos', $subdomain.'/vehicledocupdate', [
        'controller' => 'App\Controllers\VehiculoDocumentosController',
        'action' => 'postUpdateDocumentos',
        'auth' => true,
        'license' => ['vehicledocupdate']
]);

//Rutas vehiculoPersonas
$map->get('getAddVehiculoPersonas', $subdomain.'/vehiclepeopleadd', [
        'controller' => 'App\Controllers\VehiculoVehiculosPersonasController',
        'action' => 'getAddVehiculosPersonas',
        'auth' => true,
        'license' => ['vehiclepeopleadd']
]);
$map->post('postAddVehiculoPersonas', $subdomain.'/vehiclepeopleadd', [
        'controller' => 'App\Controllers\VehiculoVehiculosPersonasController',
        'action' => 'postAddVehiculosPersonas',
        'auth' => true,
        'license' => ['vehiclepeopleadd']
]);
$map->get('getListVehiculoPersonas', $subdomain.'/vehiclepeoplelist', [
        'controller' => 'App\Controllers\VehiculoVehiculosPersonasController',
        'action' => 'getListVehiculosPersonas',
        'auth' => true,
        'license' => ['vehiclepeoplelist']
]);
$map->post('postListVehiculoPersonas', $subdomain.'/vehiclepeoplelist', [
        'controller' => 'App\Controllers\VehiculoVehiculosPersonasController',
        'action' => 'getListVehiculosPersonas',
        'auth' => true,
        'license' => ['vehiclepeoplelist']
]);
$map->post('postDelVehiculoPersonas', $subdomain.'/vehiclepeopledel', [
        'controller' => 'App\Controllers\VehiculoVehiculosPersonasController',
        'action' => 'postDelVehiculosPersonas',
        'auth' => true,
        'license' => ['vehiclepeopledel']
]);
$map->post('postUpdVehiculoPersonas', $subdomain.'/vehiclepeopleupd', [
        'controller' => 'App\Controllers\VehiculoVehiculosPersonasController',
        'action' => 'postUpdVehiculosPersonas',
        'auth' => true,
        'license' => ['vehiclepeopleupdate']
]);
$map->post('postUpdateVehiculoPersonas', $subdomain.'/vehiclepeopleupdate', [
        'controller' => 'App\Controllers\VehiculoVehiculosPersonasController',
        'action' => 'postUpdateVehiculosPersonas',
        'auth' => true,
        'license' => ['vehiclepeopleupdate']
]);







//Prueba de Vistas
$map->get('getViews', $subdomain.'/forms', [
        'controller' => 'App\Controllers\GetViews',
        'action' => 'getForms',
        'auth' => true,
]);
$map->get('getList', $subdomain.'/list', [
        'controller' => 'App\Controllers\GetViews',
        'action' => 'getList',
        'auth' => true,
]);






//Rutas que validan el login, dan acceso o denega acceso
$map->get('loginForm', $subdomain.'/login', [
        'controller' => 'App\Controllers\AuthController',
        'action' => 'getLogin'
]);
$map->post('auth', $subdomain.'/auth', [
        'controller' => 'App\Controllers\AuthController',
        'action' => 'postLogin'
]);
$map->get('admin', $subdomain.'/admin', [
        'controller' => 'App\Controllers\AdminController',
        'action' => 'getIndex',
        'auth' => true
]);
$map->get('logout', $subdomain.'/logout', [
        'controller' => 'App\Controllers\AuthController',
        'action' => 'getLogout'
]);
$map->get('noRoute', $subdomain.'/noRoute', [
        'controller' => 'App\Controllers\NoRouteController',
        'action' => 'getNoRoute'
]);


$matcher = $routerContainer->getMatcher();
$route = $matcher->match($request);


?>