<?php 

namespace App\Controllers;

use App\Models\{Personas,PersonaDocumentos, PersonaTiposDocumentosPer};
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
		$responseMessage = null; $registrationErrorMessage=null;
		$documentos = null; $numeroDePaginas=null; $idPer=0; $persona=null;


		if($request->getMethod()=='POST'){
			//crea el array $postData pasando las variables POST como indices de este array
			$postData = $request->getParsedBody();
			//crea el array $files guardando el archivo (files) y sus propiedades
			$files = $request->getUploadedFiles(); var_dump($files);
			
			$documentoValidator = v::key('referencia', v::stringType()->length(1, 50)->notEmpty())
					->key('emisor', v::stringType()->length(1, 100)->notEmpty())
					->key('fechainicio', v::date())
					->key('fechafinal', v::date())
					->key('tdpid', v::numeric()->positive()->notEmpty());
			
			
			if($_SESSION['userId']){
				try{
					$documentoValidator->assert($postData);
					//$filesValidator->assert($files);
					assert($filesValidator);
					$postData = $request->getParsedBody();

					/* Toma el archivo y lo mueve a la carpeta upload en public */
					$fileImg = $files['urlcomprobante'];
					
					if($fileImg->getError() == UPLOAD_ERR_OK){
						$fileName = $fileImg->getClientFilename();
						$imgName = 'docp'.$postData['referencia'].$fileName;
						//$fileImg->moveTo("uploads/$imgName");
					}


					$idPer = $postData['idPer'];

					$documento = new PersonaDocumentos();
					$documento->referencia=$postData['referencia'];
					$documento->emisor = $postData['emisor'];
					$documento->fechainicio = $postData['fechainicio'];
					$documento->fechafinal = $postData['fechafinal'];
					$documento->urlcomprobante = $imgName;
					$documento->activocheck = 1;
					$documento->perid = $idPer;
					$documento->tdpid = $postData['tdpid'];
					$documento->iduserregister = $_SESSION['userId'];
					$documento->iduserupdate = $_SESSION['userId'];
					$documento->save();

					$responseMessage = 'Registrado';
				}catch(\Exception $exception){
					//$responseMessage = $exception->getMessage();
					$prevMessage = substr($exception->getMessage(), 0, 33);
					$responseMessage = substr($exception->getMessage(), 0, 200);	
echo "mensaje: $responseMessage";
					if ($prevMessage == "SQLSTATE[23505]: Unique violation") {
						$responseMessage = 'Error, La referencia ya esta registrada';
					}elseif ($prevMessage == "SQLSTATE[23503]: Foreign key viol") {
						$responseMessage = 'Error, El ID de esta persona no esta registrado';
					}elseif ($prevMessage == "SQLSTATE[42703]: Undefined column") {
						$responseMessage = 'Error interno de base de datos, en el pie de pagina esta toda la información de contacto, por favor contáctenos para darle una rápida solución.';
					}else{
						$registrationErrorMessage = $exception->findMessages([
						'notEmpty' => '- Los campos con (*) no pueden estar vacios',
						'length' => '- Tiene una longitud no permitida',
						'stringType' => '- Solo puede contener numeros y letras',
						'date' => '- Formato de fecha no valido',
						'numeric' => '- Solo puede contener numeros', 
						'positive' => '- Solo puede contener numeros mayores a cero'
						]) ?? null;
						
					}
				}
			}
		}
		
		$paginador = $this->paginador($idPer);

		$documentos = $paginador['documentos'];
		$numeroDePaginas = $paginador['numeroDePaginas'];
		$persona = $paginador['persona'];

		return $this->renderHTML('personaDocumentosList.twig',[
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

	//Este metodo unicamente es llamado desde personasList.twig al seleccionar una persona y darle al boton documentos en el controller PersonasController en el metodo postUpdDelPersonas(), esta es la unica forma de entrada a este controller. (Porque si no se selecciona una persona, no se pueden ver sus documentos)
	public function listPersonasDocumentos($idPer=null){
		$numeroDePaginas=null; $documentos=null; $persona=null;

		$paginador = $this->paginador($idPer);
		
		$documentos = $paginador['documentos'];
		$numeroDePaginas = $paginador['numeroDePaginas'];
		$persona = $paginador['persona'];
		
		return $this->renderHTML('personaDocumentosList.twig', [
			'idPer' => $idPer,
			'documentos' => $documentos,
			'numeroDePaginas' => $numeroDePaginas,
			'persona' => $persona
		]);
	}

	public function getListDocumentos(){
		$responseMessage=null; $numeroDePaginas=null; $documentos=null; $numeroDePaginas=null; $persona=null;
		
		$idPer = $_GET['?'] ?? null;
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
					$personaValidator = v::key('textBuscar', v::numeric()->positive()->length(1, 50)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();
						
						$criterioQuery="persona.documentosper.referencia"; $comparador='ilike';
						$textBuscarModificado='%'.$textBuscar.'%';
						$paginador = $this->paginadorWhere($idPer,$paginaActual, $criterioQuery,$comparador, $textBuscarModificado);
						$documentos=$paginador['documentos'];
						$numeroDePaginas=$paginador['numeroDePaginas'];
						$persona=$paginador['persona'];

					}catch(\Exception $exception){
						$error=true;
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

						$criterioQuery="persona.documentosper.emisor"; $comparador='ilike'; $orden='orderBy';
						$textBuscarModificado='%'.$textBuscar.'%';
						$paginador = $this->paginadorWhere($idPer,$paginaActual, $criterioQuery, $comparador, $textBuscarModificado,$orden, $criterioQuery);
						$documentos=$paginador['documentos'];
						$numeroDePaginas=$paginador['numeroDePaginas'];
						$persona=$paginador['persona'];

					}catch(\Exception $exception){
						$error=true;
						//$prevMessage = $exception->getMessage();
						$prevMessage = substr($exception->getMessage(), 0, 30);

						if ($prevMessage == 'SQLSTATE[42703]: Undefined col') {
							$prevMessage= "(Parámetro de criterio incorrecto)";
						}else{
							$queryErrorMessage = $exception->findMessages([
							'notEmpty' => '- El texto de busqueda no puede quedar vacio',
							'length' => '- Tiene una longitud no permitida',
							'stringType' => '- Solo puede contener nombres'
							]);
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


	/*Al seleccionar uno de los dos botones (Eliminar o Actualizar) llega a esta accion y verifica cual de los dos botones oprimio si eligio el boton eliminar(del) elimina el registro de where $id Pero
	Si elige actualizar(upd) cambia la ruta del renderHTML y guarda una consulta de los datos del registro a modificar para mostrarlos en formulario de actualizacion llamado updateActOperario.twig y cuando modifica los datos y le da guardar a ese formulaio regresa a esta class y elige la accion getUpdateActivity()*/
	public function postUpdDelDocumentos($request){
		$tiposdocumentos=null; $documentos=null; $responseMessage = null; $id=null; $boton=null;
		$quiereActualizar = false; $ruta='personaDocumentosList.twig'; $numeroDePaginas=null; $persona=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$btnDelUpd = $postData['btnDelUpd'] ?? null;
			$idPer = $postData['idPer'] ?? null;
			
			if ($btnDelUpd) {
				$divideCadena = explode("|", $btnDelUpd);
				$boton=$divideCadena[0];
				$id=$divideCadena[1];
			}
			if ($id) {
				if($boton == 'del'){
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
				}elseif ($boton == 'upd') {
					$quiereActualizar=true;
				}
			}else{
				$responseMessage = 'Debe Seleccionar un documento';
			}
		}
		
		if ($quiereActualizar){
			//si quiere actualizar hace una consulta where id=$id y la envia por el array del renderHtml
			$documentos = PersonaDocumentos::find($id);
			$tiposdocumentos = PersonaTiposDocumentosPer::orderBy('nombre')->get();

			$ruta='personaDocumentosUpdate.twig';
		}else{
			$paginador = $this->paginador($idPer);

			$documentos = $paginador['documentos'];
			$numeroDePaginas = $paginador['numeroDePaginas'];
			$persona = $paginador['persona'];
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
				
		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();

			$idPer = $postData['idPer'] ?? null;

			$documentoValidator = v::key('referencia', v::stringType()->length(1, 50)->notEmpty())
					->key('emisor', v::stringType()->length(1, 100)->notEmpty())
					->key('fechainicio', v::date())
					->key('fechafinal', v::date())
					->key('tdpid', v::numeric()->positive()->notEmpty());

			
			if($_SESSION['userId']){
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
					$documento->tdpid = $postData['tdpid'];
					$documento->activocheck = 1;
					$documento->iduserregister = $_SESSION['userId'];
					$documento->iduserupdate = $_SESSION['userId'];
					$documento->save();

					$responseMessage .= 'Editado.';
				}catch(\Exception $exception){
					//$responseMessage = $exception->getMessage();
					$prevMessage = substr($exception->getMessage(), 0, 33);
					$responseMessage = substr($exception->getMessage(), 0, 33);	

					if ($prevMessage == "SQLSTATE[23505]: Unique violation") {
						$responseMessage = 'Error, La referencia ya esta registrada';
					}elseif ($prevMessage == "SQLSTATE[23503]: Foreign key viol") {
						$responseMessage = 'Error, El ID de esta persona no esta registrado';
					}elseif ($prevMessage == "SQLSTATE[42703]: Undefined column") {
						$responseMessage = 'Error interno de base de datos, en el pie de pagina esta toda la información de contacto, por favor contáctenos para darle una rápida solución.';
					}else{
						$registrationErrorMessage = $exception->findMessages([
						'notEmpty' => '- Los campos con (*) no pueden estar vacios',
						'length' => '- Tiene una longitud no permitida',
						'stringType' => '- Solo puede contener numeros y letras',
						'date' => '- Formato de fecha no valido',
						'numeric' => '- Solo puede contener numeros', 
						'positive' => '- Solo puede contener numeros mayores a cero'
						]) ?? null;
					}
				}
			}
		}

		
		$paginador = $this->paginador($idPer);

		$documentos = $paginador['documentos'];
		$numeroDePaginas = $paginador['numeroDePaginas'];
		$persona = $paginador['persona'];

		return $this->renderHTML('personaDocumentosList.twig',[
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
