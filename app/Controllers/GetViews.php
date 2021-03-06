<?php 

namespace App\Controllers;

use App\Models\{Personas, Vehiculos, Alistamientos, AlistamientoGruposAlistamiento, AlistamientosInformacionAlistamiento, AlistamientosTiposAlistamiento};
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Zend\Diactoros\Response\RedirectResponse;

class GetViews extends BaseController{
	
	//estos dos valores son los que se cambian, para modificar la cantidad de registros listados por pagina y el maximo numero en paginacion
	private $articulosPorPagina=10;
	private $limitePaginacion=20;

	public function getForms(){
		$gruposalistamiento = null; $tiposalistamiento=null; $vehiculos=null; $conductores=null; $responsables=null; $fechaHoy=null;

		$gruposalistamiento = AlistamientoGruposAlistamiento::orderBy('id')->get();
		$tiposalistamiento = AlistamientosTiposAlistamiento::orderBy('gaid')->get();
		$vehiculos = Vehiculos::orderBy('placa')->get();
		$conductores = Personas::where("persona.personas.rolid",">=",4)->orderBy('nombre')->get();
		$responsables = Personas::where("persona.personas.rolid","=",3)->orderBy('nombre')->get();


		$fechaHoy= date("Y-m-d");

		return $this->renderHTML('alistamientoPrint.twig',[
				'gruposalistamiento' => $gruposalistamiento,
				'alistamientosRegistrados' => $tiposalistamiento,
				'fechaHoy' => $fechaHoy,
				'vehiculos' => $vehiculos,
				'conductores' => $conductores,
				'responsables' => $responsables,
		]);
	}


	//Lista todas los modelos Ordenando por posicion
	public function getList(){
		$responseMessage = null; $personas=null; $numeroDePaginas=null;

		$paginaActual = $_GET['pag'] ?? null;		
		$paginador = $this->paginador($paginaActual);
		$numeroDePaginas=$paginador['numeroDePaginas'];
		$personas=$paginador['personas'];

		return $this->renderHTML('vehiculoList.twig', [
			'personas' => $personas,
			'numeroDePaginas' => $numeroDePaginas,
			'paginaActual' => $paginaActual
		]);
		
	}

	public function paginador($paginaActual=null){
		$retorno = array(); $iniciar=0; $numeroDePaginas=1; $personas=null;
		
		$numeroDeFilas = Vehiculo::selectRaw('count(*) as query_count')
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

		$personas = Vehiculo::Join("vehiculo.tiposvinculacion","vehiculo.vehiculos.tipvinculacionid","=","vehiculo.tiposvinculacion.id")
		->select('vehiculos.*', 'vehiculo.tiposvinculacion.nombre As tiposvinculacion')
		->latest('id')
		->limit($this->articulosPorPagina)->offset($iniciar)
		->get();

		$retorno = [
			'iniciar' => $iniciar,
			'numeroDePaginas' => $numeroDePaginas,
			'personas' => $personas

		];

		return $retorno;
	}

}

?>
