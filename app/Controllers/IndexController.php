<?php 

namespace App\Controllers;

use App\Models\{Roles};
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Zend\Diactoros\Response\RedirectResponse;

class IndexController extends BaseController{
	public function indexAction(){

		try{
			$userRol = Roles::where("id","=",$_SESSION['userRolId'])->first();
			if ($_SESSION['userRolId']) {
				if ($userRol->ruta) {
					return new RedirectResponse($userRol->ruta);

				}else{
					$responseMessage = "Este usuario no tiene una ruta asignada, en el pie de pagina esta toda la información de contacto, por favor contáctenos para darle una rápida solución. ";
				}	
			}else{
				return new RedirectResponse('login');
			}
			
			
		} catch (\Exception $e) {
			$responseMessage = 'Error, '.substr($e->getMessage(), 0, 33);
		}
		return $this->renderHTML('index.twig',[
			'responseMessage' => $responseMessage
		]);
	}
}

?>