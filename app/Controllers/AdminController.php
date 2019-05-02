<?php 

namespace App\Controllers;

use App\Models\{Job,Project};

class AdminController extends BaseController{
	public function getIndex(){
		return $this->renderHTML('admin.twig');

	}

	public function getSupervisor(){
		return $this->renderHTML('supervisor.twig');

	}

	public function getManager(){
		return $this->renderHTML('supervisor.twig');

	}
}

?>