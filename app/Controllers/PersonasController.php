<?php 

namespace App\Controllers;

use App\Models\{Personas,Roles,TiposDocumento};
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Zend\Diactoros\Response\RedirectResponse;

class PersonasController extends BaseController{
	
	//estos dos valores son los que se cambian, para modificar la cantidad de registros listados por pagina y el maximo numero en paginacion
	private $articulosPorPagina=15;
	private $limitePaginacion=20;

	public function getAddPersonas(){
		$roles = null; $tiposdocumentos=null;

		$roles = Roles::where("id",">=",$_SESSION['userRolId'])->latest('id')->get();
		$tiposdocumentos = TiposDocumento::orderBy('nombre')->get();
		

		return $this->renderHTML('addPersonas.twig',[
				'roles' => $roles,
				'tiposdocumentos' => $tiposdocumentos
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
					->key('genero', v::stringType()->length(1, 10)->notEmpty())
					->key('estadocivil', v::stringType()->length(1, 12)->notEmpty())
					->key('fechanacimiento', v::date())
					->key('rh', v::stringType()->length(1, 5)->notEmpty())
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
					$persona->genero = $postData['genero'];
					$persona->estadocivil = $postData['estadocivil'];
					$persona->fechanacimiento = $postData['fechanacimiento'];
					$persona->rh = $postData['rh'];
					$persona->niveleducativo = $postData['niveleducativo'];
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
					$prevMessage = substr($exception->getMessage(), 0, 33);
					
					if ($prevMessage == "SQLSTATE[23505]: Unique violation") {
						$responseMessage = 'Error, El numero del documento o el correo ya esta registrado';
					}else{
						$registrationErrorMessage = $exception->findMessages([
						'notEmpty' => '- Los campos con (*) no pueden estar vacios',
						'length' => '- Tiene una longitud no permitida',
						'stringType' => '- Solo puede contener numeros y letras',
						'email' => '- Formato de correo no valido', 
						'date' => '- Formato de fecha no valido',
						'numeric' => '- Solo puede contener numeros', 
						'positive' => '- Solo puede contener numeros mayores a cero'
						]);
					}
				}
			}
		}
		$iniciar=0;
		$personas = Personas::Join("persona.tiposdocumento","persona.personas.tipodocumentoid","=","persona.tiposdocumento.id")
		->select('personas.*', 'persona.tiposdocumento.nombre As tiposdocumento')
		->orderBy('nombre')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		return $this->renderHTML('listPersonas.twig',[
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
		->select('personas.*', 'persona.tiposdocumento.nombre As tiposdocumento')
		->orderBy('nombre')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();
	
		return $this->renderHTML('listPersonas.twig', [
			'personas' => $personas,
			'numeroDePaginas' => $numeroDePaginas,
			'paginaActual' => $paginaActual
		]);
		
	}

	/*Al seleccionar uno de los dos botones (Eliminar o Actualizar) llega a esta accion y verifica cual de los dos botones oprimio si eligio el boton eliminar(del) elimina el registro de where $id Pero
	Si elige actualizar(upd) cambia la ruta del renderHTML y guarda una consulta de los datos del registro a modificar para mostrarlos en formulario de actualizacion llamado updateActOperario.twig y cuando modifica los datos y le da guardar a ese formulaio regresa a esta class y elige la accion getUpdateActivity()*/
	public function postUpdDelPersonas($request){
		$roles = null; $tiposdocumentos=null; $personas=null; $responseMessage = null;
		$quiereActualizar = false; $ruta='listPersonas.twig';

		if($request->getMethod()=='POST'){
			$postData = $request->getParsedBody();
			
			$id = $postData['id'] ?? false;
			if ($id) {
				if($postData['boton']=='del'){
				  try{
					$people = new Personas();
					$people->destroy($id);
					$responseMessage = "Se elimino el registro de la persona";
				  }catch(\Exception $e){
				  	//$responseMessage = $e->getMessage();
				  	$prevMessage = substr($e->getMessage(), 0, 38);
					if ($prevMessage =="SQLSTATE[23503]: Foreign key violation") {
						$responseMessage = 'Error, No se puede eliminar, esta persona esta en uso en la base de datos.';
					}
				  }
				}elseif ($postData['boton']=='upd') {
					$quiereActualizar=true;
				}
			}else{
				$responseMessage = 'Debe Seleccionar una persona';
			}
		}
		
		if ($quiereActualizar){
			//si quiere actualizar hace una consulta where id=$id y la envia por el array del renderHtml
			$personas = Personas::find($id);

			$roles = Roles::latest('id')->get();
			$tiposdocumentos = TiposDocumento::orderBy('nombre')->get();
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
			'responseMessage' => $responseMessage
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
					->key('genero', v::stringType()->length(1, 10)->notEmpty())
					->key('estadocivil', v::stringType()->length(1, 12)->notEmpty())
					->key('fechanacimiento', v::date())
					->key('rh', v::stringType()->length(1, 5)->notEmpty())
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
					$persona->genero = $postData['genero'];
					$persona->estadocivil = $postData['estadocivil'];
					$persona->fechanacimiento = $postData['fechanacimiento'];
					$persona->rh = $postData['rh'];
					$persona->niveleducativo = $postData['niveleducativo'];
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
					$prevMessage = substr($exception->getMessage(), 0, 33);
					
					if ($prevMessage == "SQLSTATE[23505]: Unique violation") {
						$responseMessage = 'Error, El numero del documento o el correo ya esta registrado';
					}else{
						$registrationErrorMessage = $exception->findMessages([
						'notEmpty' => '- Los campos con (*) no pueden estar vacios',
						'length' => '- Tiene una longitud no permitida',
						'stringType' => '- Solo puede contener numeros y letras',
						'email' => '- Formato de correo no valido', 
						'date' => '- Formato de fecha no valido',
						'numeric' => '- Solo puede contener numeros', 
						'positive' => '- Solo puede contener numeros mayores a cero'
						]);
					}
				}
			}
		}

		$iniciar=0;
		$personas = Personas::Join("persona.tiposdocumento","persona.personas.tipodocumentoid","=","persona.tiposdocumento.id")
		->select('personas.*', 'persona.tiposdocumento.nombre As tiposdocumento')
		->orderBy('nombre')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		return $this->renderHTML('listPersonas.twig',[
				'registrationErrorMessage' => $registrationErrorMessage,
				'responseMessage' => $responseMessage,
				'personas' => $personas
		]);
	}
}

?>
