<?php 

namespace App\Controllers;

use App\Models\{Personas,PersonaLicencias, PersonaCategoriasLicencias};
use App\Controllers\{FilesValidatorController};
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Zend\Diactoros\Response\RedirectResponse;

class PersonaLicenciasController extends BaseController{
	
	//estos dos valores son los que se cambian, para modificar la cantidad de registros listados por pagina y el maximo numero en paginacion
	private $articulosPorPagina=10;
	private $limitePaginacion=20;
	private $mensajePersonaNoDefinida="Persona no definida, por favor seleccione una persona y luego presione el botón licencias";


	public function getAddLicencias(){
		$responseMessage=null; $ruta='personaLicenciasAdd.twig'; $categoriaslicencias=null;

		$idPer = $_GET['?'] ?? null;

		if ($idPer) {
			$categoriaslicencias = PersonaCategoriasLicencias::orderBy('descripcion')->get();
		}else{
			$responseMessage = $this->mensajePersonaNoDefinida;
			$ruta='personaLicenciasList.twig';
		}

		return $this->renderHTML($ruta,[
				'responseMessage' => $responseMessage,
				'idPer' => $idPer,
				'categoriaslicencias' => $categoriaslicencias,
		]);
	}

	//Registra la Licencia
	public function postAddLicencias($request){
		$responseMessage = null; $registrationErrorMessage=null; $fileName=null;
		$licencias = null; $numeroDePaginas=null; $idPer=0; $persona=null; $ruta='personaLicenciasList.twig';


		if($request->getMethod()=='POST'){
			//crea el array $postData pasando las variables POST como indices de este array
			$postData = $request->getParsedBody();
			
			/*En la variable $files se almacena el $request del file y en $fileComprobante se
			*almacena el array con todas las propiedades de este file*/
			$files = $request->getUploadedFiles(); 
			if ($files) {
				$fileComprobante = $files['urlcomprobante']; 
				$temporaryFileName = 'licp'.$postData['numero'].$postData['catid'];
				
				/*Se hace llamado al metodo que se creo para validar las imagenes */
				$FilesValidatorController = new FilesValidatorController();
				$validadorComprobante = $FilesValidatorController->filesValidator($fileComprobante, $temporaryFileName);
			}else{
				$validadorComprobante['error']=1;
				$validadorComprobante['message']="Error, el comprobante no puede pesar mas de 2MB, seleccione nuevamente la persona y agregué un comprobante valido";
				$ruta='personasList.twig';
			}
			

			$documentoValidator = v::key('numero', v::stringType()->length(1, 50)->notEmpty())
					->key('fechaexpedicion', v::date())
					->key('fechavencimiento', v::date())
					->key('catid', v::numeric()->positive()->notEmpty());
			$idPer = $postData['idPer'] ?? null;
			
			if($_SESSION['userId']){
				if ($validadorComprobante['error'] == 0) {
					$fileName = $validadorComprobante['fileName'];

					try{
						$documentoValidator->assert($postData);
						$postData = $request->getParsedBody();

						$licencia = new PersonaLicencias();
						$licencia->numero=$postData['numero'];
						$licencia->fechaexpedicion = $postData['fechaexpedicion'];
						$licencia->fechavencimiento = $postData['fechavencimiento'];
						$fileComprobante->moveTo("uploads/$fileName");
						$licencia->urlcomprobante = $fileName;
						$licencia->perid = $idPer;
						$licencia->catid = $postData['catid'];
						$licencia->iduserregister = $_SESSION['userId'];
						$licencia->iduserupdate = $_SESSION['userId'];
						$licencia->save();

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
			$licencias = $paginador['licencias'];
			$numeroDePaginas = $paginador['numeroDePaginas'];	
		}

		return $this->renderHTML($ruta,[
				'idPer' => $idPer,
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'licencias' => $licencias,
				'numeroDePaginas' => $numeroDePaginas,
				'persona' => $persona
		]);
	}

 
	//Lista todas los modelos Ordenando por posicion
	public function paginador($idPer=0, $paginaActual=0){
		$retorno = array(); $iniciar=0; $licencias=null; $persona=null;
		
		$numeroDeFilas = PersonaLicencias::selectRaw('count(*) as query_count')
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
		
		$licencias = PersonaLicencias::Join("persona.categoriaslicencias","persona.licencias.catid","=","persona.categoriaslicencias.id")
		->where("persona.licencias.perid","=",$idPer)
		->select('persona.licencias.*', 'persona.categoriaslicencias.descripcion As categoria')
		->latest('persona.licencias.id')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		/* Consulta toda la informacion de la persona a la cual se le estan buscando los Documentos */
		$persona = Personas::find($idPer);

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'licencias' => $licencias,
			'persona' => $persona
		];

		return $retorno;
		
	}

	public function paginadorWhere($idPer=null, $paginaActual=null, $criterio=null, $comparador='=', $textBuscar=null, $orden='latest', $criterioOrden='id'){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $licencias=null; $persona=null;
		
		$numeroDeFilas = PersonaLicencias::where($criterio, $comparador ,$textBuscar)->selectRaw('count(*) as query_count')
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

		$licencias = PersonaLicencias::Join("persona.categoriaslicencias","persona.licencias.catid","=","persona.categoriaslicencias.id")
		->where("persona.licencias.perid","=",$idPer)
		->where($criterio,$comparador,$textBuscar)
		->select('persona.licencias.*', 'persona.categoriaslicencias.descripcion As categoria')
		//->$orden($criterioOrden)
		->latest('persona.licencias.id')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();	

		/* Consulta toda la informacion de la persona a la cual se le estan buscando los Documentos */
		$persona = Personas::find($idPer);		

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'licencias' => $licencias,
			'persona' => $persona

		];
		
		return $retorno;
	}

	//Este metodo unicamente es llamado desde personasList.twig al seleccionar una persona y darle al boton licencias en el controller PersonasController en el metodo postUpdDelPersonas(), esta es la unica forma de entrada a este controller. (Porque si no se selecciona una persona, no se pueden ver sus licencias)
	public function listPersonasLicencias($idPer=null){
		$numeroDePaginas=null; $licencias=null; $persona=null;

		$paginador = $this->paginador($idPer);
		
		$licencias = $paginador['licencias'];
		$numeroDePaginas = $paginador['numeroDePaginas'];
		$persona = $paginador['persona'];
		
		return $this->renderHTML('personaLicenciasList.twig', [
			'idPer' => $idPer,
			'licencias' => $licencias,
			'numeroDePaginas' => $numeroDePaginas,
			'persona' => $persona
		]);
	}

	public function getListLicencias(){
		$responseMessage=null; $numeroDePaginas=null; $licencias=null; $numeroDePaginas=null; $persona=null;
		
		$idPer = $_GET['?'] ?? null;
		$paginaActual = $_GET['pag'] ?? null;

		if ($idPer) {
			$paginador = $this->paginador($idPer, $paginaActual);
		
			$licencias = $paginador['licencias'];
			$numeroDePaginas = $paginador['numeroDePaginas'];	
			$persona = $paginador['persona'];

		}else{
			$responseMessage = $this->mensajePersonaNoDefinida;
		}
		
		
		return $this->renderHTML('personaLicenciasList.twig', [
			'idPer' => $idPer,
			'responseMessage' => $responseMessage,
			'licencias' => $licencias,
			'numeroDePaginas' => $numeroDePaginas,
			'paginaActual' => $paginaActual,
			'persona' => $persona
		]);
	}


	public function postBusquedaLicencias($request){
		$prevMessage = null; $responseMessage=null; $iniciar=0; $licencias=null; $queryErrorMessage=null; $error=false;
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
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 15)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();
						
						$criterioQuery="persona.licencias.numero"; $comparador='ilike';
						//$textBuscarModificado='%'.$textBuscar.'%';
						$textBuscarModificado=$textBuscar;
						$paginador = $this->paginadorWhere($idPer,$paginaActual, $criterioQuery,$comparador, $textBuscarModificado);
						$licencias=$paginador['licencias'];
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

						$criterioQuery="persona.licencias.fechaexpedicion"; $comparador='ilike'; $orden='orderBy';
						//$textBuscarModificado='%'.$textBuscar.'%';
						$textBuscarModificado=$textBuscar;
						$paginador = $this->paginadorWhere($idPer,$paginaActual, $criterioQuery, $comparador, $textBuscarModificado,$orden, $criterioQuery);
						$licencias=$paginador['licencias'];
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
	
		return $this->renderHTML('personaLicenciasList.twig', [
			'idPer' => $idPer,
			'numeroDePaginasBusqueda' => $numeroDePaginas,
			'licencias' => $licencias,
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
	public function postUpdDelLicencias($request){
		$categoriaslicencias=null; $licencias=null; $responseMessage = null; $id=null; $boton=null;
		$quiereActualizar = false; $ruta='personaLicenciasList.twig'; $numeroDePaginas=null; $persona=null;
		$mensajeNoPermisos='Su rol no tiene permisos para realizar esta funcion';

		$sessionUserPermission = $_SESSION['userLicense'] ?? null;

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
				 if (in_array('licensedel', $sessionUserPermission)) {
				  try{
					$people = new PersonaLicencias();
					$people->destroy($id);
					$responseMessage = "Se elimino la licencias";
				  }catch(\Exception $e){
				  	//$responseMessage = $e->getMessage();
				  	$prevMessage = substr($e->getMessage(), 0, 38);
					if ($prevMessage =="SQLSTATE[23503]: Foreign key violation") {
						$responseMessage = 'Error, No se puede eliminar, esta licencia esta en uso.';
					}else{
						$responseMessage= 'Error, No se puede eliminar, '.$prevMessage;
					}
				  }
				 }else{
				 	$responseMessage=$mensajeNoPermisos;
				 }
				}elseif ($boton == 'upd') {
				  if (in_array('licenseupdate', $sessionUserPermission)) {
					$quiereActualizar=true;
				  }else{
				  	$responseMessage=$mensajeNoPermisos;
				  }
				}
			}else{
				$responseMessage = 'Debe Seleccionar una licencia';
			}
		}
		
		if ($quiereActualizar){
			//si quiere actualizar hace una consulta where id=$id y la envia por el array del renderHtml
			$licencias = PersonaLicencias::find($id);
			$categoriaslicencias = PersonaCategoriasLicencias::orderBy('descripcion')->get();

			$ruta='personaLicenciasUpdate.twig';
		}else{
			$paginador = $this->paginador($idPer);

			$licencias = $paginador['licencias'];
			$numeroDePaginas = $paginador['numeroDePaginas'];
			$persona = $paginador['persona'];
		}
		return $this->renderHTML($ruta, [
			'licencias' => $licencias,
			'categoriaslicencias' => $categoriaslicencias,
			'responseMessage' => $responseMessage,
			'numeroDePaginas' => $numeroDePaginas,
			'idPer' => $idPer,
			'persona' => $persona
		]);
	}

