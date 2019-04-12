<?php 

namespace App\Controllers;

use Zend\Diactoros\Response\HtmlResponse;

class BaseController{
	protected $templateEngine;

	public function __construct(){
		$loader = new \Twig_Loader_Filesystem('../views');
		$this->templateEngine = new \Twig_Environment($loader, [
    		'debug' => true,
    		'cache' => false,
		]);
	}

	public function renderHTML($fileName, $data = []){

		$userName = $_SESSION['userName'] ?? null;
		$userRol = $_SESSION['userRol'] ?? null;
		$companyName = $_SESSION['companyName'] ?? null;

		$this->templateEngine->addGlobal('userName', $userName);
		$this->templateEngine->addGlobal('userRol', $userRol);
		$this->templateEngine->addGlobal('companyName', $companyName);
		return new HtmlResponse($this->templateEngine->render($fileName, $data));
	}
}

?>