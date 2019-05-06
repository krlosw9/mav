<?php 

namespace App\Controllers;

use App\Models\{Vehiculos,VehiculoDocumentosVeh, VehiculoTipoDocumentosVeh};
use App\Controllers\{FilesValidatorController};
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Zend\Diactoros\Response\RedirectResponse;

class VehiculoDocumentosController extends BaseController{
	
	//estos dos valores son los que se cambian, para modificar la cantidad de registros listados por pagina y el maximo numero en paginacion
	private $articulosPorPagina=10;
	private $limitePaginacion=20;
	private $mensajePersonaNoDefinida="Vehículo no definido, por favor seleccione un vehículo y luego presione el botón documentos";


	public function getAddDocumentos(){
		$responseMessage=null; $ruta='vehiculoDocumentosAdd.twig'; $tiposdocumentos=null;

		$idVeh = $_GET['?'] ?? null;

		if ($idVeh) {
			$tiposdocumentos = VehiculoTipoDocumentosVeh::orderBy('nombre')->get();
		}else{
			$responseMessage = $this->mensajePersonaNoDefinida;
			$ruta='vehiculoDocumentosList.twig';
		}

		return $this->renderHTML($ruta,[
				'responseMessage' => $responseMessage,
				'idVeh' => $idVeh,
				'tiposdocumentos' => $tiposdocumentos,
		]);
	}

