<?php 

namespace App\Controllers;

use App\Models\{Vehiculos,Personas, VehiculoRolPersonaVehiculo, VehiculoVehiculosPersonas};
use App\Controllers\{FilesValidatorController};
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Zend\Diactoros\Response\RedirectResponse;

class VehiculoVehiculosPersonasController extends BaseController{
	
	//estos dos valores son los que se cambian, para modificar la cantidad de registros listados por pagina y el maximo numero en paginacion
	private $articulosPorPagina=10;
	private $limitePaginacion=20;
	private $mensajePersonaNoDefinida="Vehículo no definido, por favor seleccione un vehículo y luego presione el botón personas";


	public function getAddVehiculosPersonas(){
		$responseMessage=null; $ruta='vehiculoVehiculosPersonasAdd.twig'; $roles=null;

		$idVeh = $_GET['?'] ?? null;

		if ($idVeh) {
            $roles = VehiculoRolPersonaVehiculo::orderBy('nombre')->get();
            $vehiculo = Vehiculos::find($idVeh);
            $personas = Personas::where("persona.personas.activocheck","=",1)->orderBy('nombre')->get();
		}else{
			$responseMessage = $this->mensajePersonaNoDefinida;
			$ruta='vehiculoVehiculosPersonasList.twig';
		}

		return $this->renderHTML($ruta,[
				'responseMessage' => $responseMessage,
				'idVeh' => $idVeh,
				'roles' => $roles,
				'vehiculo' => $vehiculo,
				'personas' => $personas
		]);
	}

	//Registra el Documento
	public function postAddVehiculosPersonas($request){
		$responseMessage = null; $registrationErrorMessage=null; $ruta='vehiculoVehiculosPersonasList.twig';
		$documentos = null; $numeroDePaginas=null; $idVeh=0; $vehiculo=null; 


		if($request->getMethod()=='POST'){
			//crea el array $postData pasando las variables POST como indices de este array
			$postData = $request->getParsedBody();
			

			$documentoValidator = v::key('idVeh', v::numeric()->positive()->notEmpty())
					->key('perid', v::numeric()->positive()->notEmpty())
					->key('idrolpersonavehiculo', v::numeric()->positive()->notEmpty());
			
			$idVeh = $postData['idVeh'] ?? null;
			
			if($_SESSION['userId']){
				
				try{
					$documentoValidator->assert($postData);
					$postData = $request->getParsedBody();

					$documento = new VehiculoVehiculosPersonas();
					$documento->vehid=$postData['idVeh'];
					$documento->perid = $postData['perid'];
					$documento->idrolpersonavehiculo = $postData['idrolpersonavehiculo'];
					$documento->iduserregister = $_SESSION['userId'];
					$documento->iduserupdate = $_SESSION['userId'];
					$documento->save();

					$responseMessage = 'Registrado';
				}catch(\Exception $exception){
					//$responseMessage = $exception->getMessage();
					$prevMessage = substr($exception->getMessage(), 0, 25);
					if ($prevMessage == "SQLSTATE[23505]: Unique v") {
						$responseMessage = 'Error, La referencia ya esta registrada';
					}elseif ($prevMessage == "SQLSTATE[23503]: Foreign ") {
						$responseMessage = 'Error, El ID de este vehiculo no esta registrado';
					}elseif ($prevMessage == "SQLSTATE[42703]: Undefine") {
						$responseMessage = 'Error interno de base de datos, en el pie de pagina esta toda la información de contacto, por favor contáctenos para darle una rápida solución.';
					}elseif($prevMessage == 'These rules must pass for' or $prevMessage == 'All of the required rules') {
						$registrationErrorMessage = $exception->findMessages([
						'notEmpty' => '- Los campos con (*) no pueden estar vacios',
						'numeric' => '- Error relacional de base de datos', 
						'positive' => '- Valores que no pertenecen a la entidad relacion'
						]);
					}else{
						$responseMessage = $prevMessage;
					}
				}
				

			}
		}
		$paginador = $this->paginador($idVeh);
		$vehiculo = $paginador['vehiculo'];
		if ($responseMessage=='Registrado') {
			$documentos = $paginador['documentos'];
			$numeroDePaginas = $paginador['numeroDePaginas'];	
		}

		return $this->renderHTML($ruta,[
				'idVeh' => $idVeh,
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'documentos' => $documentos,
				'numeroDePaginas' => $numeroDePaginas,
				'vehiculo' => $vehiculo
		]);
	}

 
	//Lista todas los modelos Ordenando por posicion
	public function paginador($idVeh=0, $paginaActual=0){
		$retorno = array(); $iniciar=0; $documentos=null; $vehiculo=null;
		
		$numeroDeFilas = VehiculoVehiculosPersonas::selectRaw('count(*) as query_count')
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
		
		$documentos = VehiculoVehiculosPersonas::Join("persona.personas","vehiculo.vehiculospersonas.perid","=","persona.personas.id")
		//->Join("vehiculo.vehiculos","vehiculo.vehiculospersonas.vehid","=","vehiculo.vehiculos.id")
		->Join("vehiculo.rolpersonavehiculo","vehiculo.vehiculospersonas.idrolpersonavehiculo","=","vehiculo.rolpersonavehiculo.id")
        ->where("vehiculo.vehiculospersonas.vehid","=",$idVeh)
        ->select('vehiculo.vehiculospersonas.*', 'persona.personas.numerodocumento', 'persona.personas.nombre', 'persona.personas.apellido', 'persona.personas.celular', 'vehiculo.rolpersonavehiculo.nombre As rol')
        ->latest('vehiculo.vehiculospersonas.id')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		/* Consulta toda la informacion de la vehiculo a la cual se le estan buscando los Documentos */
		$vehiculo = Vehiculos::find($idVeh);

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'documentos' => $documentos,
			'vehiculo' => $vehiculo
		];

