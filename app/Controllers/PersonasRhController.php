<?php 

namespace App\Controllers;

use App\Models\{Personas, PersonaRh};
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Zend\Diactoros\Response\RedirectResponse;

class PersonasRhController extends BaseController{
	
	//estos dos valores son los que se cambian, para modificar la cantidad de registros listados por pagina y el maximo numero en paginacion
	private $articulosPorPagina=10;
	private $limitePaginacion=20;

	public function getAddRh(){
	

		return $this->renderHTML('personaRhAdd.twig');
	}

	//Registra la Persona
	public function postAddRh($request){
		$responseMessage = null; $prevMessage=null; $registrationErrorMessage=null;
		$rhs = null; $numeroDePaginas=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			
			$personaValidator = v::key('nombre', v::stringType()->length(1, 20)->notEmpty());
			
			
			if($_SESSION['userId']){
				try{
					$personaValidator->assert($postData);
					$postData = $request->getParsedBody();

					$rh = new PersonaRh();
					$rh->nombre = $postData['nombre'];
					$rh->iduserregister = $_SESSION['userId'];
					$rh->iduserupdate = $_SESSION['userId'];
					$rh->save();

					$responseMessage = 'Registrado';
				}catch(\Exception $exception){
					$prevMessage = substr($exception->getMessage(), 0, 25);

					if ($prevMessage == "SQLSTATE[23505]: Unique v") {
						$responseMessage = 'Error, El numero del documento ya esta registrado';
					}elseif ($prevMessage == "SQLSTATE[42703]: Undefine") {
						$responseMessage = 'Error interno de base de datos, en el pie de pagina esta toda la información de contacto, por favor contáctenos para darle una rápida solución.';
					}elseif ($prevMessage == "SQLSTATE[23503]: Foreign ") {
						$responseMessage = 'Error relacional de base de datos, en el pie de pagina esta toda la información de contacto, por favor contáctenos para darle una rápida solución.';
					}elseif($prevMessage == 'These rules must pass for' or $prevMessage == 'All of the required rules') {
						$registrationErrorMessage = $exception->findMessages([
						'notEmpty' => '- Los campos con (*) no pueden estar vacios',
						'length' => '- Tiene una longitud no permitida',
						'stringType' => '- Solo puede contener numeros y letras'
						]) ?? null;
					}else{
							$responseMessage = $prevMessage;
					}
				}
			}
		}
		if ($responseMessage=='Registrado') {
			$paginador = $this->paginador();
			$numeroDePaginas=$paginador['numeroDePaginas'];
			$rhs=$paginador['rhs'];
		}

