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

$admin=1; $secretary=2; $conductor=3;
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
        'license' => [$admin, $secretary]
]);
$map->post('postAddPersonas', $subdomain.'/peopleadd', [
        'controller' => 'App\Controllers\PersonasController',
        'action' => 'postAddPersonas',
        'auth' => true
]);
$map->get('getListPersonas', $subdomain.'/peoplelist', [
        'controller' => 'App\Controllers\PersonasController',
        'action' => 'getListPersonas',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->post('postBuscarPersonas', $subdomain.'/peoplesearch', [
        'controller' => 'App\Controllers\PersonasController',
        'action' => 'postBusquedaPersonas',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->get('getBuscarPersonas', $subdomain.'/peoplesearch', [
        'controller' => 'App\Controllers\PersonasController',
        'action' => 'postBusquedaPersonas',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->post('postDelPersonas', $subdomain.'/peopledel', [
        'controller' => 'App\Controllers\PersonasController',
        'action' => 'postUpdDelPersonas',
        'auth' => true
]);
$map->post('postUpdatePersonas', $subdomain.'/peopleupdate', [
        'controller' => 'App\Controllers\PersonasController',
        'action' => 'postUpdatePersonas',
        'auth' => true
]);

//Rutas PersonaDocumentos
$map->get('getAddDocumentos', $subdomain.'/documentsadd', [
        'controller' => 'App\Controllers\PersonaDocumentosController',
        'action' => 'getAddDocumentos',
        'auth' => true,
        'license' => [$admin]
]);
$map->post('postAddDocumentos', $subdomain.'/documentsadd', [
        'controller' => 'App\Controllers\PersonaDocumentosController',
        'action' => 'postAddDocumentos',
        'auth' => true
]);
$map->get('getListDocumentos', $subdomain.'/documentslist', [
        'controller' => 'App\Controllers\PersonaDocumentosController',
        'action' => 'getListDocumentos',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->post('postBuscarDocumentos', $subdomain.'/documentssearch', [
        'controller' => 'App\Controllers\PersonaDocumentosController',
        'action' => 'postBusquedaDocumentos',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->get('getBuscarDocumentos', $subdomain.'/documentssearch', [
        'controller' => 'App\Controllers\PersonaDocumentosController',
        'action' => 'postBusquedaDocumentos',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->post('postDelDocumentos', $subdomain.'/documentsdel', [
        'controller' => 'App\Controllers\PersonaDocumentosController',
        'action' => 'postUpdDelDocumentos',
        'auth' => true
]);
$map->post('postUpdateDocumentos', $subdomain.'/documentsupdate', [
        'controller' => 'App\Controllers\PersonaDocumentosController',
        'action' => 'postUpdateDocumentos',
        'auth' => true
]);


//Rutas PersonaLicencias
$map->get('getAddLicencias', $subdomain.'/licenseadd', [
        'controller' => 'App\Controllers\PersonaLicenciasController',
        'action' => 'getAddLicencias',
        'auth' => true,
        'license' => [$admin]
]);
$map->post('postAddLicencias', $subdomain.'/licenseadd', [
        'controller' => 'App\Controllers\PersonaLicenciasController',
        'action' => 'postAddLicencias',
        'auth' => true
]);
$map->get('getListLicencias', $subdomain.'/licenselist', [
        'controller' => 'App\Controllers\PersonaLicenciasController',
        'action' => 'getListLicencias',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->post('postBuscarLicencias', $subdomain.'/licensesearch', [
        'controller' => 'App\Controllers\PersonaLicenciasController',
        'action' => 'postBusquedaLicencias',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->get('getBuscarLicencias', $subdomain.'/licensesearch', [
        'controller' => 'App\Controllers\PersonaLicenciasController',
        'action' => 'postBusquedaLicencias',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->post('postDelLicencias', $subdomain.'/licensedel', [
        'controller' => 'App\Controllers\PersonaLicenciasController',
        'action' => 'postUpdDelLicencias',
        'auth' => true
]);
$map->post('postUpdateLicencias', $subdomain.'/licenseupdate', [
        'controller' => 'App\Controllers\PersonaLicenciasController',
        'action' => 'postUpdateLicencias',
        'auth' => true
]);


//Rutas PersonaRh
$map->get('getAddRh', $subdomain.'/rhadd', [
        'controller' => 'App\Controllers\PersonasRhController',
        'action' => 'getAddRh',
        'auth' => true,
        'license' => [$admin]
]);
$map->post('postAddRh', $subdomain.'/rhadd', [
        'controller' => 'App\Controllers\PersonasRhController',
        'action' => 'postAddRh',
        'auth' => true
]);
$map->get('getListRh', $subdomain.'/rhlist', [
        'controller' => 'App\Controllers\PersonasRhController',
        'action' => 'getListRh',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->post('postBuscarRh', $subdomain.'/rhsearch', [
        'controller' => 'App\Controllers\PersonasRhController',
        'action' => 'postBusquedaRh',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->post('postDelRh', $subdomain.'/rhdel', [
        'controller' => 'App\Controllers\PersonasRhController',
        'action' => 'postUpdDelRh',
        'auth' => true
]);
$map->post('postUpdateRh', $subdomain.'/rhupdate', [
        'controller' => 'App\Controllers\PersonasRhController',
        'action' => 'postUpdateRh',
        'auth' => true
]);





//Rutas Alistamientos
$map->get('getAddAlistamientos', $subdomain.'/checkadd', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'getAddAlistamientos',
        'auth' => true,
        'license' => [$admin]
]);
$map->get('getAddAlistamientos2', $subdomain.'/checkadd2', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'getAddAlistamientos2',
        'auth' => true,
        'license' => [$admin]
]);
$map->post('postAddAlistamientos', $subdomain.'/checkadd', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'postAddAlistamientos',
        'auth' => true
]);
$map->get('getListAlistamientos', $subdomain.'/checklist', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'getListAlistamientos',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->post('postBuscarAlistamientos', $subdomain.'/checksearch', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'postBusquedaAlistamientos',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->get('getBuscarAlistamientos', $subdomain.'/checksearch', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'postBusquedaAlistamientos',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->post('postDelAlistamientos', $subdomain.'/checkdel', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'postUpdDelAlistamientos',
        'auth' => true
]);
$map->post('postUpdateAlistamientos', $subdomain.'/checkupdate', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'postUpdateAlistamientos',
        'auth' => true
]);



//Rutas Vehiculos
$map->get('getAddVehiculos', $subdomain.'/vehicleadd', [
        'controller' => 'App\Controllers\VehiculosController',
        'action' => 'getAddVehiculos',
        'auth' => true,
        'license' => [$admin, $secretary]
]);
$map->post('postAddVehiculos', $subdomain.'/vehicleadd', [
        'controller' => 'App\Controllers\VehiculosController',
        'action' => 'postAddVehiculos',
        'auth' => true
]);
$map->get('getListVehiculos', $subdomain.'/vehiclelist', [
        'controller' => 'App\Controllers\VehiculosController',
        'action' => 'getListVehiculos',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->post('postBuscarVehiculos', $subdomain.'/vehiclesearch', [
        'controller' => 'App\Controllers\VehiculosController',
        'action' => 'postBusquedaVehiculos',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->get('getBuscarVehiculos', $subdomain.'/vehiclesearch', [
        'controller' => 'App\Controllers\VehiculosController',
        'action' => 'postBusquedaVehiculos',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->post('postDelVehiculos', $subdomain.'/vehicledel', [
        'controller' => 'App\Controllers\VehiculosController',
        'action' => 'postUpdDelVehiculos',
        'auth' => true
]);
$map->post('postUpdateVehiculos', $subdomain.'/vehicleupdate', [
        'controller' => 'App\Controllers\VehiculosController',
        'action' => 'postUpdateVehiculos',
        'auth' => true
]);


//Rutas vehiculoDocumentos
$map->get('getAddVehiculoDocumentos', $subdomain.'/vehicledocadd', [
        'controller' => 'App\Controllers\VehiculoDocumentosController',
        'action' => 'getAddDocumentos',
        'auth' => true,
        'license' => [$admin]
]);
$map->post('postAddVehiculoDocumentos', $subdomain.'/vehicledocadd', [
        'controller' => 'App\Controllers\VehiculoDocumentosController',
        'action' => 'postAddDocumentos',
        'auth' => true
]);
$map->get('getListVehiculoDocumentos', $subdomain.'/vehicledoclist', [
        'controller' => 'App\Controllers\VehiculoDocumentosController',
        'action' => 'getListDocumentos',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->post('postBuscarVehiculoDocumentos', $subdomain.'/vehicledocsearch', [
        'controller' => 'App\Controllers\VehiculoDocumentosController',
        'action' => 'postBusquedaDocumentos',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->get('getBuscarVehiculoDocumentos', $subdomain.'/vehicledocsearch', [
        'controller' => 'App\Controllers\VehiculoDocumentosController',
        'action' => 'postBusquedaDocumentos',
        'auth' => true,
        'license' => [$admin,$secretary]
]);
$map->post('postDelVehiculoDocumentos', $subdomain.'/vehicledocdel', [
        'controller' => 'App\Controllers\VehiculoDocumentosController',
        'action' => 'postUpdDelDocumentos',
        'auth' => true
]);
$map->post('postUpdateVehiculoDocumentos', $subdomain.'/vehicledocupdate', [
        'controller' => 'App\Controllers\VehiculoDocumentosController',
        'action' => 'postUpdateDocumentos',
        'auth' => true
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
$map->get('secretary', $subdomain.'/secretary', [
        'controller' => 'App\Controllers\AdminController',
        'action' => 'getSecrerary',
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