<?php 

namespace App\Controllers;

use App\Models\{Personas,PersonaDocumentos, PersonaTiposDocumentosPer};
use App\Controllers\{FilesValidatorController};
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Zend\Diactoros\Response\RedirectResponse;

class PersonaDocumentosController extends BaseController{
	
	//estos dos valores son los que se cambian, para modificar la cantidad de registros listados por pagina y el maximo numero en paginacion
	private $articulosPorPagina=10;
	private $limitePaginacion=20;
	private $mensajePersonaNoDefinida="Persona no definida, por favor seleccione una persona y luego presione el botón documentos";


	public function getAddDocumentos(){
		$responseMessage=null; $ruta='personaDocumentosAdd.twig'; $tiposdocumentos=null;

		$idPer = $_GET['?'] ?? null;

		if ($idPer) {
			$tiposdocumentos = PersonaTiposDocumentosPer::orderBy('nombre')->get();
		}else{
			$responseMessage = $this->mensajePersonaNoDefinida;
			$ruta='personaDocumentosList.twig';
		}

		return $this->renderHTML($ruta,[
				'responseMessage' => $responseMessage,
				'idPer' => $idPer,
				'tiposdocumentos' => $tiposdocumentos,
		]);
	}

	//Registra el Documento
	public function postAddDocumentos($request){
		$responseMessage = null; $registrationErrorMessage=null; $fileName=null;
		$documentos = null; $numeroDePaginas=null; $idPer=0; $persona=null; $ruta='personaDocumentosList.twig';


		if($request->getMethod()=='POST'){
			//crea el array $postData pasando las variables POST como indices de este array
			$postData = $request->getParsedBody();
			
			/*En la variable $files se almacena el $request del file y en $fileComprobante se
			*almacena el array con todas las propiedades de este file*/
			$files = $request->getUploadedFiles(); 
			if ($files) {
				$fileComprobante = $files['urlcomprobante']; 
				$temporaryFileName = 'docp'.$postData['referencia'];
				
				/*Se hace llamado al metodo que se creo para validar las imagenes */
				$FilesValidatorController = new FilesValidatorController();
				$validadorComprobante = $FilesValidatorController->filesValidator($fileComprobante, $temporaryFileName);
			}else{
				$validadorComprobante['error']=1;
				$validadorComprobante['message']="Error, el comprobante no puede pesar mas de 2MB, seleccione nuevamente la persona y agregué un comprobante valido";
				$ruta='personasList.twig';
			}
			

			$documentoValidator = v::key('referencia', v::stringType()->length(1, 50)->notEmpty())
					->key('emisor', v::stringType()->length(1, 100)->notEmpty())
					->key('fechainicio', v::date())
					->key('fechafinal', v::date())
					->key('tdpid', v::numeric()->positive()->notEmpty());
			
			$idPer = $postData['idPer'] ?? null;
			
			if($_SESSION['userId']){
				if ($validadorComprobante['error'] == 0) {
					$fileName = $validadorComprobante['fileName'];

					try{
						$documentoValidator->assert($postData);
						$postData = $request->getParsedBody();

						$documento = new PersonaDocumentos();
						$documento->referencia=$postData['referencia'];
						$documento->emisor = $postData['emisor'];
						$documento->fechainicio = $postData['fechainicio'];
						$documento->fechafinal = $postData['fechafinal'];
						$fileComprobante->moveTo("uploads/$fileName");
						$documento->urlcomprobante = $fileName;
						$documento->perid = $idPer;
						$documento->tdpid = $postData['tdpid'];
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
							$responseMessage = 'Error, El ID de esta persona no esta registrado';
						}elseif ($prevMessage == "SQLSTATE[42703]: Undefine") {
							$responseMessage = 'Error interno de base de datos, en el pie de pagina esta toda la información de contacto, por favor contáctenos para darle una rápida solución.';
						}elseif($prevMessage == 'These rules must pass for' or $prevMessage == 'All of the required rules') {
							$registrationErrorMessage = $exception->findMessages([
							'notEmpty' => '- Los campos con (*) no pueden estar vacios',
							'length' => '- Tiene una longitud no permitida',
							'stringType' => '- Solo puede contener numeros y letras',
							'date' => '- Formato de fecha no valido',
							'numeric' => '- Solo puede contener numeros', 
							'positive' => '- Solo puede contener numeros mayores a cero'
							]);
						}else{
							$responseMessage = $prevMessage;
						}
					}
				}else{
					//si la validacion del $file da error, se guarda en la variable $responseMessage el mensaje de error
					$responseMessage = $validadorComprobante['message'];
				}

			}
		}
		$paginador = $this->paginador($idPer);
		$persona = $paginador['persona'];
		if ($responseMessage=='Registrado') {
			$documentos = $paginador['documentos'];
			$numeroDePaginas = $paginador['numeroDePaginas'];	
		}

