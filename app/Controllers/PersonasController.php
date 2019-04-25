<?php 

namespace App\Controllers;

use App\Models\{Personas,Roles,TiposDocumento, Generos, EstadosCiviles, Rh, NivelesEducativos};
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

		$roles = Roles::where("id",">=",$_SESSION['userRolId'])->latest('id')->get();
		$tiposdocumentos = TiposDocumento::orderBy('nombre')->get();
		$generos = Generos::orderBy('nombre')->get();
		$estadocivil = EstadosCiviles::orderBy('nombre')->get();
		$rh = Rh::latest('nombre')->get();
		$niveleducativo = NivelesEducativos::orderBy('nombre')->get();
		

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
			
			$personaValidator = v::key('nombre', v::stringType()->length(1, 50)->notEmpty())
					->key('apellido', v::stringType()->length(1, 50)->notEmpty())
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
		$numeroDePaginas=$paginador['numeroDePaginas'];
		$personas=$paginador['personas'];

		return $this->renderHTML('personasList.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'prevMessage' => $prevMessage,
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
					$personaValidator = v::key('textBuscar', v::numeric()->positive()->length(1, 15)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();
						
						$criterioQuery="numerodocumento"; $comparador='ilike';
						$textBuscarModificado='%'.$textBuscar.'%';
						$paginador = $this->paginadorWhere($paginaActual, $criterioQuery,$comparador, $textBuscarModificado);
						$personas=$paginador['personas'];
						$numeroDePaginas=$paginador['numeroDePaginas'];

					}catch(\Exception $exception){
						//$prevMessage = $exception->getMessage();
						$prevMessage = substr($exception->getMessage(), 0, 30);
						if ($prevMessage == 'SQLSTATE[42703]: Undefined col') {
							$prevMessage= "(Parámetro de criterio incorrecto)";
						}else{
							$queryErrorMessage = $exception->findMessages([
							'notEmpty' => '- El texto de busqueda no puede quedar vacio',
							'length' => '- Tiene una longitud no permitida',
							'numeric' => '- Solo puede contener numeros', 
							'positive' => '- Solo puede contener numeros mayores a cero'
							]);
						}
					}
				}elseif ($criterio==2) {
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 50)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();

						$criterioQuery="persona.personas.nombre"; $comparador='ilike'; $orden='orderBy';
						$textBuscarModificado='%'.$textBuscar.'%';
						$paginador = $this->paginadorWhere($paginaActual, $criterioQuery, $comparador, $textBuscarModificado,$orden, $criterioQuery);
						$personas=$paginador['personas'];
						$numeroDePaginas=$paginador['numeroDePaginas'];

					}catch(\Exception $exception){
						//$prevMessage = $exception->getMessage();
						$prevMessage = substr($exception->getMessage(), 0, 30);

						if ($prevMessage == 'SQLSTATE[42703]: Undefined col') {
							$prevMessage= "(Parámetro de criterio incorrecto)";
						}else{
							$queryErrorMessage = $exception->findMessages([
							'notEmpty' => '- El texto de busqueda no puede quedar vacio',
							'length' => '- Tiene una longitud no permitida',
							'stringType' => '- Solo puede contener nombres de personas'
							]);
						}
					}
				}elseif ($criterio==3) {
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 50)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();

						$criterioQuery="persona.personas.apellido"; $comparador='ilike'; $orden='orderBy';
						$textBuscarModificado='%'.$textBuscar.'%';
						$paginador = $this->paginadorWhere($paginaActual, $criterioQuery, $comparador, $textBuscarModificado, $orden, $criterioQuery);
						$personas=$paginador['personas'];
						$numeroDePaginas=$paginador['numeroDePaginas'];

					}catch(\Exception $exception){
						//$prevMessage = $exception->getMessage();
						$prevMessage = substr($exception->getMessage(), 0, 30);

						if ($prevMessage == 'SQLSTATE[42703]: Undefined col') {
							$prevMessage= "(Parámetro de criterio incorrecto)";
						}else{
							$queryErrorMessage = $exception->findMessages([
							'notEmpty' => '- El texto de busqueda no puede quedar vacio',
							'length' => '- Tiene una longitud no permitida',
							'stringType' => '- Solo puede contener apellidos de personas'
							]);
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


	/*Al seleccionar uno de los dos botones (Eliminar o Actualizar) llega a esta accion y verifica cual de los dos botones oprimio si eligio el boton eliminar(del) elimina el registro de where $id Pero
	Si elige actualizar(upd) cambia la ruta del renderHTML y guarda una consulta de los datos del registro a modificar para mostrarlos en formulario de actualizacion llamado updateActOperario.twig y cuando modifica los datos y le da guardar a ese formulaio regresa a esta class y elige la accion getUpdateActivity()*/
	public function postUpdDelPersonas($request){
		$roles = null; $tiposdocumentos=null; $personas=null; $numeroDePaginas=null; $id=null; $boton=null;
		$quiereActualizar = false; $ruta='personasList.twig'; $responseMessage = null;
		$generos=null; $estadocivil=null; $rh=null; $niveleducativo=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$btnDelUpd = $postData['btnDelUpd'] ?? null;
			$btnDocumentos = $postData['btnDocumentos'] ?? null;			

			/*En este if verifica que boton se presiono si el de documentos o licencia y crea una instancia de la clase que corresponde, ejemplo si presiono documentos crea una instancia de la clase PersonaDocumentosController y llama al metodo listPersonasDocumentos(parametro el ID de la persona)*/
			if ($btnDocumentos) {
				$id = $postData['id'] ?? null;				
				if ($id) {
					if ($btnDocumentos == 'doc') {
						$DocumentosController = new PersonaDocumentosController();
						return $DocumentosController->listPersonasDocumentos($id);

					}elseif ($btnDocumentos == 'lic') {
						$responseMessage = 'Quiere agregar licencias al id: '.$id;
					}else{
						$responseMessage = 'Opcion no definida, btn: '.$btnDocumentos;
					}	
				}else{
					$responseMessage = 'Debe Seleccionar una persona';
				}
			}
			
			if ($btnDelUpd) {
				$divideCadena = explode("|", $btnDelUpd);
				$boton=$divideCadena[0];
				$id=$divideCadena[1];
			}
			if ($id) {
				if($boton == 'del'){
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
				}elseif ($boton == 'upd') {
					$quiereActualizar=true;
				}
			}else{
				$responseMessage = 'Debe Seleccionar una persona';
			}
		}
		
		if ($quiereActualizar){
			//si quiere actualizar hace una consulta where id=$id y la envia por el array del renderHtml
			$personas = Personas::find($id);

			$roles = Roles::where("id",">=",$_SESSION['userRolId'])->latest('id')->get();
			$tiposdocumentos = TiposDocumento::orderBy('nombre')->get();
			$generos = Generos::orderBy('nombre')->get();
			$estadocivil = EstadosCiviles::orderBy('nombre')->get();
			$rh = Rh::latest('nombre')->get();
			$niveleducativo = NivelesEducativos::orderBy('nombre')->get();
			$ruta='personasUpdate.twig';
		}else{
			$iniciar=0;

			$paginador = $this->paginador();
			$numeroDePaginas=$paginador['numeroDePaginas'];
			$personas=$paginador['personas'];
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
				
		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();

			$personaValidator = v::key('nombre', v::stringType()->length(1, 50)->notEmpty())
					->key('apellido', v::stringType()->length(1, 50)->notEmpty())
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
					$persona->iduserregister = $_SESSION['userId'];
					$persona->iduserupdate = $_SESSION['userId'];
					$persona->save();

					$responseMessage .= 'Editado.';
				}catch(\Exception $exception){
					$prevMessage = substr($exception->getMessage(), 0, 25);

					if ($prevMessage == "SQLSTATE[23505]: Unique v") {
						$responseMessage = 'Error, El numero del documento ya esta registrado';
					}elseif ($prevMessage == "SQLSTATE[42703]: Undefine") {
						$responseMessage = 'Error interno de base de datos, en el pie de pagina esta toda la información de contacto, por favor contáctenos para darle una rápida solución.';
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
		$numeroDePaginas=$paginador['numeroDePaginas'];
		$personas=$paginador['personas'];

		return $this->renderHTML('personasList.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'numeroDePaginas' => $numeroDePaginas,
				'personas' => $personas
		]);
	}
}

?>