		return $this->renderHTML('personaRhList.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'numeroDePaginas' => $numeroDePaginas,
				'rhs' => $rhs
		]);
	}

	//Lista todas los modelos Ordenando por posicion
	public function getListRh(){
		$responseMessage = null; $rhs=null; $numeroDePaginas=null;

		$paginaActual = $_GET['pag'] ?? null;		
		$paginador = $this->paginador($paginaActual);
		$numeroDePaginas=$paginador['numeroDePaginas'];
		$rhs=$paginador['rhs'];

		return $this->renderHTML('personaRhList.twig', [
			'rhs' => $rhs,
			'numeroDePaginas' => $numeroDePaginas,
			'paginaActual' => $paginaActual
		]);
		
	}

	public function paginador($paginaActual=null){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $rhs=null;
		
		$numeroDeFilas = PersonaRh::selectRaw('count(*) as query_count')
		->first();
		
		$totalFilasDb = $numeroDeFilas->query_count;
		$numeroDePaginas = $totalFilasDb/$this->articulosPorPagina;
		$numeroDePaginas = ceil($numeroDePaginas);

		//No permite que haya muchos botones de paginar y de esa forma va a traer una cantidad limitada de registro, no queremos que se pagine hasta el infinito, porque tambien puede ser molesto.
		if ($numeroDePaginas > $this->limitePaginacion) {
			$numeroDePaginas=$this->limitePaginacion;
		}

		if ($paginaActual) {
			if ($paginaActual > $numeroDePaginas or $paginaActual < 1) {
				$paginaActual = 1;
			}
			$iniciar = ($paginaActual-1)*$this->articulosPorPagina;
		}

		$rhs = PersonaRh::orderBy('nombre')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'rhs' => $rhs

		];

		return $retorno;
	}


	public function postDelRh($request){
		$rhs=null; $numeroDePaginas=null; $id=null; $responseMessage = null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$id = $postData['btnDelUpd'] ?? null;

			if ($id) {	 
			  try{
				$people = new PersonaRh();
				$people->destroy($id);
				$responseMessage = "Se elimino el Rh";
			  }catch(\Exception $e){
			  	//$responseMessage = $e->getMessage();
			  	$prevMessage = substr($e->getMessage(), 0, 38);
				if ($prevMessage =="SQLSTATE[23503]: Foreign key violation") {
					$responseMessage = 'Error, No se puede eliminar, este Rh esta en uso.';
				}else{
					$responseMessage= 'Error, No se puede eliminar, '.$prevMessage;
				}
			  }
			}else{
				$responseMessage = 'Debe Seleccionar una persona';
			}
		}
		
		$paginador = $this->paginador();
		$numeroDePaginas=$paginador['numeroDePaginas'];
		$rhs=$paginador['rhs'];
		
		return $this->renderHTML('personaRhList.twig', [
			'numeroDePaginas' => $numeroDePaginas,
			'rhs' => $rhs,
			'responseMessage' => $responseMessage
		]);
	}


	public function postUpdRh($request){
		$rhs=null; $numeroDePaginas=null; $id=null; $responseMessage = null;
		$ruta='personaRhUpdate.twig';

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$id = $postData['btnDelUpd'] ?? null;

			if ($id) {
				$rhs = PersonaRh::find($id);  
			}else{
				$paginador = $this->paginador();
				$numeroDePaginas=$paginador['numeroDePaginas'];
				$rhs=$paginador['rhs'];

				$ruta='personaRhList.twig';
				$responseMessage = 'Debe Seleccionar una persona';
			}
		}
		
		return $this->renderHTML($ruta, [
			'numeroDePaginas' => $numeroDePaginas,
			'rhs' => $rhs,
			'responseMessage' => $responseMessage
		]);
	}

	//en esta accion se registra las modificaciones del registro utiliza metodo post no get
	public function postUpdateRh($request){
		$responseMessage = null; $registrationErrorMessage=null; $rhs=null; $numeroDePaginas=null;
				
		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();

			$personaValidator = v::key('nombre', v::stringType()->length(1, 50)->notEmpty());

			
			if($_SESSION['userId']){
				try{
					$personaValidator->assert($postData);
					$postData = $request->getParsedBody();

					//la siguiente linea hace una consulta en la DB y trae el registro where id=$id y lo guarda en rh y posteriormente remplaza los valores y con el ->save() guarda la modificacion en la DB
					$id = $postData['id'];
					$rh = PersonaRh::find($id);
					
					$rh->nombre = $postData['nombre'];
					$rh->iduserupdate = $_SESSION['userId'];
					$rh->save();

					$responseMessage = 'Editado.';
				}catch(\Exception $exception){
					$prevMessage = substr($exception->getMessage(), 0, 25);

					if ($prevMessage == "SQLSTATE[23505]: Unique v") {
						$responseMessage = 'Error, El numero del documento ya esta registrado';
					}elseif ($prevMessage == "SQLSTATE[42703]: Undefine") {
						$responseMessage = 'Error interno de base de datos, en el pie de pagina esta toda la información de contacto, por favor contáctenos para darle una rápida solución.';
					}elseif ($prevMessage == "SQLSTATE[23503]: Foreign ") {
						$responseMessage = 'Error relacional de base de datos, en el pie de pagina esta toda la información de contacto, por favor contáctenos para darle una rápida solución.';
					}elseif($prevMessage == 'These rules must pass for' or $prevMessage == 'All of the required rules') {
						$registrationErrorMessage = $exception->findMessages([
						'notEmpty' => '- Los campos con (*) no pueden estar vacios',
						'length' => '- Tiene una longitud no permitida',
						'stringType' => '- Solo puede contener numeros y letras'
						]) ?? null;
					}else{
							$responseMessage = $prevMessage;
					}
				}
			}
		}

		if ($responseMessage == 'Editado.') {
			$paginador = $this->paginador();
			$numeroDePaginas=$paginador['numeroDePaginas'];
			$rhs=$paginador['rhs'];
		}

		return $this->renderHTML('personaRhList.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'numeroDePaginas' => $numeroDePaginas,
				'rhs' => $rhs
		]);
	}
}

?>
