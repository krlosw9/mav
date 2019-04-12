<?php 

namespace App\Controllers;

use App\Models\User;
use Zend\Diactoros\Response\HtmlResponse;


class NoRouteController extends BaseController{
	
	public function getNoRoute(){
		return $this->renderHTML('NoRoute.twig');
	}
}

?>