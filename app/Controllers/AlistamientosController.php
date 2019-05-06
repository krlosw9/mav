<?php 

namespace App\Controllers;

use App\Models\{Personas, Vehiculos, Alistamientos, AlistamientoGruposAlistamiento, AlistamientosInformacionAlistamiento, AlistamientosTiposAlistamiento};
use App\Controllers\{DocumentosController};
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Zend\Diactoros\Response\RedirectResponse;

class AlistamientosController extends BaseController{
	
	//estos dos valores son los que se cambian, para modificar la cantidad de registros listados por pagina y el maximo numero en paginacion
	private $articulosPorPagina=10;
	private $limitePaginacion=20;
	private $maximoPuntosMalos=10;

	public function getAddAlistamientos(){
		$gruposalistamiento = null; $tiposalistamiento=null; $vehiculos=null; $conductores=null; $responsables=null; $fechaHoy=null;

		$gruposalistamiento = AlistamientoGruposAlistamiento::orderBy('id')->get();
		$tiposalistamiento = AlistamientosTiposAlistamiento::orderBy('gaid')->get();
		$vehiculos = Vehiculos::orderBy('placa')->get();
		$conductores = Personas::where("persona.personas.rolid",">=",4)->orderBy('nombre')->get();
		$responsables = Personas::where("persona.personas.rolid","=",3)->orderBy('nombre')->get();


		$fechaHoy= date("Y-m-d");

		return $this->renderHTML('alistamientosAdd.twig',[
				'gruposalistamiento' => $gruposalistamiento,
				'tiposalistamiento' => $tiposalistamiento,
				'fechaHoy' => $fechaHoy,
				'vehiculos' => $vehiculos,
				'conductores' => $conductores,
				'responsables' => $responsables,
		]);
	}

	public function getAddAlistamientos2(){
		$gruposalistamiento = null; $tiposalistamiento=null;
		$gruposalistamiento = AlistamientoGruposAlistamiento::orderBy('id')->get();
		$tiposalistamiento = AlistamientosTiposAlistamiento::orderBy('gaid')->get();

		return $this->renderHTML('alistamientosAdd2.twig',[
				'gruposalistamiento' => $gruposalistamiento,
				'tiposalistamiento' => $tiposalistamiento
		]);
	}

