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
        'action' => 'postDelPersonas',
        'auth' => true,
        'license' => ['peopledel']
]);
$map->post('postUpdPersonas', $subdomain.'/peopleupd', [
        'controller' => 'App\Controllers\PersonasController',
        'action' => 'postUpdPersonas',
        'auth' => true,
        'license' => ['peopleupd']
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
$map->post('postListDocumentos', $subdomain.'/documentslist', [
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
        'action' => 'postDelDocumentos',
        'auth' => true,
        'license' => ['documentsdel']
]);
$map->post('postUpdDocumentos', $subdomain.'/documentsupd', [
        'controller' => 'App\Controllers\PersonaDocumentosController',
        'action' => 'postUpdDocumentos',
        'auth' => true,
        'license' => ['documentsupdate']
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
$map->post('postListLicencias', $subdomain.'/licenselist', [
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
        'action' => 'postDelLicencias',
        'auth' => true,
        'license' => ['licensedel']
]);
$map->post('postUpdLicencias', $subdomain.'/licenseupd', [
        'controller' => 'App\Controllers\PersonaLicenciasController',
        'action' => 'postUpdLicencias',
        'auth' => true,
        'license' => ['licenseupdate']
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
        'action' => 'postDelRh',
        'auth' => true,
        'license' => ['rhdel']
]);
$map->post('postUpdRh', $subdomain.'/rhupd', [
        'controller' => 'App\Controllers\PersonasRhController',
        'action' => 'postUpdRh',
        'auth' => true,
        'license' => ['rhupdate']
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
$map->get('getSelectVehiculoAlistamiento', $subdomain.'/selectvehicle', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'getSelectVehiculoAlistamiento',
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
        'action' => 'postDelAlistamientos',
        'auth' => true,
        'license' => ['checkdel']
]);
$map->post('postUpdAlistamientos', $subdomain.'/checkupd', [
        'controller' => 'App\Controllers\AlistamientosController',
        'action' => 'postUpdAlistamientos',
        'auth' => true,
        'license' => ['checkupdate']
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
        'action' => 'postDelVehiculos',
        'auth' => true,
        'license' => ['vehicledel']
]);
$map->post('postUpdVehiculos', $subdomain.'/vehicleupd', [
        'controller' => 'App\Controllers\VehiculosController',
        'action' => 'postUpdVehiculos',
        'auth' => true,
        'license' => ['vehicleupdate']
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

//Rutas Incidentes
$map->get('getAddIncidentes', $subdomain.'/incidentadd', [
        'controller' => 'App\Controllers\AccidentesIncidentesController',
        'action' => 'getAddIncidentes',
        'auth' => true,
        'license' => ['incidentadd']
]);
$map->post('postAddIncidentes', $subdomain.'/incidentadd', [
        'controller' => 'App\Controllers\AccidentesIncidentesController',
        'action' => 'postAddIncidentes',
        'auth' => true,
        'license' => ['incidentadd']
]);
$map->get('getListIncidentes', $subdomain.'/incidentlist', [
        'controller' => 'App\Controllers\AccidentesIncidentesController',
        'action' => 'getListIncidentes',
        'auth' => true,
        'license' => ['incidentlist']
]);
$map->post('postBuscarIncidentes', $subdomain.'/incidentsearch', [
        'controller' => 'App\Controllers\AccidentesIncidentesController',
        'action' => 'postBusquedaIncidentes',
        'auth' => true,
        'license' => ['incidentlist']
]);
$map->get('getBuscarIncidentes', $subdomain.'/incidentsearch', [
        'controller' => 'App\Controllers\AccidentesIncidentesController',
        'action' => 'postBusquedaIncidentes',
        'auth' => true,
        'license' => ['incidentlist']
]);
$map->post('postDelIncidentes', $subdomain.'/incidentdel', [
        'controller' => 'App\Controllers\AccidentesIncidentesController',
        'action' => 'postDelIncidentes',
        'auth' => true,
        'license' => ['incidentdel']
]);
$map->post('postUpdIncidentes', $subdomain.'/incidentupd', [
        'controller' => 'App\Controllers\AccidentesIncidentesController',
        'action' => 'postUpdIncidentes',
        'auth' => true,
        'license' => ['incidentupdate']
]);
$map->post('postUpdateIncidentes', $subdomain.'/incidentupdate', [
        'controller' => 'App\Controllers\AccidentesIncidentesController',
        'action' => 'postUpdateIncidentes',
        'auth' => true,
        'license' => ['incidentupdate']
]);


//Rutas Comparendos
$map->get('getAddComparendos', $subdomain.'/subpoenaadd', [
        'controller' => 'App\Controllers\ComparendosController',
        'action' => 'getAddComparendos',
        'auth' => true,
        'license' => ['subpoenaadd']
]);
$map->post('postAddComparendos', $subdomain.'/subpoenaadd', [
        'controller' => 'App\Controllers\ComparendosController',
        'action' => 'postAddComparendos',
        'auth' => true,
        'license' => ['subpoenaadd']
]);
$map->get('getListComparendos', $subdomain.'/subpoenalist', [
        'controller' => 'App\Controllers\ComparendosController',
        'action' => 'getListComparendos',
        'auth' => true,
        'license' => ['subpoenalist']
]);
$map->post('postBuscarComparendos', $subdomain.'/subpoenasearch', [
        'controller' => 'App\Controllers\ComparendosController',
        'action' => 'postBusquedaComparendos',
        'auth' => true,
        'license' => ['subpoenalist']
]);
$map->get('getBuscarComparendos', $subdomain.'/subpoenasearch', [
        'controller' => 'App\Controllers\ComparendosController',
        'action' => 'postBusquedaComparendos',
        'auth' => true,
        'license' => ['subpoenalist']
]);
$map->post('postDelComparendos', $subdomain.'/subpoenadel', [
        'controller' => 'App\Controllers\ComparendosController',
        'action' => 'postDelComparendos',
        'auth' => true,
        'license' => ['subpoenadel']
]);
$map->post('postUpdComparendos', $subdomain.'/subpoenaupd', [
        'controller' => 'App\Controllers\ComparendosController',
        'action' => 'postUpdComparendos',
        'auth' => true,
        'license' => ['subpoenaupdate']
]);
$map->post('postUpdateComparendos', $subdomain.'/subpoenaupdate', [
        'controller' => 'App\Controllers\ComparendosController',
        'action' => 'postUpdateComparendos',
        'auth' => true,
        'license' => ['subpoenaupdate']
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