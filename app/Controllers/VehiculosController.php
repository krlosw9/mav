<?php 

namespace App\Controllers;

use App\Models\{Vehiculos,VehiculoTiposVinculacion,VehiculoMarcas,VehiculoLineas, VehiculoColores, VehiculoServicios, VehiculoClases, VehiculoCarrocerias, VehiculoCombustibles, VehiculoOrganimosTransito};
use App\Controllers\{VehiculoDocumentosController, VehiculoPropietarioController};
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Zend\Diactoros\Response\RedirectResponse;

class VehiculosController extends BaseController{
	
	//estos dos valores son los que se cambian, para modificar la cantidad de registros listados por pagina y el maximo numero en paginacion
	private $articulosPorPagina=10;
	private $limitePaginacion=20;

	public function getAddVehiculos(){
		$tiposvinculacion=null; $marcas=null; $lineas=null; $colores=null; $servicios=null; $clases=null; $carrocerias=null;
		$combustibles=null; $organimostransito=null;
		
		$tiposvinculacion = VehiculoTiposVinculacion::orderBy('nombre')->get();
		$marcas = VehiculoMarcas::orderBy('nombre')->get();
		$lineas = VehiculoLineas::orderBy('nombre')->get();
		$colores = VehiculoColores::orderBy('nombre')->get();
		$servicios = VehiculoServicios::orderBy('nombre')->get();
		$clases = VehiculoClases::orderBy('nombre')->get();
		$carrocerias = VehiculoCarrocerias::orderBy('nombre')->get();
		$combustibles = VehiculoCombustibles::orderBy('nombre')->get();
		$organimostransito = VehiculoOrganimosTransito::orderBy('nombre')->get();
		$maximumYearModel = Date('Y'); 
		$maximumYearModel++;

		return $this->renderHTML('vehiculosAdd.twig',[
				'tiposvinculacion' => $tiposvinculacion,
				'marcas' => $marcas,
				'lineas' => $lineas,
				'colores' => $colores,
				'servicios' => $servicios,
				'clases' => $clases,
				'carrocerias' => $carrocerias,
				'combustibles' => $combustibles,
				'organimostransito' => $organimostransito,
				'maximumYearModel' => $maximumYearModel
		]);
	}