	//Registra el Alistamientos
	public function postAddAlistamientos($request){
		$responseMessage = null; $prevMessage=null; $registrationErrorMessage=null;
		$alistamientos = null; $numeroDePaginas=null; $puntosMalos=0; $ruta='alistamientosList.twig';
		$infoAlistamientoRegistrado=null; $alistamientosRegistrados=null; $gruposalistamiento=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			
			$personaValidator = v::key('fecha', v::date())
					->key('kilometraje', v::numeric()->positive()->notEmpty())
					->key('vehplacaid', v::numeric()->positive()->length(1, 15)->notEmpty())
					->key('perinspectorid', v::numeric()->positive()->notEmpty())
					->key('perconductorid', v::numeric()->positive()->notEmpty());
			
			
			if($_SESSION['userId']){
				try{
					$personaValidator->assert($postData);
					$postData = $request->getParsedBody();

					$calificacion = 'APROBADO';
					/* Consulta el ultimo id de InfoAlistamiento */
					$queryInfoAlis = AlistamientosInformacionAlistamiento::all();
					$ultimoInfoAlis = $queryInfoAlis->last();
					$ultimoIdInfoAlis = $ultimoInfoAlis->id+1;

					$infoAlistamiento = new AlistamientosInformacionAlistamiento();
					$infoAlistamiento->id=$ultimoIdInfoAlis;
					$infoAlistamiento->fecha=$postData['fecha'];
					$infoAlistamiento->kilometraje = $postData['kilometraje'];
					$infoAlistamiento->calificacion = $calificacion;
					$infoAlistamiento->observaciongeneral = $postData['observaciongeneral'];
					$infoAlistamiento->vehplacaid = $postData['vehplacaid'];
					$infoAlistamiento->perinspectorid = $postData['perinspectorid'];
					$infoAlistamiento->perconductorid = $postData['perconductorid'];
					$infoAlistamiento->iduserregister = $_SESSION['userId'];
					$infoAlistamiento->iduserupdate = $_SESSION['userId'];
					$infoAlistamiento->save();

					$arrayIdTipoAlistamiento = $postData['taid'] ?? null;
					foreach ($arrayIdTipoAlistamiento as $idTipoAlis) {
						$calificacionTipoAlis = $postData['calificacionTipoAlis'.$idTipoAlis] ?? null;
						$postCheck = $postData['check'.$idTipoAlis] ?? null;

						if ($postCheck == 'on') {
							$ccheck = 1;
						}else{
							$ccheck = 0;
							$puntosMalos+=$calificacionTipoAlis; 
						}
						
						//Registro del Alistamiento
						$alistamiento = new Alistamientos();
						$alistamiento->ccheck=$ccheck;
						$alistamiento->observacion = $postData['obs'.$idTipoAlis];
						$alistamiento->taid = $idTipoAlis;
						$alistamiento->infoalisid = $ultimoIdInfoAlis;
						$alistamiento->iduserregister = $_SESSION['userId'];
						$alistamiento->iduserupdate = $_SESSION['userId'];
						$alistamiento->save();		
					}

					if ($puntosMalos >= $this->maximoPuntosMalos) {
						$infoAlistamientoUpd = AlistamientosInformacionAlistamiento::find($ultimoIdInfoAlis);
						$infoAlistamientoUpd->calificacion = 'RECHAZADO';
						$infoAlistamientoUpd->save();	
					}
					
					$infoAlistamientoRegistrado = AlistamientosInformacionAlistamiento::Join("vehiculo.vehiculos","alistamiento.informacionalistamiento.vehplacaid","=","vehiculo.vehiculos.id")
					->Join("persona.personas","alistamiento.informacionalistamiento.perinspectorid","=","persona.personas.id")
					->Join("persona.personas","alistamiento.informacionalistamiento.perconductorid","=","persona.personas.id")
					->select('alistamiento.informacionalistamiento.*', 'persona.personas.nombre', 'vehiculo.vehiculos.placa')
					->find($ultimoIdInfoAlis);

					$alistamientosRegistrados=Alistamientos::Join("alistamiento.tiposalistamiento","alistamiento.alistamientos.taid","=","alistamiento.tiposalistamiento.id")
					->where("alistamiento.alistamientos.infoalisid","=",$ultimoIdInfoAlis)->orderBy('alistamiento.alistamientos.id')
					->select('alistamiento.alistamientos.*', 'alistamiento.tiposalistamiento.nombre', 'alistamiento.tiposalistamiento.gaid')
					->get();

					$gruposalistamiento = AlistamientoGruposAlistamiento::orderBy('id')->get();

					$ruta='alistamientoPrint.twig';
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
						'date' => '- Formato de fecha no valido',
						'numeric' => '- Solo puede contener numeros', 
						'positive' => '- Solo puede contener numeros mayores a cero'
						]);
					}else{
						$responseMessage = $prevMessage;
					}
				}
			}
		}
		
		if ($responseMessage=='Registrado') {
			$paginador = $this->paginador();
			$numeroDePaginas=$paginador['numeroDePaginas'];
			$alistamientos=$paginador['alistamientos'];
		}

		return $this->renderHTML($ruta,[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'numeroDePaginas' => $numeroDePaginas,
				'alistamientos' => $alistamientos,
				'infoAlistamientoRegistrado' => $infoAlistamientoRegistrado,
				'alistamientosRegistrados' => $alistamientosRegistrados,
				'gruposalistamiento' => $gruposalistamiento
		]);
	}

	//Lista todas los modelos Ordenando por posicion
	public function getListAlistamientos(){
		$responseMessage = null; $alistamientos=null; $numeroDePaginas=null;

		$paginaActual = $_GET['pag'] ?? null;		
		$paginador = $this->paginador($paginaActual);
		$numeroDePaginas=$paginador['numeroDePaginas'];
		$alistamientos=$paginador['alistamientos'];

		return $this->renderHTML('alistamientosList.twig', [
			'alistamientos' => $alistamientos,
			'numeroDePaginas' => $numeroDePaginas,
			'paginaActual' => $paginaActual
		]);
		
	}

	public function paginador($paginaActual=null){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $alistamientos=null;
		
		$numeroDeFilas = AlistamientosInformacionAlistamiento::selectRaw('count(*) as query_count')
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

		$alistamientos = AlistamientosInformacionAlistamiento::Join("vehiculo.vehiculos","alistamiento.informacionalistamiento.vehplacaid","=","vehiculo.vehiculos.id")
		->Join("persona.personas","alistamiento.informacionalistamiento.perconductorid","=","persona.personas.id")
		->select('alistamiento.informacionalistamiento.*', 'vehiculo.vehiculos.placa', 'persona.personas.nombre')
		->latest('id')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'alistamientos' => $alistamientos

		];

		return $retorno;
	}

	public function paginadorWhere($paginaActual=null, $criterio=null, $comparador='=', $textBuscar=null, $orden='latest', $criterioOrden='id'){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $alistamientos=null;
		
		$numeroDeFilas = Alistamientos::where($criterio, $comparador ,$textBuscar)->selectRaw('count(*) as query_count')
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

		$alistamientos = Personas::Join("persona.tiposdocumento","persona.personas.tipodocumentoid","=","persona.tiposdocumento.id")
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
			'alistamientos' => $alistamientos

		];
		
		return $retorno;
	}


	public function postBusquedaAlistamientos($request){
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
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 50)->notEmpty());
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
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 50)->notEmpty());
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
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 50)->notEmpty());
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
	
		return $this->renderHTML('alistamientosList.twig', [
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
	public function postUpdDelAlistamientos($request){
		$roles = null; $tiposdocumentos=null; $personas=null; $numeroDePaginas=null; $id=null; $boton=null;
		$quiereActualizar = false; $ruta='alistamientosList.twig'; $responseMessage = null;
		$generos=null; $estadocivil=null; $rh=null; $niveleducativo=null;
		$mensajeNoPermisos='Su rol no tiene permisos para realizar esta funcion';

		$sessionUserPermission = $_SESSION['userLicense'] ?? null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$btnDelUpd = $postData['btnDelUpd'] ?? null;
			$btnDocumentos = $postData['btnDocumentos'] ?? null;			

			/*En este if verifica que boton se presiono si el de documentos o licencia y crea una instancia de la clase que corresponde, ejemplo si presiono documentos crea una instancia de la clase PersonaDocumentosController y llama al metodo listPersonasDocumentos(parametro el ID de la persona)*/
			if ($btnDocumentos) {
				$id = $postData['id'] ?? null;				
				if ($id) {
					if ($btnDocumentos == 'doc') {
					  if (in_array('documentslist', $sessionUserPermission)) {
						$DocumentosController = new PersonaDocumentosController();
						return $DocumentosController->listPersonasDocumentos($id);
					  }else{
						$responseMessage=$mensajeNoPermisos;
					  }
					}elseif ($btnDocumentos == 'lic') {
					  if (in_array('licenselist', $sessionUserPermission)) {
						$LicenciasController = new PersonaLicenciasController();
						return $LicenciasController->listPersonasLicencias($id);
					  }else{
					  	$responseMessage=$mensajeNoPermisos;
					  }
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
				 if (in_array('checkdel', $sessionUserPermission)) {
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
				 	$responseMessage=$mensajeNoPermisos;
				 }
				}elseif ($boton == 'upd') {
				  if (in_array('checkupdate', $sessionUserPermission)) {
					$quiereActualizar=true;
				  }else{
				  	$responseMessage=$mensajeNoPermisos;
				  }
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
			$ruta='alistamientosUpdate.twig';
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
	public function postUpdateAlistamientos($request){
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

		return $this->renderHTML('alistamientosList.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'numeroDePaginas' => $numeroDePaginas,
				'personas' => $personas
		]);
	}
}

?>