	//Registra el Documento
	public function postAddDocumentos($request){
		$responseMessage = null; $registrationErrorMessage=null; $fileName=null;
		$documentos = null; $numeroDePaginas=null; $idVeh=0; $vehiculo=null; $ruta='vehiculoDocumentosList.twig';


		if($request->getMethod()=='POST'){
			//crea el array $postData pasando las variables POST como indices de este array
			$postData = $request->getParsedBody();

			/*En la variable $files se almacena el $request del file y en $fileComprobante se
			*almacena el array con todas las propiedades de este file*/
			$files = $request->getUploadedFiles();
			if ($files) {
				$fileComprobante = $files['urlcomprobante'];
				$temporaryFileName = 'docv'.$postData['referencia'];
				
				/*Se hace llamado al metodo que se creo para validar las imagenes */
				$FilesValidatorController = new FilesValidatorController();
				$validadorComprobante = $FilesValidatorController->filesValidator($fileComprobante, $temporaryFileName);	
			}else{
				$validadorComprobante['error']=1;
				$validadorComprobante['message']="Error, el comprobante no puede pesar mas de 2MB, seleccione nuevamente el vehículo y agregué un comprobante valido";
				$ruta='vehiculosList.twig';
			}
			
			$idVeh = $postData['idVeh'] ?? null;

			$documentoValidator = v::key('referencia', v::stringType()->length(1, 50)->notEmpty())
					->key('emisor', v::stringType()->length(1, 100)->notEmpty())
					->key('fechainicio', v::date())
					->key('fechafinal', v::date())
					->key('tdvid', v::numeric()->positive()->notEmpty());
			
			if($_SESSION['userId']){
				if ($validadorComprobante['error'] == 0) {
					$fileName = $validadorComprobante['fileName'];

					try{
						$documentoValidator->assert($postData);
						$postData = $request->getParsedBody();

						$documento = new VehiculoDocumentosVeh();
						$documento->referencia=$postData['referencia'];
						$documento->emisor = $postData['emisor'];
						$documento->fechainicio = $postData['fechainicio'];
						$documento->fechafinal = $postData['fechafinal'];
						$fileComprobante->moveTo("uploads/$fileName");
						$documento->urlcomprobante = $fileName;
						$documento->vehid = $idVeh;
						$documento->tdvid = $postData['tdvid'];
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
		
		$numeroDeFilas = VehiculoDocumentosVeh::selectRaw('count(*) as query_count')
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
		
		$documentos = VehiculoDocumentosVeh::Join("vehiculo.tipodocumentosveh","vehiculo.documentosveh.tdvid","=","vehiculo.tipodocumentosveh.id")
		->where("vehiculo.documentosveh.vehid","=",$idVeh)
		->select('vehiculo.documentosveh.*', 'vehiculo.tipodocumentosveh.nombre As tipoDocumento')
		->latest('vehiculo.documentosveh.id')
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

	public function paginadorWhere($idVeh=null, $paginaActual=null, $criterio=null, $comparador='=', $textBuscar=null, $orden='latest', $criterioOrden='id'){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $documentos=null; $vehiculo=null;
		
		$numeroDeFilas = VehiculoDocumentosVeh::where($criterio, $comparador ,$textBuscar)->selectRaw('count(*) as query_count')
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

		$documentos = VehiculoDocumentosVeh::Join("vehiculo.tipodocumentosveh","vehiculo.documentosveh.tdvid","=","vehiculo.tipodocumentosveh.id")
		->where("vehiculo.documentosveh.vehid","=",$idVeh)
		->where($criterio,$comparador,$textBuscar)
		->select('vehiculo.documentosveh.*', 'vehiculo.tipodocumentosveh.nombre As tipoDocumento')
		->$orden($criterioOrden)
		//->latest('vehiculo.documentosveh.id')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();	

		/* Consulta toda la informacion de la persona a la cual se le estan buscando los Documentos */
		$vehiculo = Vehiculos::find($idVeh);		

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'documentos' => $documentos,
			'vehiculo' => $vehiculo

		];
		
		return $retorno;
	}

	//Este metodo unicamente es llamado desde personasList.twig al seleccionar una persona y darle al boton documentos en el controller PersonasController en el metodo postUpdDelPersonas(), esta es la unica forma de entrada a este controller. (Porque si no se selecciona una persona, no se pueden ver sus documentos)
	public function listVehiculosDocumentos($idVeh=null){
		$numeroDePaginas=null; $documentos=null; $vehiculo=null;

		$paginador = $this->paginador($idVeh);
		
		$documentos = $paginador['documentos'];
		$numeroDePaginas = $paginador['numeroDePaginas'];
		$vehiculo = $paginador['vehiculo'];
		
		return $this->renderHTML('vehiculoDocumentosList.twig', [
			'idVeh' => $idVeh,
			'documentos' => $documentos,
			'numeroDePaginas' => $numeroDePaginas,
			'vehiculo' => $vehiculo
		]);
	}

	public function getListDocumentos(){
		$responseMessage=null; $numeroDePaginas=null; $documentos=null; $numeroDePaginas=null; $vehiculo=null;
		
		$idVeh = $_GET['?'] ?? null;
		$paginaActual = $_GET['pag'] ?? null;

		if ($idVeh) {
			$paginador = $this->paginador($idVeh, $paginaActual);
		
			$documentos = $paginador['documentos'];
			$numeroDePaginas = $paginador['numeroDePaginas'];	
			$vehiculo = $paginador['vehiculo'];

		}else{
			$responseMessage = $this->mensajePersonaNoDefinida;
		}
		
		
		return $this->renderHTML('vehiculoDocumentosList.twig', [
			'idVeh' => $idVeh,
			'responseMessage' => $responseMessage,
			'documentos' => $documentos,
			'numeroDePaginas' => $numeroDePaginas,
			'paginaActual' => $paginaActual,
			'vehiculo' => $vehiculo
		]);
	}


	public function postBusquedaDocumentos($request){
		$prevMessage = null; $responseMessage=null; $iniciar=0; $documentos=null; $queryErrorMessage=null; $error=false;
		$numeroDePaginas=null; $paginaActual=null; $criterio=null; $textBuscar=null; $idVeh=null; $vehiculo=null;

		//if($request->getMethod()=='POST'){
		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$textBuscar = $postData['textBuscar'] ?? null;
			$criterio = $postData['criterio'] ?? null;
			$idVeh = $postData['idVeh'] ?? null;
		}elseif ($request->getMethod()=='GET') {
			$getData = $request->getQueryParams();
			$paginaActual = $getData['pag'] ?? null;
			$criterio = $getData['?'] ?? null;
			$textBuscar = $getData['??'] ?? null;
			$idVeh = $getData['%'] ?? null;

			//el textBuscar que llega por GET lo paso al array $postData para de esta forma usar el validador
			$postData['textBuscar'] = $textBuscar;
		}


			if ($textBuscar) {

				if ($criterio==1) {
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 50)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();
						
						$criterioQuery="vehiculo.documentosveh.referencia"; $comparador='ilike';
						//$textBuscarModificado='%'.$textBuscar.'%';
						$textBuscarModificado=$textBuscar;
						$paginador = $this->paginadorWhere($idVeh,$paginaActual, $criterioQuery,$comparador, $textBuscarModificado);
						$documentos=$paginador['documentos'];
						$numeroDePaginas=$paginador['numeroDePaginas'];
						$vehiculo=$paginador['vehiculo'];

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

						$criterioQuery="vehiculo.documentosveh.emisor"; $comparador='ilike'; $orden='orderBy';
						//$textBuscarModificado='%'.$textBuscar.'%';
						$textBuscarModificado=$textBuscar;
						$paginador = $this->paginadorWhere($idVeh,$paginaActual, $criterioQuery, $comparador, $textBuscarModificado,$orden, $criterioQuery);
						$documentos=$paginador['documentos'];
						$numeroDePaginas=$paginador['numeroDePaginas'];
						$vehiculo=$paginador['vehiculo'];

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
				$paginador = $this->paginador($idVeh, $paginaActual);
		
				$vehiculo = $paginador['vehiculo'];
			}
		//}
	
		return $this->renderHTML('vehiculoDocumentosList.twig', [
			'idVeh' => $idVeh,
			'numeroDePaginasBusqueda' => $numeroDePaginas,
			'documentos' => $documentos,
			'prevMessage' => $prevMessage,
			'responseMessage' => $responseMessage,
			'queryErrorMessage' => $queryErrorMessage,
			'paginaActual' => $paginaActual,
			'textBuscar' => $textBuscar,
			'criterio' => $criterio,
			'vehiculo' => $vehiculo
		]);
		
	}


	/*Al seleccionar uno de los dos botones (Eliminar o Actualizar) llega a esta accion y verifica cual de los dos botones oprimio si eligio el boton eliminar(del) elimina el registro de where $id Pero
	Si elige actualizar(upd) cambia la ruta del renderHTML y guarda una consulta de los datos del registro a modificar para mostrarlos en formulario de actualizacion llamado updateActOperario.twig y cuando modifica los datos y le da guardar a ese formulaio regresa a esta class y elige la accion getUpdateActivity()*/
	public function postUpdDelDocumentos($request){
		$tiposdocumentos=null; $documentos=null; $responseMessage = null; $id=null; $boton=null;
		$quiereActualizar = false; $ruta='vehiculoDocumentosList.twig'; $numeroDePaginas=null; $vehiculo=null;
		$mensajeNoPermisos='Su rol no tiene permisos para realizar esta funcion';

		$sessionUserPermission = $_SESSION['userLicense'] ?? null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$btnDelUpd = $postData['btnDelUpd'] ?? null;
			$idVeh = $postData['idVeh'] ?? null;
			
			if ($btnDelUpd) {
				$divideCadena = explode("|", $btnDelUpd);
				$boton=$divideCadena[0];
				$id=$divideCadena[1];
			}
			if ($id) {
				if($boton == 'del'){
				 if (in_array('peopledel', $sessionUserPermission)) {
				  try{
					$people = new VehiculoDocumentosVeh();
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
				 	$responseMessage=$mensajeNoPermisos;
				 }
				}elseif ($boton == 'upd') {
				  if (in_array('peopleupd', $sessionUserPermission)) {
					$quiereActualizar=true;
				  }else{
				  	$responseMessage=$mensajeNoPermisos;
				  }
				}
			}else{
				$responseMessage = 'Debe Seleccionar un documento';
			}
		}
		
		if ($quiereActualizar){
			//si quiere actualizar hace una consulta where id=$id y la envia por el array del renderHtml
			$documentos = VehiculoDocumentosVeh::find($id);
			$tiposdocumentos = VehiculoTipoDocumentosVeh::orderBy('nombre')->get();

			$ruta='vehiculoDocumentosUpdate.twig';
		}else{
			$paginador = $this->paginador($idVeh);

			$documentos = $paginador['documentos'];
			$numeroDePaginas = $paginador['numeroDePaginas'];
			$vehiculo = $paginador['vehiculo'];
		}
		return $this->renderHTML($ruta, [
			'documentos' => $documentos,
			'tiposdocumentos' => $tiposdocumentos,
			'responseMessage' => $responseMessage,
			'numeroDePaginas' => $numeroDePaginas,
			'idVeh' => $idVeh,
			'vehiculo' => $vehiculo
		]);
	}

	//en esta accion se registra las modificaciones del registro utiliza metodo post no get
	public function postUpdateDocumentos($request){
		$responseMessage = null; $registrationErrorMessage=null; $documentos=null; $numeroDePaginas=null; $vehiculo=null;
		$ruta='vehiculoDocumentosList.twig';

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();

			/*En la variable $files se almacena el $request del file y en $fileComprobante se
			*almacena el array con todas las propiedades de este file*/
			$files = $request->getUploadedFiles();
			if ($files) {
				$fileComprobante = $files['urlcomprobante'];
				$temporaryFileName = 'docv'.$postData['referencia'];
				
				/*Se hace llamado al metodo que se creo para validar las imagenes */
				$FilesValidatorController = new FilesValidatorController();
				$validadorComprobante = $FilesValidatorController->filesValidator($fileComprobante, $temporaryFileName);	
			}else{
				$validadorComprobante['error']=1;
				$validadorComprobante['message']="Error, el comprobante no puede pesar mas de 2MB, seleccione nuevamente el vehículo y agregué un comprobante valido";
				$ruta='vehiculosList.twig';
			}
			

			$idVeh = $postData['idVeh'] ?? null;

			$documentoValidator = v::key('referencia', v::stringType()->length(1, 50)->notEmpty())
					->key('emisor', v::stringType()->length(1, 100)->notEmpty())
					->key('fechainicio', v::date())
					->key('fechafinal', v::date())
					->key('tdvid', v::numeric()->positive()->notEmpty());

			
			if($_SESSION['userId']){

				//Si no ocurrio error en la validacion del file 
				if ($validadorComprobante['error'] == 0 or $validadorComprobante['error'] == 4) {
					$fileName = $validadorComprobante['fileName'];
					try{
						$documentoValidator->assert($postData);
						$postData = $request->getParsedBody();

						//la siguiente linea hace una consulta en la DB y trae el registro where id=$id y lo guarda en documento y posteriormente remplaza los valores y con el ->save() guarda la modificacion en la DB
						$idDocumento = $postData['id'];
						$documento = VehiculoDocumentosVeh::find($idDocumento);
						
						$documento->referencia=$postData['referencia'];
						$documento->emisor = $postData['emisor'];
						$documento->fechainicio = $postData['fechainicio'];
						$documento->fechafinal = $postData['fechafinal'];
						if ($validadorComprobante['error'] == 0) {
							$fileComprobante->moveTo("uploads/$fileName");	
							$documento->urlcomprobante = $fileName;
						}
						$documento->tdvid = $postData['tdvid'];
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