	//en esta accion se registra las modificaciones del registro utiliza metodo post no get
	public function postUpdateLicencias($request){
		$responseMessage = null; $registrationErrorMessage=null; $licencias=null; $numeroDePaginas=null; $persona=null;
		$ruta='personaLicenciasList.twig';

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();

			/*En la variable $files se almacena el $request del file y en $fileComprobante se
			*almacena el array con todas las propiedades de este file*/
			$files = $request->getUploadedFiles();
			if ($files) {
				$fileComprobante = $files['urlcomprobante'];
				$temporaryFileName = 'licp'.$postData['numero'].$postData['catid'];
				
				/*Se hace llamado al metodo que se creo para validar las imagenes */
				$FilesValidatorController = new FilesValidatorController();
				$validadorComprobante = $FilesValidatorController->filesValidator($fileComprobante, $temporaryFileName);
			}else{
				$validadorComprobante['error']=1;
				$validadorComprobante['message']="Error, el comprobante no puede pesar mas de 2MB, seleccione nuevamente la persona y agregué un comprobante valido";
				$ruta='personasList.twig';
			}
			

			$idPer = $postData['idPer'] ?? null;

			$documentoValidator = v::key('numero', v::stringType()->length(1, 50)->notEmpty())
					->key('fechaexpedicion', v::date())
					->key('fechavencimiento', v::date())
					->key('catid', v::numeric()->positive()->notEmpty());

			
			if($_SESSION['userId']){

				//Si no ocurrio error en la validacion del file 
				if ($validadorComprobante['error'] == 0 or $validadorComprobante['error'] == 4) {
					$fileName = $validadorComprobante['fileName'];
					try{
						$documentoValidator->assert($postData);
						$postData = $request->getParsedBody();

						//la siguiente linea hace una consulta en la DB y trae el registro where id=$id y lo guarda en documento y posteriormente remplaza los valores y con el ->save() guarda la modificacion en la DB
						$idLicencia = $postData['id'];
						$licencia = PersonaLicencias::find($idLicencia);
						
						$licencia->numero=$postData['numero'];
						$licencia->fechaexpedicion = $postData['fechaexpedicion'];
						$licencia->fechavencimiento = $postData['fechavencimiento'];
						if ($validadorComprobante['error'] == 0) {
							$fileComprobante->moveTo("uploads/$fileName");	
							$licencia->urlcomprobante = $fileName;
						}
						$licencia->catid = $postData['catid'];
						$licencia->iduserupdate = $_SESSION['userId'];
						$licencia->save();

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
			$licencias = $paginador['licencias'];
			$numeroDePaginas = $paginador['numeroDePaginas'];
		}
		

		return $this->renderHTML($ruta,[
				'idPer' => $idPer,
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'licencias' => $licencias,
				'numeroDePaginas' => $numeroDePaginas,
				'persona' => $persona
		]);
	}
}

?>
