<?php 

namespace App\Controllers;

use App\Models\{Personas,PersonaRoles,PersonaTiposDocumento, PersonaGeneros, PersonaEstadosCiviles, PersonaRh, PersonaNivelesEducativos};
use App\Controllers\{DocumentosController};
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Zend\Diactoros\Response\RedirectResponse;

class PersonasController extends BaseController{
	
	//estos dos valores son los que se cambian, para modificar la cantidad de registros listados por pagina y el maximo numero en paginacion
	private $articulosPorPagina=10;
	private $limitePaginacion=20;

	public function getAddPersonas(){
		$roles = null; $tiposdocumentos=null;

		$roles = PersonaRoles::where("id",">=",$_SESSION['userRolId'])->latest('id')->get();
		$tiposdocumentos = PersonaTiposDocumento::orderBy('nombre')->get();
		$generos = PersonaGeneros::orderBy('nombre')->get();
		$estadocivil = PersonaEstadosCiviles::orderBy('nombre')->get();
		$rh = PersonaRh::latest('nombre')->get();
		$niveleducativo = PersonaNivelesEducativos::orderBy('nombre')->get();
		

		return $this->renderHTML('personasAdd.twig',[
				'roles' => $roles,
				'tiposdocumentos' => $tiposdocumentos,
				'generos' => $generos,
				'estadocivil' => $estadocivil,
				'rh' => $rh,
				'niveleducativo' => $niveleducativo
		]);
	}

	//Registra la Persona
	public function postAddPersonas($request){
		$responseMessage = null; $prevMessage=null; $registrationErrorMessage=null;
		$personas = null; $numeroDePaginas=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			
			$personaValidator = v::key('nombre', v::stringType()->length(1, 35)->notEmpty())
					->key('apellido', v::stringType()->length(1, 35)->notEmpty())
					->key('tipodocumentoid', v::numeric()->positive()->notEmpty())
					->key('numerodocumento', v::numeric()->positive()->length(1, 15)->notEmpty())
					->key('genero', v::numeric()->positive()->notEmpty())
					->key('estadocivil', v::numeric()->positive()->notEmpty())
					->key('fechanacimiento', v::date())
					->key('rh', v::numeric()->positive()->notEmpty())
					->key('correo', v::email())
					->key('celular', v::numeric()->positive()->length(1, 15)->notEmpty())
					->key('rolid', v::numeric()->positive()->length(1, 2)->notEmpty());
			
			
			if($_SESSION['userId']){
				try{
					$personaValidator->assert($postData);
					$postData = $request->getParsedBody();

					$persona = new Personas();
					$persona->numerodocumento=$postData['numerodocumento'];
					$persona->tipodocumentoid = $postData['tipodocumentoid'];
					$persona->nombre = $postData['nombre'];
					$persona->apellido = $postData['apellido'];
					$persona->generoid = $postData['genero'];
					$persona->estadocivilid = $postData['estadocivil'];
					$persona->fechanacimiento = $postData['fechanacimiento'];
					$persona->rhid = $postData['rh'];
					$persona->niveleducativoid = $postData['niveleducativo'];
					$persona->profesion = $postData['profesion'];
					$persona->direccion = $postData['direccion'];
					$persona->correo = $postData['correo'];
					$persona->telefono = $postData['telefono'];
					$persona->celular = $postData['celular'];
					$persona->rolid = $postData['rolid'];
					$persona->activocheck = 1;
					$persona->iduserregister = $_SESSION['userId'];
					$persona->iduserupdate = $_SESSION['userId'];
					$persona->save();

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
		
		if ($responseMessage=='Registrado') {
			$paginador = $this->paginador();
			$numeroDePaginas=$paginador['numeroDePaginas'];
			$personas=$paginador['personas'];
		}

		return $this->renderHTML('personasList.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'numeroDePaginas' => $numeroDePaginas,
				'personas' => $personas
		]);
	}

	//Lista todas los modelos Ordenando por posicion
	public function getListPersonas(){
		$responseMessage = null; $personas=null; $numeroDePaginas=null;

		$paginaActual = $_GET['pag'] ?? null;		
		$paginador = $this->paginador($paginaActual);
		$numeroDePaginas=$paginador['numeroDePaginas'];
		$personas=$paginador['personas'];

		return $this->renderHTML('personasList.twig', [
			'personas' => $personas,
			'numeroDePaginas' => $numeroDePaginas,
			'paginaActual' => $paginaActual
		]);
		
	}

	public function paginador($paginaActual=null){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $personas=null;
		
		$numeroDeFilas = Personas::selectRaw('count(*) as query_count')
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

		$personas = Personas::Join("persona.tiposdocumento","persona.personas.tipodocumentoid","=","persona.tiposdocumento.id")
		->Join("persona.rh","persona.personas.rhid","=","persona.rh.id")
		->where("persona.personas.rolid",">=",$_SESSION['userRolId'])
		->select('personas.*', 'persona.tiposdocumento.nombre As tiposdocumento', 'persona.rh.nombre As rh')
		->latest('id')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'personas' => $personas

		];

		return $retorno;
	}

