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
        'license' => [$admin]
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