	//Registra la Persona
	public function postAddVehiculos($request){
		$responseMessage = null; $prevMessage=null; $registrationErrorMessage=null;
		$query = null; $numeroDePaginas=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			
			$personaValidator = v::key('placa', v::stringType()->length(1, 6)->notEmpty())
					->key('km', v::numeric()->length(1, 20)->notEmpty())
					->key('tipvinculacionid', v::numeric()->positive()->notEmpty())
					->key('licenciatransito', v::stringType()->length(1, 50)->notEmpty())
					->key('capacidad', v::numeric()->positive()->length(1, 2)->notEmpty())
					->key('marcid', v::numeric()->positive()->notEmpty())
					->key('linea', v::numeric()->positive()->notEmpty())
					->key('modelo', v::numeric()->positive()->length(1, 4)->notEmpty())
					->key('colorid', v::numeric()->positive()->notEmpty())
					->key('servid', v::numeric()->positive()->notEmpty())
					->key('clasid', v::numeric()->positive()->notEmpty())
					->key('carroceria', v::numeric()->positive()->notEmpty())
					->key('combustibleid', v::numeric()->positive()->notEmpty())
					->key('puertas', v::numeric()->positive()->length(1, 1)->notEmpty())
					->key('numchasis', v::stringType()->length(1, 50)->notEmpty())
					->key('fechamatricula', v::date())
					->key('fechaexpedicion', v::date())
					->key('orgtransitoid', v::numeric()->positive()->notEmpty());
			
			
			if($_SESSION['userId']){
				try{
					$personaValidator->assert($postData);
					$postData = $request->getParsedBody();

					$vehiculo = new Vehiculos();
					$vehiculo->placa=$postData['placa'];
					$vehiculo->interno = $postData['interno'];
					$vehiculo->km = $postData['km'];
					$vehiculo->tipvinculacionid = $postData['tipvinculacionid'];
					$vehiculo->licenciatransito = $postData['licenciatransito'];
					$vehiculo->capacidad = $postData['capacidad'];
					$vehiculo->marcid = $postData['marcid'];
					$vehiculo->linea = $postData['linea'];
					$vehiculo->modelo = $postData['modelo'];
					$vehiculo->cilindraje = $postData['cilindraje'];
					$vehiculo->potencia = $postData['potencia'];
					$vehiculo->colorid = $postData['colorid'];
					$vehiculo->servid = $postData['servid'];
					$vehiculo->clasid = $postData['clasid'];
					$vehiculo->carroceria = $postData['carroceria'];
					$vehiculo->combustibleid = $postData['combustibleid'];
					$vehiculo->puertas = $postData['puertas'];
					$vehiculo->nummotor = $postData['nummotor'];
					$vehiculo->vin = $postData['vin'];
					$vehiculo->numserie = $postData['numserie'];
					$vehiculo->numchasis = $postData['numchasis'];
					$vehiculo->fechamatricula = $postData['fechamatricula'];
					$vehiculo->fechaexpedicion = $postData['fechaexpedicion'];
					$vehiculo->orgtransitoid = $postData['orgtransitoid'];
					$vehiculo->iduserregister = $_SESSION['userId'];
					$vehiculo->iduserupdate = $_SESSION['userId'];
					$vehiculo->save();

					$responseMessage = 'Registrado';
				}catch(\Exception $exception){
					$prevMessage = substr($exception->getMessage(), 0, 25);

					if ($prevMessage == "SQLSTATE[23505]: Unique v") {
						$responseMessage = 'Error, El numero interno, licencia de transito, numero de chasis, numero de motor, numero de serie, placa o vin deben ser unicos y alguno ya esta registrado';
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
			$query=$paginador['query'];
		}

		return $this->renderHTML('vehiculosList.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'numeroDePaginas' => $numeroDePaginas,
				'query' => $query
		]);
	}

	//Lista todas los modelos Ordenando por posicion
	public function getListVehiculos(){
		$responseMessage = null; $query=null; $numeroDePaginas=null;

		$paginaActual = $_GET['pag'] ?? null;		
		$paginador = $this->paginador($paginaActual);
		$numeroDePaginas=$paginador['numeroDePaginas'];
		$query=$paginador['query'];

		return $this->renderHTML('vehiculosList.twig', [
			'query' => $query,
			'numeroDePaginas' => $numeroDePaginas,
			'paginaActual' => $paginaActual
		]);
		
	}

	public function paginador($paginaActual=null){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $query=null;
		
		$numeroDeFilas = Vehiculos::selectRaw('count(*) as query_count')
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

		$query = Vehiculos::Join("vehiculo.tiposvinculacion","vehiculo.vehiculos.tipvinculacionid","=","vehiculo.tiposvinculacion.id")
		->Join("vehiculo.servicios","vehiculo.vehiculos.servid","=","vehiculo.servicios.id")
		->select('vehiculo.vehiculos.*', 'vehiculo.tiposvinculacion.nombre As vinculacion', 'vehiculo.servicios.nombre As servicio')
		->latest('id')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'query' => $query

		];

		return $retorno;
	}

	public function paginadorWhere($paginaActual=null, $criterio=null, $comparador='=', $textBuscar=null, $orden='latest', $criterioOrden='id'){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $query=null;
		
		$numeroDeFilas = Vehiculos::where($criterio, $comparador ,$textBuscar)->selectRaw('count(*) as query_count')
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

		$query = Vehiculos::Join("vehiculo.tiposvinculacion","vehiculo.vehiculos.tipvinculacionid","=","vehiculo.tiposvinculacion.id")
		->Join("vehiculo.servicios","vehiculo.vehiculos.servid","=","vehiculo.servicios.id")
		->where($criterio,$comparador,$textBuscar)
		->select('vehiculo.vehiculos.*', 'vehiculo.tiposvinculacion.nombre As vinculacion', 'vehiculo.servicios.nombre As servicio')
		->$orden($criterioOrden)
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();
		
		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'query' => $query

		];
		
