<?php 

namespace App\Controllers;

use App\Models\{MantenimientoOperadores, PersonaTiposDocumento};
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Zend\Diactoros\Response\RedirectResponse;

class MantenimientoOperadoresController extends BaseController{
	
	//estos dos valores son los que se cambian, para modificar la cantidad de registros listados por pagina y el maximo numero en paginacion
	private $articulosPorPagina=10;
	private $limitePaginacion=20;

	public function getAddOperadores(){
		$tiposdocumentos=null;

		$tiposdocumentos = PersonaTiposDocumento::orderBy('nombre')->get();
		

		return $this->renderHTML('mantenimientoOperadoresAdd.twig',[
				'tiposdocumentos' => $tiposdocumentos
		]);
	}

	//Registra la Persona
	public function postAddOperadores($request){
		$responseMessage = null; $prevMessage=null; $registrationErrorMessage=null;
		$operadores = null; $numeroDePaginas=null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			
			$personaValidator = v::key('nombre', v::stringType()->length(1, 35)->notEmpty())
					->key('tipodocumentoid', v::numeric()->positive()->notEmpty())
					->key('numerodocumento', v::numeric()->positive()->length(1, 15)->notEmpty())
					->key('direccion', v::stringType()->length(1, 35)->notEmpty())
					->key('telefono', v::numeric()->positive()->length(1, 15)->notEmpty());
			
			
			if($_SESSION['userId']){
				try{
					$personaValidator->assert($postData);
					$postData = $request->getParsedBody();

					$operador = new MantenimientoOperadores();
					$operador->numerodocumento=$postData['numerodocumento'];
					$operador->tipodocumentoid = $postData['tipodocumentoid'];
					$operador->nombre = $postData['nombre'];
					$operador->direccion = $postData['direccion'];
					$operador->correo = $postData['correo'];
					$operador->telefono = $postData['telefono'];
					$operador->celular = $postData['celular'];
					$operador->iduserregister = $_SESSION['userId'];
					$operador->iduserupdate = $_SESSION['userId'];
					$operador->save();

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
			$operadores=$paginador['operadores'];
		}

		return $this->renderHTML('mantenimientoOperadoresList.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'numeroDePaginas' => $numeroDePaginas,
				'operadores' => $operadores
		]);
	}

	//Lista todas los modelos Ordenando por posicion
	public function getListOperadores(){
		$responseMessage = null; $operadores=null; $numeroDePaginas=null;

		$paginaActual = $_GET['pag'] ?? null;		
		$paginador = $this->paginador($paginaActual);
		$numeroDePaginas=$paginador['numeroDePaginas'];
		$operadores=$paginador['operadores'];

		return $this->renderHTML('mantenimientoOperadoresList.twig', [
			'operadores' => $operadores,
			'numeroDePaginas' => $numeroDePaginas,
			'paginaActual' => $paginaActual
		]);
		
	}

	public function paginador($paginaActual=null){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $operadores=null;
		
		$numeroDeFilas = MantenimientoOperadores::selectRaw('count(*) as query_count')
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

		$operadores = MantenimientoOperadores::orderBy('nombre')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'operadores' => $operadores

		];