		return $this->renderHTML($ruta,[
				'idPer' => $idPer,
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'documentos' => $documentos,
				'numeroDePaginas' => $numeroDePaginas,
				'persona' => $persona
		]);
	}

 
	//Lista todas los modelos Ordenando por posicion
	public function paginador($idPer=0, $paginaActual=0){
		$retorno = array(); $iniciar=0; $documentos=null; $persona=null;
		
		$numeroDeFilas = PersonaDocumentos::selectRaw('count(*) as query_count')
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
		
		$documentos = PersonaDocumentos::Join("persona.tiposdocumentosper","persona.documentosper.tdpid","=","persona.tiposdocumentosper.id")
		->where("persona.documentosper.perid","=",$idPer)
		->select('persona.documentosper.*', 'persona.tiposdocumentosper.nombre As tipoDocumento')
		->latest('persona.documentosper.id')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		/* Consulta toda la informacion de la persona a la cual se le estan buscando los Documentos */
		$persona = Personas::find($idPer);

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'documentos' => $documentos,
			'persona' => $persona
		];

		return $retorno;
		
	}

	public function paginadorWhere($idPer=null, $paginaActual=null, $criterio=null, $comparador='=', $textBuscar=null, $orden='latest', $criterioOrden='id'){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $documentos=null; $persona=null;
		
		$numeroDeFilas = PersonaDocumentos::where($criterio, $comparador ,$textBuscar)->selectRaw('count(*) as query_count')
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

		$documentos = PersonaDocumentos::Join("persona.tiposdocumentosper","persona.documentosper.tdpid","=","persona.tiposdocumentosper.id")
		->where("persona.documentosper.perid","=",$idPer)
		->where($criterio,$comparador,$textBuscar)
		->select('persona.documentosper.*', 'persona.tiposdocumentosper.nombre As tipoDocumento')
		//->latest('persona.documentosper.id')
		->$orden($criterioOrden)
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();	

		/* Consulta toda la informacion de la persona a la cual se le estan buscando los Documentos */
		$persona = Personas::find($idPer);		

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'documentos' => $documentos,
			'persona' => $persona

		];
		
		return $retorno;
	}

	public function getListDocumentos(){
		$responseMessage=null; $numeroDePaginas=null; $documentos=null; $numeroDePaginas=null; $persona=null;
		
		//Se utiliza esta linea Si este metodo es invocado por el metodo get desde personaDocumentosList.twig 
		$idPer = $_GET['?'] ?? null;
		
		//Se utiliza esta linea si el metodo es invocado desde personasList.twig, cuando el usuario seleccionar una persona y le da en el boton documentos
		if (!$idPer) {
			$idPer = $_POST['id'] ?? null;
			
		}

		$paginaActual = $_GET['pag'] ?? null;

		if ($idPer) {
			$paginador = $this->paginador($idPer, $paginaActual);
		
			$documentos = $paginador['documentos'];
			$numeroDePaginas = $paginador['numeroDePaginas'];	
			$persona = $paginador['persona'];

		}else{
			$responseMessage = $this->mensajePersonaNoDefinida;
		}
		
		
		return $this->renderHTML('personaDocumentosList.twig', [
			'idPer' => $idPer,
			'responseMessage' => $responseMessage,
			'documentos' => $documentos,
			'numeroDePaginas' => $numeroDePaginas,
			'paginaActual' => $paginaActual,
			'persona' => $persona
		]);
	}


	public function postBusquedaDocumentos($request){
		$prevMessage = null; $responseMessage=null; $iniciar=0; $documentos=null; $queryErrorMessage=null; $error=false;
		$numeroDePaginas=null; $paginaActual=null; $criterio=null; $textBuscar=null; $idPer=null; $persona=null;

		//if($request->getMethod()=='POST'){
		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$textBuscar = $postData['textBuscar'] ?? null;
			$criterio = $postData['criterio'] ?? null;
			$idPer = $postData['idPer'] ?? null;
		}elseif ($request->getMethod()=='GET') {
			$getData = $request->getQueryParams();
			$paginaActual = $getData['pag'] ?? null;
			$criterio = $getData['?'] ?? null;
			$textBuscar = $getData['??'] ?? null;
			$idPer = $getData['%'] ?? null;

			//el textBuscar que llega por GET lo paso al array $postData para de esta forma usar el validador
			$postData['textBuscar'] = $textBuscar;
		}


			if ($textBuscar) {

				if ($criterio==1) {
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 50)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();
						
						$criterioQuery="persona.documentosper.referencia"; $comparador='ilike';
						//$textBuscarModificado='%'.$textBuscar.'%';
						$textBuscarModificado=$textBuscar;
						$paginador = $this->paginadorWhere($idPer,$paginaActual, $criterioQuery,$comparador, $textBuscarModificado);
						$documentos=$paginador['documentos'];
						$numeroDePaginas=$paginador['numeroDePaginas'];
						$persona=$paginador['persona'];

					}catch(\Exception $exception){
						$error=true;
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
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 50)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();

						$criterioQuery="persona.documentosper.emisor"; $comparador='ilike'; $orden='orderBy';
						//$textBuscarModificado='%'.$textBuscar.'%';
						$textBuscarModificado=$textBuscar;
						$paginador = $this->paginadorWhere($idPer,$paginaActual, $criterioQuery, $comparador, $textBuscarModificado,$orden, $criterioQuery);
						$documentos=$paginador['documentos'];
						$numeroDePaginas=$paginador['numeroDePaginas'];
						$persona=$paginador['persona'];

					}catch(\Exception $exception){
						$error=true;
						//$prevMessage = $exception->getMessage();
						$prevMessage = substr($exception->getMessage(), 0, 25);

						if ($prevMessage == 'SQLSTATE[42703]: Undefine') {
							$prevMessage= "(Parámetro de criterio incorrecto)";
						}elseif($prevMessage == 'These rules must pass for' or $prevMessage == 'All of the required rules') {
							$queryErrorMessage = $exception->findMessages([
							'notEmpty' => '- El texto de busqueda no puede quedar vacio',
							'length' => '- Tiene una longitud no permitida',
							'stringType' => '- Solo puede contener nombres'
							]);
						}else{
							$responseMessage = $prevMessage;
						}
					}
				}else{
					$error=true;
					$responseMessage='Selecciono un criterio no valido';
				}

			}
			
			if ($error) {
				$paginador = $this->paginador($idPer, $paginaActual);
		
				$persona = $paginador['persona'];
			}
		//}
	
		return $this->renderHTML('personaDocumentosList.twig', [
			'idPer' => $idPer,
			'numeroDePaginasBusqueda' => $numeroDePaginas,
			'documentos' => $documentos,
			'prevMessage' => $prevMessage,
			'responseMessage' => $responseMessage,
			'queryErrorMessage' => $queryErrorMessage,
			'paginaActual' => $paginaActual,
			'textBuscar' => $textBuscar,
			'criterio' => $criterio,
			'persona' => $persona
		]);
		
	}

	public function postDelDocumentos($request){
		$documentos=null; $responseMessage = null; $id=null; $numeroDePaginas=null; $persona=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$id = $postData['btnDelUpd'] ?? null;
			$idPer = $postData['idPer'] ?? null;
			
			if ($id) {	 
			  try{
				$people = new PersonaDocumentos();
				$people->destroy($id);
				$responseMessage = "Se elimino el documento";
			  }catch(\Exception $e){
			  	//$responseMessage = $e->getMessage();
			  	$prevMessage = substr($e->getMessage(), 0, 38);
				if ($prevMessage =="SQLSTATE[23503]: Foreign key violation") {
					$responseMessage = 'Error, No se puede eliminar, este documento esta en uso.';
				}else{
					$responseMessage= 'Error, No se puede eliminar, '.$prevMessage;
				}
			  }
			}else{
				$responseMessage = 'Debe Seleccionar un documento';
			}
		}
		
		$paginador = $this->paginador($idPer);

		$documentos = $paginador['documentos'];
		$numeroDePaginas = $paginador['numeroDePaginas'];
		$persona = $paginador['persona'];
		
		return $this->renderHTML('personaDocumentosList.twig', [
			'documentos' => $documentos,
			'responseMessage' => $responseMessage,
			'numeroDePaginas' => $numeroDePaginas,
			'idPer' => $idPer,
			'persona' => $persona
		]);
	}

	public function postUpdDocumentos($request){
		$tiposdocumentos=null; $documentos=null; $responseMessage = null; $id=null;
		$ruta='personaDocumentosUpdate.twig'; $numeroDePaginas=null; $persona=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$id = $postData['btnDelUpd'] ?? null;
			$idPer = $postData['idPer'] ?? null;
			
			if ($id) {
				$documentos = PersonaDocumentos::find($id);
				$tiposdocumentos = PersonaTiposDocumentosPer::orderBy('nombre')->get();	
			}else{
				$ruta='personaDocumentosList.twig';
				
				$paginador = $this->paginador($idPer);

				$documentos = $paginador['documentos'];
				$numeroDePaginas = $paginador['numeroDePaginas'];
				$persona = $paginador['persona'];

				$responseMessage = 'Debe Seleccionar un documento';
			}
		}
		
		return $this->renderHTML($ruta, [
			'documentos' => $documentos,
			'tiposdocumentos' => $tiposdocumentos,
			'responseMessage' => $responseMessage,
			'numeroDePaginas' => $numeroDePaginas,
			'idPer' => $idPer,
			'persona' => $persona
		]);
	}

	//en esta accion se registra las modificaciones del registro utiliza metodo post no get
	public function postUpdateDocumentos($request){
		$responseMessage = null; $registrationErrorMessage=null; $documentos=null; $numeroDePaginas=null; $persona=null;
		$ruta='personaDocumentosList.twig';

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();

			/*En la variable $files se almacena el $request del file y en $fileComprobante se
			*almacena el array con todas las propiedades de este file*/
			$files = $request->getUploadedFiles();
			if ($files) {
				$fileComprobante = $files['urlcomprobante'];
				$temporaryFileName = 'docp'.$postData['referencia'];
				
				/*Se hace llamado al metodo que se creo para validar las imagenes */
				$FilesValidatorController = new FilesValidatorController();
				$validadorComprobante = $FilesValidatorController->filesValidator($fileComprobante, $temporaryFileName);	
			}else{
				$validadorComprobante['error']=1;
				$validadorComprobante['message']="Error, el comprobante no puede pesar mas de 2MB, seleccione nuevamente la persona y agregué un comprobante valido";
				$ruta='personasList.twig';
			}
			

			$idPer = $postData['idPer'] ?? null;

			$documentoValidator = v::key('referencia', v::stringType()->length(1, 50)->notEmpty())
					->key('emisor', v::stringType()->length(1, 100)->notEmpty())
					->key('fechainicio', v::date())
					->key('fechafinal', v::date())
					->key('tdpid', v::numeric()->positive()->notEmpty());

			
			if($_SESSION['userId']){

				//Si no ocurrio error en la validacion del file 
				if ($validadorComprobante['error'] == 0 or $validadorComprobante['error'] == 4) {
					$fileName = $validadorComprobante['fileName'];
					try{
						$documentoValidator->assert($postData);
						$postData = $request->getParsedBody();

						//la siguiente linea hace una consulta en la DB y trae el registro where id=$id y lo guarda en documento y posteriormente remplaza los valores y con el ->save() guarda la modificacion en la DB
						$idDocumento = $postData['id'];
						$documento = PersonaDocumentos::find($idDocumento);
						
						$documento->referencia=$postData['referencia'];
						$documento->emisor = $postData['emisor'];
						$documento->fechainicio = $postData['fechainicio'];
						$documento->fechafinal = $postData['fechafinal'];
						if ($validadorComprobante['error'] == 0) {
							$fileComprobante->moveTo("uploads/$fileName");	
							$documento->urlcomprobante = $fileName;
						}
						$documento->tdpid = $postData['tdpid'];
						$documento->iduserupdate = $_SESSION['userId'];
						$documento->save();

						$responseMessage = 'Editado.';
					}catch(\Exception $exception){
						//$responseMessage = $exception->getMessage();
						$prevMessage = substr($exception->getMessage(), 0, 25);
						if ($prevMessage == "SQLSTATE[23505]: Unique v") {
							$responseMessage = 'Error, La referencia ya esta registrada';
						}elseif ($prevMessage == "SQLSTATE[23503]: Foreign ") {
							$responseMessage = 'Error, El ID de esta persona no esta registrado';
						}elseif ($prevMessage == "SQLSTATE[42703]: Undefine") {
							$responseMessage = 'Error interno de base de datos, en el pie de pagina esta toda la información de contacto, por favor contáctenos para darle una rápida solución.';
						}elseif($prevMessage == 'These rules must pass for' or $prevMessage == 'All of the required rules') {
							$registrationErrorMessage = $exception->findMessages([
							'notEmpty' => '- Los campos con (*) no pueden estar vacios',
							'length' => '- Tiene una longitud no permitida',
							'stringType' => '- Solo puede contener numeros y letras',
							'date' => '- Formato de fecha no valido',
							'numeric' => '- Solo puede contener numeros', 
							'positive' => '- Solo puede contener numeros mayores a cero'
							]);
						}else{
							$responseMessage = $prevMessage;
						}
					}
				}else{
					//si la validacion del $file da error, se guarda en la variable $responseMessage el mensaje de error
					$responseMessage = $validadorComprobante['message'];
				}

			}
		}

		$paginador = $this->paginador($idPer);
		$persona = $paginador['persona'];
		if ($responseMessage == 'Editado.') {
			$documentos = $paginador['documentos'];
			$numeroDePaginas = $paginador['numeroDePaginas'];
		}
		

		return $this->renderHTML($ruta,[
				'idPer' => $idPer,
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'documentos' => $documentos,
				'numeroDePaginas' => $numeroDePaginas,
				'persona' => $persona
		]);
	}
}

?>