		return $retorno;
	}


	public function postBusquedaVehiculos($request){
		$prevMessage = null; $responseMessage=null; $iniciar=0; $query=null; $queryErrorMessage=null;
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
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 6)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();
						
						$criterioQuery="vehiculo.vehiculos.placa"; $comparador='ilike';
						//$textBuscarModificado='%'.$textBuscar.'%';
						$textBuscarModificado=$textBuscar;
						$paginador = $this->paginadorWhere($paginaActual, $criterioQuery,$comparador, $textBuscarModificado);
						$query=$paginador['query'];
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
							'stringType' => '- Solo puede contener la placa del vehículo' 
							]);
						}else{
							$responseMessage = $prevMessage;
						}
					}
				}elseif ($criterio==2) {
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 5)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();

						$criterioQuery="vehiculo.vehiculos.interno"; $comparador='ilike'; $orden='orderBy';
						//$textBuscarModificado='%'.$textBuscar.'%';
						$textBuscarModificado=$textBuscar;
						$paginador = $this->paginadorWhere($paginaActual, $criterioQuery, $comparador, $textBuscarModificado,$orden, $criterioQuery);
						$query=$paginador['query'];
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
							'stringType' => '- Solo puede contener el numero interno del vehículo'
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

						$criterioQuery="vehiculo.vehiculos.licenciatransito"; $comparador='ilike'; $orden='orderBy';
						//$textBuscarModificado='%'.$textBuscar.'%';
						$textBuscarModificado=$textBuscar;
						$paginador = $this->paginadorWhere($paginaActual, $criterioQuery, $comparador, $textBuscarModificado, $orden, $criterioQuery);
						$query=$paginador['query'];
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
							'stringType' => '- Solo puede contener la licencia de transito del vehículo'
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
	
		return $this->renderHTML('vehiculosList.twig', [
			'numeroDePaginasBusqueda' => $numeroDePaginas,
			'query' => $query,
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
	public function postUpdDelVehiculos($request){
		$tiposvinculacion = null; $marcas=null; $vehiculos=null; $numeroDePaginas=null; $id=null; $boton=null;
		$quiereActualizar = false; $ruta='vehiculosList.twig'; $responseMessage = null; $query=null;
		$lineas=null; $colores=null; $servicios=null; $clases=null; $carrocerias=null; $combustibles=null;
		$organimostransito=null; $maximumYearModel=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$btnDelUpd = $postData['btnDelUpd'] ?? null;
			$btnDocumentos = $postData['btnDocumentos'] ?? null;			

			/*En este if verifica que boton se presiono si el de documentos o licencia y crea una instancia de la clase que corresponde, ejemplo si presiono documentos crea una instancia de la clase PersonaDocumentosController y llama al metodo listPersonasDocumentos(parametro el ID de la persona)*/
			if ($btnDocumentos) {
				$id = $postData['id'] ?? null;				
				if ($id) {
					if ($btnDocumentos == 'doc') {
						$DocumentosController = new VehiculoDocumentosController();
						return $DocumentosController->listVehiculosDocumentos($id);

					}elseif ($btnDocumentos == 'pro') {
						$LicenciasController = new VehiculoPropietarioController();
						return $LicenciasController->listVehiculosPropietario($id);
					}elseif ($btnDocumentos == 'cond') {
						$LicenciasController = new VehiculoPropietarioController();
						return $LicenciasController->listVehiculosPropietario($id);
					}else{
						$responseMessage = 'Opcion no definida, btn: '.$btnDocumentos;
					}
				}else{
					$responseMessage = 'Debe Seleccionar un vehiculo';
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
					$vehicle = new Vehiculos();
					$vehicle->destroy($id);
					$responseMessage = "Se elimino el vehículo";
				  }catch(\Exception $e){
				  	//$responseMessage = $e->getMessage();
				  	$prevMessage = substr($e->getMessage(), 0, 38);
					if ($prevMessage =="SQLSTATE[23503]: Foreign key violation") {
						$responseMessage = 'Error, No se puede eliminar, este vehículo esta en uso.';
					}else{
						$responseMessage= 'Error, No se puede eliminar, '.$prevMessage;
					}
				  }
				}elseif ($boton == 'upd') {
					$quiereActualizar=true;
				}
			}else{
				$responseMessage = 'Debe Seleccionar un vehículo';
			}
		}
		
		if ($quiereActualizar){
			//si quiere actualizar hace una consulta where id=$id y la envia por el array del renderHtml
			$vehiculos = Vehiculos::find($id);

			$tiposvinculacion = VehiculoTiposVinculacion::orderBy('nombre')->get();
			$marcas = VehiculoMarcas::orderBy('nombre')->get();
			$lineas = VehiculoLineas::orderBy('nombre')->get();
			$colores = VehiculoColores::orderBy('nombre')->get();
			$servicios = VehiculoServicios::orderBy('nombre')->get();
			$clases = VehiculoClases::orderBy('nombre')->get();
			$carrocerias = VehiculoCarrocerias::orderBy('nombre')->get();
			$combustibles = VehiculoCombustibles::orderBy('nombre')->get();
			$organimostransito = VehiculoOrganimosTransito::orderBy('nombre')->get();
			$maximumYearModel = Date('Y'); 
			$maximumYearModel++;
			$ruta='vehiculosUpdate.twig';
		}else{
			$paginador = $this->paginador();
			$numeroDePaginas=$paginador['numeroDePaginas'];
			$query=$paginador['query'];
		}
		return $this->renderHTML($ruta, [
			'numeroDePaginas' => $numeroDePaginas,
			'query' => $query,
			'vehiculos' => $vehiculos,
			'responseMessage' => $responseMessage,
			'tiposvinculacion' => $tiposvinculacion,
			'marcas' => $marcas,
			'lineas' => $lineas,
			'colores' => $colores,
			'servicios' => $servicios,
			'clases' => $clases,
			'carrocerias' => $carrocerias,
			'combustibles' => $combustibles,
			'organimostransito' => $organimostransito,
			'maximumYearModel' => $maximumYearModel
		]);
	}

	//en esta accion se registra las modificaciones del registro utiliza metodo post no get
	public function postUpdateVehiculos($request){
		$responseMessage = null; $registrationErrorMessage=null; $query=null; $numeroDePaginas=null;
				
		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();

			$personaValidator = v::key('placa', v::stringType()->length(1, 6)->notEmpty())
					->key('km', v::numeric()->length(1, 20)->notEmpty())
					->key('tipvinculacionid', v::numeric()->positive()->notEmpty())
					->key('licenciatransito', v::stringType()->length(1, 50)->notEmpty())
					->key('capacidad', v::numeric()->positive()->length(1, 2)->notEmpty())
					->key('marcid', v::numeric()->positive()->notEmpty())
					->key('linea', v::numeric()->positive()->notEmpty())
					->key('modelo', v::numeric()->positive()->length(1, 4)->notEmpty())
					->key('colorid', v::numeric()->positive()->notEmpty())
					->key('servid', v::numeric()->positive()->notEmpty())
					->key('clasid', v::numeric()->positive()->notEmpty())
					->key('carroceria', v::numeric()->positive()->notEmpty())
					->key('combustibleid', v::numeric()->positive()->notEmpty())
					->key('puertas', v::numeric()->positive()->length(1, 1)->notEmpty())
					->key('numchasis', v::stringType()->length(1, 50)->notEmpty())
					->key('fechamatricula', v::date())
					->key('fechaexpedicion', v::date())
					->key('orgtransitoid', v::numeric()->positive()->notEmpty());

			
			if($_SESSION['userId']){
				try{
					$personaValidator->assert($postData);
					$postData = $request->getParsedBody();

					//la siguiente linea hace una consulta en la DB y trae el registro where id=$id y lo guarda en persona y posteriormente remplaza los valores y con el ->save() guarda la modificacion en la DB
					$id = $postData['id'];
					$vehiculo = Vehiculos::find($id);
					
					$vehiculo->placa=$postData['placa'];
					$vehiculo->interno = $postData['interno'];
					$vehiculo->km = $postData['km'];
					$vehiculo->tipvinculacionid = $postData['tipvinculacionid'];
					$vehiculo->licenciatransito = $postData['licenciatransito'];
					$vehiculo->capacidad = $postData['capacidad'];
					$vehiculo->marcid = $postData['marcid'];
					$vehiculo->linea = $postData['linea'];
					$vehiculo->modelo = $postData['modelo'];
					$vehiculo->cilindraje = $postData['cilindraje'];
					$vehiculo->potencia = $postData['potencia'];
					$vehiculo->colorid = $postData['colorid'];
					$vehiculo->servid = $postData['servid'];
					$vehiculo->clasid = $postData['clasid'];
					$vehiculo->carroceria = $postData['carroceria'];
					$vehiculo->combustibleid = $postData['combustibleid'];
					$vehiculo->puertas = $postData['puertas'];
					$vehiculo->nummotor = $postData['nummotor'];
					$vehiculo->vin = $postData['vin'];
					$vehiculo->numserie = $postData['numserie'];
					$vehiculo->numchasis = $postData['numchasis'];
					$vehiculo->fechamatricula = $postData['fechamatricula'];
					$vehiculo->fechaexpedicion = $postData['fechaexpedicion'];
					$vehiculo->orgtransitoid = $postData['orgtransitoid'];
					$vehiculo->iduserupdate = $_SESSION['userId'];
					$vehiculo->save();

					$responseMessage = 'Editado.';
				}catch(\Exception $exception){
					$prevMessage = substr($exception->getMessage(), 0, 25);

					if ($prevMessage == "SQLSTATE[23505]: Unique v") {
						$responseMessage = 'Error, El numero interno, licencia de transito, numero de chasis, numero de motor, numero de serie, placa o vin deben ser unicos y alguno ya esta registrado';
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
			$query=$paginador['query'];
		}

		return $this->renderHTML('vehiculosList.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'numeroDePaginas' => $numeroDePaginas,
				'query' => $query
		]);
	}
}

?>
