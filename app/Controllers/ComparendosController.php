<?php 

namespace App\Controllers;

use App\Models\{Comparendos,Vehiculos, ComparendoTiposComparendo, ComparendoEstado};
use App\Controllers\{DocumentosController};
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Zend\Diactoros\Response\RedirectResponse;

class ComparendosController extends BaseController{
	
	//estos dos valores son los que se cambian, para modificar la cantidad de registros listados por pagina y el maximo numero en paginacion
	private $articulosPorPagina=10;
	private $limitePaginacion=20;

	public function getAddComparendos(){
		$tipos = null; $estados=null; $vehiculos=null;

		$tipos = ComparendoTiposComparendo::orderBy('nombre')->get();
		$estados = ComparendoEstado::orderBy('nombre')->get();
		$vehiculos = Vehiculos::orderBy('placa')->get();
		

		return $this->renderHTML('comparendosAdd.twig',[
				'tipos' => $tipos,
				'estados' => $estados,
				'vehiculos' => $vehiculos
		]);
	}

	//Registra la Persona
	public function postAddComparendos($request){
		$responseMessage = null; $prevMessage=null; $registrationErrorMessage=null;
		$comparendos = null; $numeroDePaginas=null; $vehiculos=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			
			$comparendoValidator = v::key('tipcomid', v::numeric()->positive()->notEmpty())
					->key('lugar', v::stringType()->length(1, 75)->notEmpty())
					->key('descripcion', v::stringType()->length(1, 255)->notEmpty())
					->key('idestado', v::numeric()->positive()->notEmpty())
					->key('vehid', v::numeric()->positive()->notEmpty())
					->key('fecha', v::date());
			
			
			if($_SESSION['userId']){
				try{
					$comparendoValidator->assert($postData);
					$postData = $request->getParsedBody();

					$comparendo = new Comparendos();
					$comparendo->tipcomid=$postData['tipcomid'];
					$comparendo->lugar = $postData['lugar'];
					$comparendo->descripcion = $postData['descripcion'];
					$comparendo->idestado = $postData['idestado'];
					$comparendo->vehid = $postData['vehid'];
					$comparendo->fecha = $postData['fecha'];
					$comparendo->iduserregister = $_SESSION['userId'];
					$comparendo->iduserupdate = $_SESSION['userId'];
					$comparendo->save();

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
			$comparendos=$paginador['comparendos'];
		}
		$vehiculos=$paginador['vehiculos'];

		return $this->renderHTML('comparendosList.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'numeroDePaginas' => $numeroDePaginas,
				'comparendos' => $comparendos,
				'vehiculos' => $vehiculos
		]);
	}

	//Lista todas los modelos Ordenando por posicion
	public function getListComparendos(){
		$responseMessage = null; $comparendos=null; $numeroDePaginas=null; $vehiculos=null;

		$paginaActual = $_GET['pag'] ?? null;		

		$paginador = $this->paginador($paginaActual);
		$numeroDePaginas=$paginador['numeroDePaginas'];
		$comparendos=$paginador['comparendos'];
		$vehiculos=$paginador['vehiculos'];

		return $this->renderHTML('comparendosList.twig', [
			'comparendos' => $comparendos,
			'numeroDePaginas' => $numeroDePaginas,
			'paginaActual' => $paginaActual,
			'vehiculos' => $vehiculos
		]);
		
	}

	public function paginador($paginaActual=null){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $comparendos=null; $vehiculos=null;
		
		$numeroDeFilas = Comparendos::selectRaw('count(*) as query_count')
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

		$comparendos = Comparendos::Join("comparendo.tiposcomparendo","comparendo.comparendos.tipcomid","=","comparendo.tiposcomparendo.id")
		->Join("comparendo.estado","comparendo.comparendos.idestado","=","comparendo.estado.id")
		->Join("vehiculo.vehiculos","comparendo.comparendos.vehid","=","vehiculo.vehiculos.id")
		->select('comparendo.comparendos.*', 'comparendo.tiposcomparendo.nombre As tipo', 'comparendo.estado.nombre As estado', 'vehiculo.vehiculos.placa As vehiculo')
		->latest('id')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		$vehiculos = Vehiculos::orderBy('placa')->get();

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'comparendos' => $comparendos,
			'vehiculos' => $vehiculos

		];