		return $retorno;
	}

	public function paginadorWhere($paginaActual=null, $criterio=null, $comparador='=', $textBuscar=null, $orden='latest', $criterioOrden='id'){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $operadores=null;
		
		$numeroDeFilas = MantenimientoOperadores::where($criterio, $comparador ,$textBuscar)->selectRaw('count(*) as query_count')
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

		$operadores = MantenimientoOperadores::where($criterio,$comparador,$textBuscar)
		->$orden($criterioOrden)
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'operadores' => $operadores

		];
		
		return $retorno;
	}


	public function postBusquedaOperadores($request){
		$prevMessage = null; $responseMessage=null; $iniciar=0; $operadores=null; $queryErrorMessage=null;
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
						$textBuscarModificado='%'.$textBuscar.'%';
						//$textBuscarModificado=$textBuscar;
						$paginador = $this->paginadorWhere($paginaActual, $criterioQuery,$comparador, $textBuscarModificado);
						$operadores=$paginador['operadores'];
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

						$criterioQuery="nombre"; $comparador='ilike'; $orden='orderBy';
						$textBuscarModificado='%'.$textBuscar.'%';
						//$textBuscarModificado=$textBuscar;
						$paginador = $this->paginadorWhere($paginaActual, $criterioQuery, $comparador, $textBuscarModificado,$orden, $criterioQuery);
						$operadores=$paginador['operadores'];
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
							'stringType' => '- Solo puede contener nombres de talleres'
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
	
		return $this->renderHTML('mantenimientoOperadoresList.twig', [
			'numeroDePaginasBusqueda' => $numeroDePaginas,
			'operadores' => $operadores,
			'prevMessage' => $prevMessage,
			'responseMessage' => $responseMessage,
			'queryErrorMessage' => $queryErrorMessage,
			'paginaActual' => $paginaActual,
			'textBuscar' => $textBuscar,
			'criterio' => $criterio
		]);
		
	}


	public function postDelOperadores($request){
		$operadores=null; $numeroDePaginas=null; $id=null; $responseMessage = null;

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$id = $postData['btnDelUpd'] ?? null;
			
			if ($id) {
			  try{
				$people = new MantenimientoOperadores();
				$people->destroy($id);
				$responseMessage = "Se elimino el registro del taller";
			  }catch(\Exception $e){
			  	//$responseMessage = $e->getMessage();
			  	$prevMessage = substr($e->getMessage(), 0, 38);
				if ($prevMessage =="SQLSTATE[23503]: Foreign key violation") {
					$responseMessage = 'Error, No se puede eliminar, este taller esta en uso en la base de datos.';
				}else{
					$responseMessage= 'Error, No se puede eliminar, '.$prevMessage;
				}
			  }
			}else{
				$responseMessage = 'Debe Seleccionar un taller';
			}
		}

		$paginador = $this->paginador();
		$numeroDePaginas=$paginador['numeroDePaginas'];
		$operadores=$paginador['operadores'];
	
		return $this->renderHTML('mantenimientoOperadoresList.twig', [
			'numeroDePaginas' => $numeroDePaginas,
			'operadores' => $operadores,
			'responseMessage' => $responseMessage
		]);
	}


	public function postUpdOperadores($request){
		$tiposdocumentos=null; $operadores=null; $numeroDePaginas=null; $id=null; $responseMessage = null;
		$ruta='mantenimientoOperadoresUpdate.twig';

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			$id = $postData['btnDelUpd'] ?? null;
			
			if ($id) {
				$operadores = MantenimientoOperadores::find($id);
				
				$tiposdocumentos = PersonaTiposDocumento::orderBy('nombre')->get();
				
			}else{
				$ruta='mantenimientoOperadoresList.twig';

				$paginador = $this->paginador();
				$numeroDePaginas=$paginador['numeroDePaginas'];
				$operadores=$paginador['operadores'];

				$responseMessage = 'Debe Seleccionar un taller';
			}
		}
		
		
		return $this->renderHTML($ruta, [
			'numeroDePaginas' => $numeroDePaginas,
			'operadores' => $operadores,
			'tiposdocumentos' => $tiposdocumentos,
			'responseMessage' => $responseMessage
		]);
	}


	//en esta accion se registra las modificaciones del registro utiliza metodo post no get
	public function postUpdateOperadores($request){
		$responseMessage = null; $registrationErrorMessage=null; $operadores=null; $numeroDePaginas=null;
		$activoCheck=1;
		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();

			$personaValidator = v::key('nombre', v::stringType()->length(1, 35)->notEmpty())
					->key('tipodocumentoid', v::numeric()->positive()->notEmpty())
					->key('numerodocumento', v::numeric()->positive()->length(1, 15)->notEmpty())
					->key('direccion', v::stringType()->length(1, 35)->notEmpty())
					->key('telefono', v::numeric()->positive()->length(1, 15)->notEmpty());

			
			if($_SESSION['userId']){
				try{
					$personaValidator->assert($postData);
					$postData = $request->getParsedBody();
					
					//la siguiente linea hace una consulta en la DB y trae el registro where id=$id y lo guarda en persona y posteriormente remplaza los valores y con el ->save() guarda la modificacion en la DB
					$idOperador = $postData['id'];
					$operador = MantenimientoOperadores::find($idOperador);
					
					$operador->numerodocumento=$postData['numerodocumento'];
					$operador->tipodocumentoid = $postData['tipodocumentoid'];
					$operador->nombre = $postData['nombre'];
					$operador->direccion = $postData['direccion'];
					$operador->correo = $postData['correo'];
					$operador->telefono = $postData['telefono'];
					$operador->celular = $postData['celular'];
					$operador->iduserregister = $_SESSION['userId'];
					$operador->iduserupdate = $_SESSION['userId'];
					$operador->save();

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
			$operadores=$paginador['operadores'];
		}

		return $this->renderHTML('mantenimientoOperadoresList.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'numeroDePaginas' => $numeroDePaginas,
				'operadores' => $operadores
		]);
	}
}

?>