	public function paginadorWhere($paginaActual=null, $criterio=null, $comparador='=', $textBuscar=null, $orden='latest', $criterioOrden='id'){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $personas=null;
		
		$numeroDeFilas = Personas::where($criterio, $comparador ,$textBuscar)->selectRaw('count(*) as query_count')
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

		$personas = Personas::Join("persona.tiposdocumento","persona.personas.tipodocumentoid","=","persona.tiposdocumento.id")
		->Join("persona.rh","persona.personas.rhid","=","persona.rh.id")
		->where("persona.personas.rolid",">=",$_SESSION['userRolId'])
		->where($criterio,$comparador,$textBuscar)
		->select('personas.*', 'persona.tiposdocumento.nombre As tiposdocumento', 'persona.rh.nombre As rh')
		->$orden($criterioOrden)
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'personas' => $personas

		];
		
		return $retorno;
	}


	public function postBusquedaPersonas($request){
		$prevMessage = null; $responseMessage=null; $iniciar=0; $personas=null; $queryErrorMessage=null;
		$numeroDePaginas=null; $paginaActual=null; $criterio=null; $textBuscar=null;

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
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 15)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();
						
						$criterioQuery="numerodocumento"; $comparador='ilike';
						//$textBuscarModificado='%'.$textBuscar.'%';
						$textBuscarModificado=$textBuscar;
						$paginador = $this->paginadorWhere($paginaActual, $criterioQuery,$comparador, $textBuscarModificado);
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
			
			
		//}
	
		return $this->renderHTML('personasList.twig', [
			'numeroDePaginasBusqueda' => $numeroDePaginas,
			'personas' => $personas,
			'prevMessage' => $prevMessage,
			'responseMessage' => $responseMessage,
			'queryErrorMessage' => $queryErrorMessage,
			'paginaActual' => $paginaActual,
			'textBuscar' => $textBuscar,
			'criterio' => $criterio
		]);
		
	}


	public function postDelPersonas($request){
		$personas=null; $numeroDePaginas=null; $id=null; $responseMessage = null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$id = $postData['btnDelUpd'] ?? null;
			
			if ($id) {
			  try{
				$people = new Personas();
				$people->destroy($id);
				$responseMessage = "Se elimino el registro de la persona";
			  }catch(\Exception $e){
			  	//$responseMessage = $e->getMessage();
			  	$prevMessage = substr($e->getMessage(), 0, 38);
				if ($prevMessage =="SQLSTATE[23503]: Foreign key violation") {
					$responseMessage = 'Error, No se puede eliminar, esta persona esta en uso en la base de datos.';
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
		$personas=$paginador['personas'];
	
		return $this->renderHTML('personasList.twig', [
			'numeroDePaginas' => $numeroDePaginas,
			'personas' => $personas,
			'responseMessage' => $responseMessage
		]);
	}


	public function postUpdPersonas($request){
		$roles = null; $tiposdocumentos=null; $personas=null; $numeroDePaginas=null; $id=null; $responseMessage = null;
		$ruta='personasUpdate.twig'; $generos=null; $estadocivil=null; $rh=null; $niveleducativo=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$id = $postData['btnDelUpd'] ?? null;
			
			if ($id) {
				$personas = Personas::find($id);

				$roles = PersonaRoles::where("id",">=",$_SESSION['userRolId'])->latest('id')->get();
				$tiposdocumentos = PersonaTiposDocumento::orderBy('nombre')->get();
				$generos = PersonaGeneros::orderBy('nombre')->get();
				$estadocivil = PersonaEstadosCiviles::orderBy('nombre')->get();
				$rh = PersonaRh::latest('nombre')->get();
				$niveleducativo = PersonaNivelesEducativos::orderBy('nombre')->get();	
			}else{
				$ruta='personasList.twig';

				$paginador = $this->paginador();
				$numeroDePaginas=$paginador['numeroDePaginas'];
				$personas=$paginador['personas'];

				$responseMessage = 'Debe Seleccionar una persona';
			}
		}
		
		
		return $this->renderHTML($ruta, [
			'numeroDePaginas' => $numeroDePaginas,
			'personas' => $personas,
			'roles' => $roles,
			'tiposdocumentos' => $tiposdocumentos,
			'responseMessage' => $responseMessage,
			'generos' => $generos,
			'estadocivil' => $estadocivil,
			'rh' => $rh,
			'niveleducativo' => $niveleducativo
		]);
	}


	//en esta accion se registra las modificaciones del registro utiliza metodo post no get
	public function postUpdatePersonas($request){
		$responseMessage = null; $registrationErrorMessage=null; $personas=null; $numeroDePaginas=null;
		$activoCheck=1;
		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();

			$personaValidator = v::key('nombre', v::stringType()->length(1, 35)->notEmpty())
					->key('apellido', v::stringType()->length(1, 35)->notEmpty())
					->key('tipodocumentoid', v::numeric()->positive()->notEmpty())
					->key('numerodocumento', v::numeric()->positive()->length(1, 15)->notEmpty())
					->key('genero', v::numeric()->positive()->notEmpty())
					->key('estadocivil', v::numeric()->positive()->notEmpty())
					->key('fechanacimiento', v::date())
					->key('rh', v::numeric()->positive()->notEmpty())
					->key('correo', v::email())
					->key('celular', v::numeric()->positive()->length(1, 15)->notEmpty())
					->key('rolid', v::numeric()->positive()->length(1, 2)->notEmpty());

			
			if($_SESSION['userId']){
				try{
					$personaValidator->assert($postData);
					$postData = $request->getParsedBody();

					$postActivoCheck = $postData['activocheck'] ?? null;
					if ($postActivoCheck) {
						$activoCheck = 1;
					}else{
						$activoCheck = 0;
					}
					
					//la siguiente linea hace una consulta en la DB y trae el registro where id=$id y lo guarda en persona y posteriormente remplaza los valores y con el ->save() guarda la modificacion en la DB
					$idPersona = $postData['id'];
					$persona = Personas::find($idPersona);
					
					$persona->numerodocumento=$postData['numerodocumento'];
					$persona->tipodocumentoid = $postData['tipodocumentoid'];
					$persona->nombre = $postData['nombre'];
					$persona->apellido = $postData['apellido'];
					$persona->generoid = $postData['genero'];
					$persona->estadocivilid = $postData['estadocivil'];
					$persona->fechanacimiento = $postData['fechanacimiento'];
					$persona->rhid = $postData['rh'];
					$persona->niveleducativoid = $postData['niveleducativo'];
					$persona->profesion = $postData['profesion'];
					$persona->direccion = $postData['direccion'];
					$persona->correo = $postData['correo'];
					$persona->telefono = $postData['telefono'];
					$persona->celular = $postData['celular'];
					$persona->rolid = $postData['rolid'];
					$persona->activocheck = $activoCheck;
					$persona->iduserupdate = $_SESSION['userId'];
					$persona->save();

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
		if ($responseMessage=='Editado.') {
			$paginador = $this->paginador();
			$numeroDePaginas=$paginador['numeroDePaginas'];
			$personas=$paginador['personas'];
		}

		return $this->renderHTML('personasList.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'numeroDePaginas' => $numeroDePaginas,
				'personas' => $personas
		]);
	}
}

?>