		return $retorno;
	}

	public function paginadorWhere($paginaActual=null, $criterio=null, $comparador='=', $textBuscar=null, $orden='latest', $criterioOrden='id'){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $comparendos=null; $vehiculos=null;
		
		$numeroDeFilas = Comparendos::where($criterio, $comparador ,$textBuscar)->selectRaw('count(*) as query_count')
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

		$comparendos = Comparendos::Join("comparendo.tiposcomparendo","comparendo.comparendos.tipcomid","=","comparendo.tiposcomparendo.id")
		->Join("comparendo.estado","comparendo.comparendos.idestado","=","comparendo.estado.id")
		->Join("vehiculo.vehiculos","comparendo.comparendos.vehid","=","vehiculo.vehiculos.id")
		->where($criterio,$comparador,$textBuscar)
		->select('comparendo.comparendos.*', 'comparendo.tiposcomparendo.nombre As tipo', 'comparendo.estado.nombre As estado', 'vehiculo.vehiculos.placa As vehiculo')
		->$orden($criterioOrden)
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		$vehiculos = Vehiculos::orderBy('placa')->get();

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'comparendos' => $comparendos,
			'vehiculos' => $vehiculos

		];
		
		return $retorno;
	}


	public function postBusquedaComparendos($request){
		$prevMessage = null; $responseMessage=null; $iniciar=0; $comparendos=null; $queryErrorMessage=null;
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
					$personaValidator = v::key('textBuscar', v::stringType()->length(1, 15)->notEmpty());
					try{
						$personaValidator->assert($postData);
						$postData = $request->getParsedBody();
						
						$criterioQuery="vehid"; $comparador='=';
						//$textBuscarModificado='%'.$textBuscar.'%';
						$textBuscarModificado=$textBuscar;
						$paginador = $this->paginadorWhere($paginaActual, $criterioQuery,$comparador, $textBuscarModificado);
						$comparendos=$paginador['comparendos'];
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
						$comparendos=$paginador['comparendos'];
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
						$comparendos=$paginador['comparendos'];
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
	
		return $this->renderHTML('comparendosList.twig', [
			'numeroDePaginasBusqueda' => $numeroDePaginas,
			'comparendos' => $comparendos,
			'prevMessage' => $prevMessage,
			'responseMessage' => $responseMessage,
			'queryErrorMessage' => $queryErrorMessage,
			'paginaActual' => $paginaActual,
			'textBuscar' => $textBuscar,
			'criterio' => $criterio,
			'vehiculos' => $vehiculos
		]);
		
	}


	public function postDelComparendos($request){
		$comparendos=null; $numeroDePaginas=null; $id=null; $responseMessage = null; $vehiculos=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$id = $postData['btnDelUpd'] ?? null;
			
			if ($id) {
			  try{
				$comparendo = new Comparendos();
				$comparendo->destroy($id);
				$responseMessage = "Se elimino el comparendo";
			  }catch(\Exception $e){
			  	//$responseMessage = $e->getMessage();
			  	$prevMessage = substr($e->getMessage(), 0, 38);
				if ($prevMessage =="SQLSTATE[23503]: Foreign key violation") {
					$responseMessage = 'Error, No se puede eliminar, este comparendo esta en uso.';
				}else{
					$responseMessage= 'Error, No se puede eliminar, '.$prevMessage;
				}
			  }
			}else{
				$responseMessage = 'Debe Seleccionar un comparendo';
			}
		}

		$paginador = $this->paginador();
		$numeroDePaginas=$paginador['numeroDePaginas'];
		$comparendos=$paginador['comparendos'];
		$vehiculos=$paginador['vehiculos'];
	
		return $this->renderHTML('comparendosList.twig', [
			'numeroDePaginas' => $numeroDePaginas,
			'comparendos' => $comparendos,
			'vehiculos' => $vehiculos,
			'responseMessage' => $responseMessage
		]);
	}


	public function postUpdComparendos($request){
		$tipos = null; $estados=null; $vehiculos=null; $numeroDePaginas=null; $id=null; $responseMessage = null;
		$ruta='comparendosUpdate.twig'; $comparendos=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$id = $postData['btnDelUpd'] ?? null;
			
			if ($id) {
				$comparendos = Comparendos::find($id);

				$tipos = ComparendoTiposComparendo::orderBy('nombre')->get();
				$estados = ComparendoEstado::orderBy('nombre')->get();
				$vehiculos = Vehiculos::orderBy('placa')->get();
			}else{
				$ruta='comparendosList.twig';

				$paginador = $this->paginador();
				$numeroDePaginas=$paginador['numeroDePaginas'];
				$comparendos=$paginador['comparendos'];
				$vehiculos=$paginador['vehiculos'];

				$responseMessage = 'Debe Seleccionar una persona';
			}
		}
		
		
		return $this->renderHTML($ruta, [
			'numeroDePaginas' => $numeroDePaginas,
			'comparendos' => $comparendos,
			'tipos' => $tipos,
			'estados' => $estados,
			'vehiculos' => $vehiculos,
			'responseMessage' => $responseMessage
		]);
	}


	//en esta accion se registra las modificaciones del registro utiliza metodo post no get
	public function postUpdateComparendos($request){
		$responseMessage = null; $registrationErrorMessage=null; $comparendos=null; $numeroDePaginas=null; $vehiculos=null;
		
		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();

			$comparendoValidator = v::key('tipcomid', v::numeric()->positive()->notEmpty())
					->key('lugar', v::stringType()->length(1, 75)->notEmpty())
					->key('descripcion', v::stringType()->length(1, 255)->notEmpty())
					->key('idestado', v::numeric()->positive()->notEmpty())
					->key('vehid', v::numeric()->positive()->notEmpty())
					->key('fecha', v::date());
			
			if($_SESSION['userId']){
				try{
					$comparendoValidator->assert($postData);
					$postData = $request->getParsedBody();
					
					//la siguiente linea hace una consulta en la DB y trae el registro where id=$id y lo guarda en comparendo y posteriormente remplaza los valores y con el ->save() guarda la modificacion en la DB
					$idComparendo = $postData['id'];
					$comparendo = Comparendos::find($idComparendo);
					
					$comparendo->tipcomid=$postData['tipcomid'];
					$comparendo->lugar = $postData['lugar'];
					$comparendo->descripcion = $postData['descripcion'];
					$comparendo->idestado = $postData['idestado'];
					$comparendo->vehid = $postData['vehid'];
					$comparendo->fecha = $postData['fecha'];
					$comparendo->iduserupdate = $_SESSION['userId'];
					$comparendo->save();

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
		if ($responseMessage=='Editado.') {
			$numeroDePaginas=$paginador['numeroDePaginas'];
			$comparendos=$paginador['comparendos'];
		}
		$vehiculos=$paginador['vehiculos'];

		return $this->renderHTML('comparendosList.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'numeroDePaginas' => $numeroDePaginas,
				'comparendos' => $comparendos,
				'vehiculos' => $vehiculos
		]);
	}
}

?>
