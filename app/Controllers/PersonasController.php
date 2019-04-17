<?php 

namespace App\Controllers;

use App\Models\{Personas,Roles,TiposDocumento, Generos, EstadosCiviles, Rh, NivelesEducativos};
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
		

		return $this->renderHTML('addPersonas.twig',[
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
		$responseMessage = null; $registrationErrorMessage=null;
		$personas = null;

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
					//$responseMessage = $exception->getMessage();
					$prevMessage = substr($exception->getMessage(), 0, 33);
					$responseMessage = substr($exception->getMessage(), 0, 33);	

					if ($prevMessage == "SQLSTATE[23505]: Unique violation") {
						$responseMessage = 'Error, El numero del documento o el correo ya esta registrado';
					}elseif ($prevMessage == "SQLSTATE[42703]: Undefined column") {
						$responseMessage = 'Error interno de base de datos, en el pie de pagina esta toda la información de contacto, por favor contáctenos para darle una rápida solución.';
					}else{
						$registrationErrorMessage = $exception->findMessages([
						'notEmpty' => '- Los campos con (*) no pueden estar vacios',
						'length' => '- Tiene una longitud no permitida',
						'stringType' => '- Solo puede contener numeros y letras',
						'email' => '- Formato de correo no valido', 
						'date' => '- Formato de fecha no valido',
						'numeric' => '- Solo puede contener numeros', 
						'positive' => '- Solo puede contener numeros mayores a cero'
						]) ?? null;
					}
				}
			}
		}
		$iniciar=0;
		$personas = Personas::Join("persona.tiposdocumento","persona.personas.tipodocumentoid","=","persona.tiposdocumento.id")
		->Join("persona.rh","persona.personas.rhid","=","persona.rh.id")
		->where("persona.personas.rolid",">=",$_SESSION['userRolId'])
		->select('personas.*', 'persona.tiposdocumento.nombre As tiposdocumento', 'persona.rh.nombre As rh')
		->latest('id')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		return $this->renderHTML('buscarPersonas.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'personas' => $personas
		]);
	}

	//Lista todas los modelos Ordenando por posicion
	public function getListPersonas(){
		$responseMessage = null; $iniciar=0;
		
		$numeroDeFilas = Personas::selectRaw('count(*) as query_count')
		->first();
		
		$totalFilasDb = $numeroDeFilas->query_count;
		$numeroDePaginas = $totalFilasDb/$this->articulosPorPagina;
		$numeroDePaginas = ceil($numeroDePaginas);

		//No permite que haya muchos botones de paginar y de esa forma va a traer una cantidad limitada de registro, no queremos que se pagine hasta el infinito, porque tambien puede ser molesto.
		if ($numeroDePaginas > $this->limitePaginacion) {
			$numeroDePaginas=$this->limitePaginacion;
		}

		$paginaActual = $_GET['pag'] ?? null;
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
	
		return $this->renderHTML('buscarPersonas.twig', [
			'personas' => $personas,
			'numeroDePaginas' => $numeroDePaginas,
			'paginaActual' => $paginaActual
		]);
		
	}


	public function postBusquedaPersonas($request){
		$responseMessage = null; $iniciar=0; $personas=null; $queryErrorMessage=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			

			$textBuscar = $postData['textBuscar'] ?? null;
			$criterio = $postData['criterio'] ?? null;

			if ($textBuscar) {

				if ($criterio==1) {
					$personaValidator = v::key('textBuscar', v::numeric()->positive()->length(1, 15)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();

						$personas = Personas::Join("persona.tiposdocumento","persona.personas.tipodocumentoid","=","persona.tiposdocumento.id")
						->Join("persona.rh","persona.personas.rhid","=","persona.rh.id")
						->select('personas.*', 'persona.tiposdocumento.nombre As tiposdocumento', 'persona.rh.nombre As rh')
						->where("numerodocumento","=",$textBuscar)
						->where("persona.personas.rolid",">=",$_SESSION['userRolId'])
						->get();
					}catch(\Exception $exception){
						//$responseMessage = $exception->getMessage();
						$responseMessage = substr($exception->getMessage(), 0, 30);
						
						$queryErrorMessage = $exception->findMessages([
						'notEmpty' => '- El texto de busqueda no puede quedar vacio',
						'length' => '- Tiene una longitud no permitida',
						'numeric' => '- Solo puede contener numeros', 
						'positive' => '- Solo puede contener numeros mayores a cero'
						]);
					}
				}elseif ($criterio==2) {
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 50)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();

						$personas = Personas::Join("persona.tiposdocumento","persona.personas.tipodocumentoid","=","persona.tiposdocumento.id")
						->Join("persona.rh","persona.personas.rhid","=","persona.rh.id")
						->select('personas.*', 'persona.tiposdocumento.nombre As tiposdocumento', 'persona.rh.nombre As rh')
						->where("persona.personas.nombre","ilike", '%'.$textBuscar.'%')
						->where("persona.personas.rolid",">=",$_SESSION['userRolId'])
						->limit($this->articulosPorPagina)->offset($iniciar)
						->orderBy('nombre')
						->get();
					}catch(\Exception $exception){
						//$responseMessage = $exception->getMessage();
						$responseMessage = substr($exception->getMessage(), 0, 30);

						$queryErrorMessage = $exception->findMessages([
						'notEmpty' => '- El texto de busqueda no puede quedar vacio',
						'length' => '- Tiene una longitud no permitida',
						'stringType' => '- Solo puede contener nombres de personas'
						]);
					}
				}elseif ($criterio==3) {
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 50)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();

						$personas = Personas::Join("persona.tiposdocumento","persona.personas.tipodocumentoid","=","persona.tiposdocumento.id")
						->Join("persona.rh","persona.personas.rhid","=","persona.rh.id")
						->select('personas.*', 'persona.tiposdocumento.nombre As tiposdocumento', 'persona.rh.nombre As rh')
						->where("persona.personas.apellido","ilike", '%'.$textBuscar.'%')
						->where("persona.personas.rolid",">=",$_SESSION['userRolId'])
						->limit($this->articulosPorPagina)->offset($iniciar)
						->orderBy('apellido')
						->get();
					}catch(\Exception $exception){
						//$responseMessage = $exception->getMessage();
						$responseMessage = substr($exception->getMessage(), 0, 30);

						$queryErrorMessage = $exception->findMessages([
						'notEmpty' => '- El texto de busqueda no puede quedar vacio',
						'length' => '- Tiene una longitud no permitida',
						'stringType' => '- Solo puede contener apellidos de personas'
						]);
					}
				}else{
					$responseMessage='Selecciono un criterio no valido';
				}

			}
			
			
		}
	
		return $this->renderHTML('resultadoBuscarPersonas.twig', [
			'personas' => $personas,
			'responseMessage' => $responseMessage,
			'queryErrorMessage' => $queryErrorMessage
		]);
		
	}


	/*Al seleccionar uno de los dos botones (Eliminar o Actualizar) llega a esta accion y verifica cual de los dos botones oprimio si eligio el boton eliminar(del) elimina el registro de where $id Pero
	Si elige actualizar(upd) cambia la ruta del renderHTML y guarda una consulta de los datos del registro a modificar para mostrarlos en formulario de actualizacion llamado updateActOperario.twig y cuando modifica los datos y le da guardar a ese formulaio regresa a esta class y elige la accion getUpdateActivity()*/
	public function postUpdDelPersonas($request){
		$roles = null; $tiposdocumentos=null; $personas=null; $responseMessage = null; $id=null; $boton=null;
		$quiereActualizar = false; $ruta='buscarPersonas.twig';
		$generos=null; $estadocivil=null; $rh=null; $niveleducativo=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$btnDelUpd = $postData['btnDelUpd'] ?? null;
			$btnDocumentos = $postData['btnDocumentos'] ?? null;			

			if ($btnDocumentos) {
				$id = $postData['id'] ?? null;				
				if ($id) {
					if ($btnDocumentos == 'doc') {
					$responseMessage = 'Quiere agregar documentos al id: '.$id;

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
			$ruta='updatePersonas.twig';
		}else{
			$iniciar=0;
			$personas = Personas::Join("persona.tiposdocumento","persona.personas.tipodocumentoid","=","persona.tiposdocumento.id")
			->select('personas.*', 'persona.tiposdocumento.nombre As tiposdocumento')
			->orderBy('nombre')
			->limit($this->articulosPorPagina)->offset($iniciar)
			->get();
		}
		return $this->renderHTML($ruta, [
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
		$responseMessage = null; $registrationErrorMessage=null;
				
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
					//$responseMessage = substr($exception->getMessage(), 0, 33);
					//$responseMessage = $exception->getMessage();
					$prevMessage = substr($exception->getMessage(), 0, 33);
					$responseMessage = substr($exception->getMessage(), 0, 33);	

					if ($prevMessage == "SQLSTATE[23505]: Unique violation") {
						$responseMessage = 'Error, El numero del documento o el correo ya esta registrado';
					}elseif ($prevMessage == "SQLSTATE[42703]: Undefined column") {
						$responseMessage = 'Error interno de base de datos, en el pie de pagina esta toda la información de contacto, por favor contáctenos para darle una rápida solución.';
					}else{
						$registrationErrorMessage = $exception->findMessages([
						'notEmpty' => '- Los campos con (*) no pueden estar vacios',
						'length' => '- Tiene una longitud no permitida',
						'stringType' => '- Solo puede contener numeros y letras',
						'email' => '- Formato de correo no valido', 
						'date' => '- Formato de fecha no valido',
						'numeric' => '- Solo puede contener numeros', 
						'positive' => '- Solo puede contener numeros mayores a cero'
						]) ?? null;
					}
				}
			}
		}

		$iniciar=0;
		$personas = Personas::Join("persona.tiposdocumento","persona.personas.tipodocumentoid","=","persona.tiposdocumento.id")
		->Join("persona.rh","persona.personas.rhid","=","persona.rh.id")
		->where("persona.personas.rolid",">=",$_SESSION['userRolId'])
		->select('personas.*', 'persona.tiposdocumento.nombre As tiposdocumento', 'persona.rh.nombre As rh')
		->latest('id')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		return $this->renderHTML('buscarPersonas.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'personas' => $personas
		]);
	}
}

?>
