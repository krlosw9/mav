<?php 

namespace App\Controllers;

use App\Models\{AccidentesIncidentes, Vehiculos, AccidenteClasificacion, AccidenteTiposaccinc};
use App\Controllers\{DocumentosController};
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Zend\Diactoros\Response\RedirectResponse;

class AccidentesIncidentesController extends BaseController{
	
	//estos dos valores son los que se cambian, para modificar la cantidad de registros listados por pagina y el maximo numero en paginacion
	private $articulosPorPagina=10;
	private $limitePaginacion=20;

	public function getAddIncidentes(){
		$tipos=null; $vehiculos=null; $clasificaciones=null;

		$vehiculos = Vehiculos::orderBy('placa')->get();
		$tipos = AccidenteTiposaccinc::orderBy('nombre')->get();
		$clasificaciones = AccidenteClasificacion::orderBy('nombre')->get();
		

		return $this->renderHTML('accidentesIncidentesAdd.twig',[
				'tipos' => $tipos,
				'vehiculos' => $vehiculos,
				'clasificaciones' => $clasificaciones,
		]);
	}

	//Registra la Persona
	public function postAddIncidentes($request){
		$responseMessage = null; $prevMessage=null; $registrationErrorMessage=null;
		$incidentes = null; $numeroDePaginas=null; $vehiculos=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			
			$incidentesValidator = v::key('tipacid', v::numeric()->positive()->notEmpty())
					->key('clasid', v::numeric()->positive()->notEmpty())
					->key('vehid', v::numeric()->positive()->notEmpty())
					->key('fecha', v::date());
			
			
			if($_SESSION['userId']){
				try{
					$incidentesValidator->assert($postData);
					$postData = $request->getParsedBody();

					$incidentes = new AccidentesIncidentes();
					$incidentes->tipacid=$postData['tipacid'];
					$incidentes->fecha = $postData['fecha'];
					$incidentes->descripcioncorta = $postData['descripcioncorta'];
					$incidentes->clasid = $postData['clasid'];
					$incidentes->descripcionlarga = $postData['descripcionlarga'];
					$incidentes->vehid = $postData['vehid'];
					$incidentes->iduserregister = $_SESSION['userId'];
					$incidentes->iduserupdate = $_SESSION['userId'];
					$incidentes->save();

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
						'stringType' => '- Solo puede contener numeros y letras',
						'email' => '- Formato de correo no valido', 
						'date' => '- Formato de fecha no valido',
						'numeric' => '- Solo puede contener numeros', 
						'positive' => '- Solo puede contener numeros mayores a cero'
						]) ?? null;
					}else{
							$responseMessage = $prevMessage;
					}
				}
			}
		}
		
		$paginador = $this->paginador();
		if ($responseMessage=='Registrado') {
			$numeroDePaginas=$paginador['numeroDePaginas'];
			$incidentes=$paginador['incidentes'];
		}
		$vehiculos=$paginador['vehiculos'];

		return $this->renderHTML('accidentesIncidentesList.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'numeroDePaginas' => $numeroDePaginas,
				'incidentes' => $incidentes,
				'vehiculos' => $vehiculos
		]);
	}

	//Lista todas los modelos Ordenando por posicion
	public function getListIncidentes(){
		$responseMessage = null; $incidentes=null; $numeroDePaginas=null; $vehiculos=null;

		$paginaActual = $_GET['pag'] ?? null;

		$paginador = $this->paginador($paginaActual);
		$numeroDePaginas=$paginador['numeroDePaginas'];
		$incidentes=$paginador['incidentes'];
		$vehiculos=$paginador['vehiculos'];

		return $this->renderHTML('accidentesIncidentesList.twig', [
			'incidentes' => $incidentes,
			'numeroDePaginas' => $numeroDePaginas,
			'paginaActual' => $paginaActual,
			'vehiculos' => $vehiculos
		]);
		
	}

	public function paginador($paginaActual=null){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $incidentes=null;
		
		$numeroDeFilas = AccidentesIncidentes::selectRaw('count(*) as query_count')
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

		$incidentes = AccidentesIncidentes::Join("vehiculo.vehiculos","accidente.accidentesincidentes.vehid","=","vehiculo.vehiculos.id")
		->Join("accidente.tiposaccinc","accidente.accidentesincidentes.tipacid","=","accidente.tiposaccinc.id")
		->Join("accidente.clasificacion","accidente.accidentesincidentes.clasid","=","accidente.clasificacion.id")
		->select('accidente.accidentesincidentes.*', 'vehiculo.vehiculos.placa As placa', 'accidente.tiposaccinc.nombre As tipo', 'accidente.clasificacion.nombre As clasificacion')
		->latest('id')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		$vehiculos = Vehiculos::orderBy('placa')->get();

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'incidentes' => $incidentes,
			'vehiculos' => $vehiculos

		];

		return $retorno;
	}

	public function paginadorWhere($paginaActual=null, $criterio=null, $comparador='=', $textBuscar=null, $orden='latest', $criterioOrden='id'){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $personas=null;
		
		$numeroDeFilas = AccidentesIncidentes::where($criterio, $comparador ,$textBuscar)->selectRaw('count(*) as query_count')
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

		$incidentes = AccidentesIncidentes::Join("vehiculo.vehiculos","accidente.accidentesincidentes.vehid","=","vehiculo.vehiculos.id")
		->Join("accidente.tiposaccinc","accidente.accidentesincidentes.tipacid","=","accidente.tiposaccinc.id")
		->Join("accidente.clasificacion","accidente.accidentesincidentes.clasid","=","accidente.clasificacion.id")
		->where($criterio,$comparador,$textBuscar)
		->select('accidente.accidentesincidentes.*', 'vehiculo.vehiculos.placa As placa', 'accidente.tiposaccinc.nombre As tipo', 'accidente.clasificacion.nombre As clasificacion')
		->$orden($criterioOrden)
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		$vehiculos = Vehiculos::orderBy('placa')->get();

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'incidentes' => $incidentes,
			'vehiculos' => $vehiculos
		];
		
		return $retorno;
	}


	public function postBusquedaIncidentes($request){
		$prevMessage = null; $responseMessage=null; $iniciar=0; $incidentes=null; $queryErrorMessage=null;
		$numeroDePaginas=null; $paginaActual=null; $criterio=null; $textBuscar=null; $vehiculos=null;

		//if($request->getMethod()=='POST'){
		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$textBuscar = $postData['textBuscar'] ?? null;
			$criterio = $postData['criterio'] ?? null;
		}elseif ($request->getMethod()=='GET') {
			$getData = $request->getQueryParams();
			$paginaActual = $getData['pag'] ?? null;
			$criterio = $getData['?'] ?? null;
			$textBuscar = $getData['??'] ?? null;	
			$postData['textBuscar'] = $textBuscar;
		}


			if ($textBuscar) {

				if ($criterio==1) {
					$personaValidator = v::key('textBuscar', v::numeric()->positive()->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();
						$criterioQuery="vehid"; $comparador='=';
						//$textBuscarModificado='%'.$textBuscar.'%';
						$textBuscarModificado=$textBuscar;
						$paginador = $this->paginadorWhere($paginaActual, $criterioQuery,$comparador, $textBuscarModificado);
						$incidentes=$paginador['incidentes'];
						$numeroDePaginas=$paginador['numeroDePaginas'];
						$vehiculos=$paginador['vehiculos'];

					}catch(\Exception $exception){
						//$prevMessage = $exception->getMessage();
						$prevMessage = substr($exception->getMessage(), 0, 25);
						if ($prevMessage == 'SQLSTATE[42703]: Undefine') {
							$prevMessage= "(Parámetro de criterio incorrecto)";
						}elseif($prevMessage == 'These rules must pass for' or $prevMessage == 'All of the required rules') {
							$queryErrorMessage = $exception->findMessages([
							'notEmpty' => '- El texto de busqueda no puede quedar vacio',
							'numeric' => '- Solo puede contener numeros', 
							'positive' => '- Solo puede contener numeros mayores a cero'
							]);
						}else{
							$responseMessage = $prevMessage;
						}
					}
				}elseif ($criterio==2) {
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 35)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();

						$criterioQuery="persona.personas.nombre"; $comparador='ilike'; $orden='orderBy';
						//$textBuscarModificado='%'.$textBuscar.'%';
						$textBuscarModificado=$textBuscar;
						$paginador = $this->paginadorWhere($paginaActual, $criterioQuery, $comparador, $textBuscarModificado,$orden, $criterioQuery);
						$personas=$paginador['personas'];
						$numeroDePaginas=$paginador['numeroDePaginas'];

					}catch(\Exception $exception){
						//$prevMessage = $exception->getMessage();
						$prevMessage = substr($exception->getMessage(), 0, 25);

						if ($prevMessage == 'SQLSTATE[42703]: Undefine') {
							$prevMessage= "(Parámetro de criterio incorrecto)";
						}elseif($prevMessage == 'These rules must pass for' or $prevMessage == 'All of the required rules') {
							$queryErrorMessage = $exception->findMessages([
							'notEmpty' => '- El texto de busqueda no puede quedar vacio',
							'length' => '- Tiene una longitud no permitida',
							'stringType' => '- Solo puede contener nombres de personas'
							]);
						}else{
							$responseMessage = $prevMessage;
						}
					}
				}elseif ($criterio==3) {
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 35)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();

						$criterioQuery="persona.personas.apellido"; $comparador='ilike'; $orden='orderBy';
						//$textBuscarModificado='%'.$textBuscar.'%';
						$textBuscarModificado=$textBuscar;
						$paginador = $this->paginadorWhere($paginaActual, $criterioQuery, $comparador, $textBuscarModificado, $orden, $criterioQuery);
						$personas=$paginador['personas'];
						$numeroDePaginas=$paginador['numeroDePaginas'];

					}catch(\Exception $exception){
						//$prevMessage = $exception->getMessage();
						$prevMessage = substr($exception->getMessage(), 0, 25);

						if ($prevMessage == 'SQLSTATE[42703]: Undefine') {
							$prevMessage= "(Parámetro de criterio incorrecto)";
						}elseif($prevMessage == 'These rules must pass for' or $prevMessage == 'All of the required rules') {
							$queryErrorMessage = $exception->findMessages([
							'notEmpty' => '- El texto de busqueda no puede quedar vacio',
							'length' => '- Tiene una longitud no permitida',
							'stringType' => '- Solo puede contener apellidos de personas'
							]);
						}else{
							$responseMessage = $prevMessage;
						}
					}
				}else{
					$responseMessage='Selecciono un criterio no valido';
				}

			}
			
		$vehiculos = Vehiculos::orderBy('placa')->get();
		//}
	
		return $this->renderHTML('accidentesIncidentesList.twig', [
			'numeroDePaginasBusqueda' => $numeroDePaginas,
			'incidentes' => $incidentes,
			'prevMessage' => $prevMessage,
			'responseMessage' => $responseMessage,
			'queryErrorMessage' => $queryErrorMessage,
			'paginaActual' => $paginaActual,
			'textBuscar' => $textBuscar,
			'criterio' => $criterio,
			'vehiculos' => $vehiculos
		]);
		
	}


	public function postDelIncidentes($request){
		$incidentes=null; $numeroDePaginas=null; $id=null; $responseMessage = null; $vehiculos=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$id = $postData['btnDelUpd'] ?? null;
			
			if ($id) {
			  try{
				$incident = new AccidentesIncidentes();
				$incident->destroy($id);
				$responseMessage = "Se elimino el accidente o incidente";
			  }catch(\Exception $e){
			  	//$responseMessage = $e->getMessage();
			  	$prevMessage = substr($e->getMessage(), 0, 38);
				if ($prevMessage =="SQLSTATE[23503]: Foreign key violation") {
					$responseMessage = 'Error, No se puede eliminar, este accidente/incidente esta en uso.';
				}else{
					$responseMessage= 'Error, No se puede eliminar, '.$prevMessage;
				}
			  }
			}else{
				$responseMessage = 'Debe Seleccionar un accidente/incidente';
			}
		}

		$paginador = $this->paginador();
		$numeroDePaginas=$paginador['numeroDePaginas'];
		$incidentes=$paginador['incidentes'];
		$vehiculos=$paginador['vehiculos'];
	
		return $this->renderHTML('accidentesIncidentesList.twig', [
			'numeroDePaginas' => $numeroDePaginas,
			'incidentes' => $incidentes,
			'responseMessage' => $responseMessage,
			'vehiculos' => $vehiculos
		]);
	}


	public function postUpdIncidentes($request){
		$vehiculos = null; $tipos=null; $incidentes=null; $numeroDePaginas=null; $id=null; $responseMessage = null;
		$ruta='accidentesIncidentesUpdate.twig'; $clasificaciones=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$id = $postData['btnDelUpd'] ?? null;
			
			if ($id) {
				$incidentes = AccidentesIncidentes::find($id);

				$vehiculos = Vehiculos::orderBy('placa')->get();
				$tipos = AccidenteTiposaccinc::orderBy('nombre')->get();
				$clasificaciones = AccidenteClasificacion::orderBy('nombre')->get();
			}else{
				$ruta='accidentesIncidentesList.twig';

				$paginador = $this->paginador();
				$numeroDePaginas=$paginador['numeroDePaginas'];
				$incidentes=$paginador['incidentes'];
				$vehiculos=$paginador['vehiculos'];

				$responseMessage = 'Debe Seleccionar una persona';
			}
		}
		
		
		return $this->renderHTML($ruta, [
			'numeroDePaginas' => $numeroDePaginas,
			'incidentes' => $incidentes,
			'vehiculos' => $vehiculos,
			'tipos' => $tipos,
			'clasificaciones' => $clasificaciones,
			'responseMessage' => $responseMessage
		]);
	}


	//en esta accion se registra las modificaciones del registro utiliza metodo post no get
	public function postUpdateIncidentes($request){
		$responseMessage = null; $registrationErrorMessage=null; $incidentes=null; $numeroDePaginas=null; $vehiculos=null;
		
		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();

			$incidentesValidator = v::key('tipacid', v::numeric()->positive()->notEmpty())
					->key('clasid', v::numeric()->positive()->notEmpty())
					->key('vehid', v::numeric()->positive()->notEmpty())
					->key('fecha', v::date());

			if($_SESSION['userId']){
				try{
					$incidentesValidator->assert($postData);
					$postData = $request->getParsedBody();
					
					//la siguiente linea hace una consulta en la DB y trae el registro where id=$id y lo guarda en persona y posteriormente remplaza los valores y con el ->save() guarda la modificacion en la DB
					$idIncidente = $postData['id'];
					$incidentes = AccidentesIncidentes::find($idIncidente);
					
					$incidentes->tipacid=$postData['tipacid'];
					$incidentes->fecha = $postData['fecha'];
					$incidentes->descripcioncorta = $postData['descripcioncorta'];
					$incidentes->clasid = $postData['clasid'];
					$incidentes->descripcionlarga = $postData['descripcionlarga'];
					$incidentes->vehid = $postData['vehid'];
					$incidentes->iduserupdate = $_SESSION['userId'];
					$incidentes->save();

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
						'date' => '- Formato de fecha no valido',
						'numeric' => '- Solo puede contener numeros', 
						'positive' => '- Solo puede contener numeros mayores a cero'
						]) ?? null;
					}else{
							$responseMessage = $prevMessage;
					}
				}
			}
		}
		if ($responseMessage=='Editado.') {
			$paginador = $this->paginador();
			$numeroDePaginas=$paginador['numeroDePaginas'];
			$incidentes=$paginador['incidentes'];
			$vehiculos=$paginador['vehiculos'];
		}

		return $this->renderHTML('accidentesIncidentesList.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'numeroDePaginas' => $numeroDePaginas,
				'incidentes' => $incidentes,
				'vehiculos' => $vehiculos
		]);
	}
}

?>
