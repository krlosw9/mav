<?php

namespace App\Controllers;

use App\Models\{User, Personas, PersonaRoles, PersonaContrasenas, PersonaPermisosRoles};
use Respect\Validation\Validator as v;
use Zend\Diactoros\Response\RedirectResponse;

class AuthController extends BaseController{
	public function getLogin(){
		return $this->renderHTML('login.twig');
	}

	public function postLogin($request){
		$i=0; $arrayPermisos = array();
		$postData = $request->getParsedBody();

		$responseMessage = null;

		try{
			//Consulta si el nombre de usuario (email) esta en base de datos y si lo esta trae el primer registro que encuentra con todos sus datos idCedula, nombre, contrasena y otros para compararlos contra los que se trae en el $request que pasa a ser $postData['email']

			//$user = User::where('nombre',$postData['email'])->first();
			$user = PersonaContrasenas::Join("persona.personas","persona.contrasenas.perid","=","persona.personas.id")
			->select('persona.contrasenas.*', 'persona.personas.nombre', 'persona.personas.apellido', 'persona.personas.numerodocumento', 'persona.personas.rolid')
			->where("persona.contrasenas.activocheck","=",1)
			->where('numerodocumento',$postData['numerodocumento'])
			->first();

			if ($user) {
				if(\password_verify($postData['pass'], $user->pass)){
					
					$permisos = PersonaPermisosRoles::Join("persona.permisos","persona.permisosroles.idpermiso","=","persona.permisos.id")
					->select('persona.permisosroles.*', 'persona.permisos.identificadorpermiso')
					->where("persona.permisosroles.idrol","=",$user->rolid)
					->get();

					foreach ($permisos as $permiso) {
						$arrayPermisos[$i] = $permiso->identificadorpermiso;
						$i++;
					}

					$userRol = PersonaRoles::where("id","=",$user->rolid)->first();					

					$_SESSION['userId'] = $user->perid;
					$_SESSION['companyName'] = 'SSoftrans';
					$_SESSION['userName'] = $user->nombre.' '.$user->apellido;
					$_SESSION['userRol'] = $userRol->nombrerol;
					$_SESSION['userRolId'] = $user->rolid;
					$_SESSION['userLicense'] = $arrayPermisos;

					/*$people = Personas::Join("persona.roles","persona.personas.rolid","=","persona.roles.id")
					->select('persona.personas.*', 'persona.roles.nombrerol')
					->where('numerodocumento',$postData['numerodocumento'])
					->first();*/

						return new RedirectResponse('admin');

					
				}else{
					$responseMessage = 'Usuario y Contraseña incorrecto';
				}
			}else{
				$responseMessage = 'Usuario y Contraseña incorrecto';
			}
		}catch(\Exception $e){
			$prevMessage = substr($e->getMessage(), 0, 47);
			if ($prevMessage =="SQLSTATE[23000]: Integrity constraint violation") {
				$responseMessage = 'Error.';
			}else{
				$responseMessage = substr($e->getMessage(), 0, 47);
				$responseMessage = $e->getMessage();
			}
		}
		return $this->renderHTML('login.twig',[
			'responseMessage' => $responseMessage
		]);
	}


	public function getLogout(){
		unset($_SESSION['userId']);
		unset($_SESSION['companyName']);
		unset($_SESSION['userName']);
		unset($_SESSION['userRol']);
		unset($_SESSION['userRolId']);
		unset($_SESSION['userLicense']);
		
		return new RedirectResponse('login');
	}
}