		return $retorno;
		
	}

	public function getListVehiculosPersonas(){
		$responseMessage=null; $numeroDePaginas=null; $documentos=null; $numeroDePaginas=null; $vehiculo=null;
		
		//Se utiliza esta linea Si este metodo es invocado por el metodo get desde vehiculoDocumentosList.twig
		$idVeh = $_GET['?'] ?? null;

		//Se utiliza esta linea si el metodo es invocado desde vehiculosList.twig, cuando el usuario seleccionar un vehiculo y le da en el boton documentos
		if (!$idVeh) {
			$idVeh = $_POST['id'] ?? null;
			
		}

		$paginaActual = $_GET['pag'] ?? null;

		if ($idVeh) {
			$paginador = $this->paginador($idVeh, $paginaActual);
		
			$documentos = $paginador['documentos'];
			$numeroDePaginas = $paginador['numeroDePaginas'];	
			$vehiculo = $paginador['vehiculo'];

		}else{
			$responseMessage = $this->mensajePersonaNoDefinida;
		}
		
		
		return $this->renderHTML('vehiculoVehiculosPersonasList.twig', [
			'idVeh' => $idVeh,
			'responseMessage' => $responseMessage,
			'documentos' => $documentos,
			'numeroDePaginas' => $numeroDePaginas,
			'paginaActual' => $paginaActual,
			'vehiculo' => $vehiculo
		]);
	}

	public function postDelVehiculosPersonas($request){
		$documentos=null; $responseMessage = null; $id=null; $numeroDePaginas=null; $vehiculo=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$id = $postData['btnDelUpd'] ?? null;
			$idVeh = $postData['idVeh'] ?? null;
			
			if ($id) {
				try{
					$people = new VehiculoVehiculosPersonas();
					$people->destroy($id);
					$responseMessage = "se desvinculo la persona del vehículo";
				}catch(\Exception $e){
					//$responseMessage = $e->getMessage();
					$prevMessage = substr($e->getMessage(), 0, 38);
					if ($prevMessage =="SQLSTATE[23503]: Foreign key violation") {
						$responseMessage = 'Error, No se puede desvincular, esta persona esta en uso.';
					}else{
						$responseMessage= 'Error, No se puede desvincular, '.$prevMessage;
					}
				}
			}else{
				$responseMessage = 'Debe Seleccionar una persona';
			}
		}
		
		$paginador = $this->paginador($idVeh);

		$documentos = $paginador['documentos'];
		$numeroDePaginas = $paginador['numeroDePaginas'];
		$vehiculo = $paginador['vehiculo'];
		
		return $this->renderHTML('vehiculoVehiculosPersonasList.twig', [
			'responseMessage' => $responseMessage,
			'numeroDePaginas' => $numeroDePaginas,
			'documentos' => $documentos,
			'idVeh' => $idVeh,
			'vehiculo' => $vehiculo
		]);
	}


	public function postUpdVehiculosPersonas($request){	
		$vehiculoPersona=null; $personas=null; $roles=null; $responseMessage = null; $id=null; 
		$numeroDePaginas=null; $vehiculo=null;


		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$idVeh = $postData['idVeh'] ?? null;
			//Este es el ID del registro a editar
			$id = $postData['btnDelUpd'] ?? null;
			
			if ($id) {
				$vehiculoPersona = VehiculoVehiculosPersonas::find($id);
			  	$vehiculo = Vehiculos::find($vehiculoPersona->vehid);
			  	$personas = Personas::orderBy('nombre')->get();
				$roles = VehiculoRolPersonaVehiculo::orderBy('nombre')->get();
			  
			}else{
				$paginador = $this->paginador($idVeh);

				$vehiculoPersona = $paginador['documentos'];
				$numeroDePaginas = $paginador['numeroDePaginas'];
				$vehiculo = $paginador['vehiculo'];

				$responseMessage = 'Debe Seleccionar una persona';
			}
		}
		
		return $this->renderHTML('vehiculoVehiculosPersonasUpdate.twig', [
			'vehiculoPersona' => $vehiculoPersona,
			'vehiculo' => $vehiculo,
			'personas' => $personas,
			'roles' => $roles,
			'responseMessage' => $responseMessage,
			'numeroDePaginas' => $numeroDePaginas,
			'idVeh' => $idVeh
		]);
	}

	//en esta accion se registra las modificaciones del registro utiliza metodo post no get
	public function postUpdateVehiculosPersonas($request){
		$responseMessage = null; $registrationErrorMessage=null; $documentos=null; $numeroDePaginas=null; $vehiculo=null;
		$ruta='vehiculoVehiculosPersonasList.twig';

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();

			$idVeh = $postData['idVeh'] ?? null;

			$documentoValidator = v::key('idVeh', v::numeric()->positive()->notEmpty())
					->key('perid', v::numeric()->positive()->notEmpty())
					->key('idrolpersonavehiculo', v::numeric()->positive()->notEmpty());
			
			if($_SESSION['userId']){

				try{
					$documentoValidator->assert($postData);
					$postData = $request->getParsedBody();

					//la siguiente linea hace una consulta en la DB y trae el registro where id=$id y lo guarda en documento y posteriormente remplaza los valores y con el ->save() guarda la modificacion en la DB
					$idDocumento = $postData['id'] ?? null;
					$documento = VehiculoVehiculosPersonas::find($idDocumento);
					
					$documento->vehid=$postData['idVeh'];
					$documento->perid = $postData['perid'];
					$documento->idrolpersonavehiculo = $postData['idrolpersonavehiculo'];
					$documento->iduserupdate = $_SESSION['userId'];
					$documento->save();

					$responseMessage = 'Editado.';
				}catch(\Exception $exception){
					//$responseMessage = $exception->getMessage();
					$prevMessage = substr($exception->getMessage(), 0, 25);
					if ($prevMessage == "SQLSTATE[23505]: Unique v") {
						$responseMessage = 'Error, La referencia ya esta registrada';
					}elseif ($prevMessage == "SQLSTATE[23503]: Foreign ") {
						$responseMessage = 'Error, El ID de este vehiculo no esta registrado';
					}elseif ($prevMessage == "SQLSTATE[42703]: Undefine") {
						$responseMessage = 'Error interno de base de datos, en el pie de pagina esta toda la información de contacto, por favor contáctenos para darle una rápida solución.';
					}elseif($prevMessage == 'These rules must pass for' or $prevMessage == 'All of the required rules') {
						$registrationErrorMessage = $exception->findMessages([
						'notEmpty' => '- Los campos con (*) no pueden estar vacios',
						'numeric' => '- Solo puede contener numeros', 
						'positive' => '- Solo puede contener numeros mayores a cero'
						]);
					}else{
						$responseMessage = $prevMessage;
					}
				}
			}
		}

		$paginador = $this->paginador($idVeh);
		$vehiculo = $paginador['vehiculo'];
		if ($responseMessage == 'Editado.') {
			$documentos = $paginador['documentos'];
			$numeroDePaginas = $paginador['numeroDePaginas'];
		}
		

		return $this->renderHTML($ruta,[
				'idVeh' => $idVeh,
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'documentos' => $documentos,
				'numeroDePaginas' => $numeroDePaginas,
				'vehiculo' => $vehiculo
		]);
	}
}

?>
