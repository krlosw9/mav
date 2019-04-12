<?php 

namespace App\Controllers;

use App\Models\{Job,Project};

class AdminController extends BaseController{
	public function getIndex(){
		return $this->renderHTML('admin.twig');

	}

	public function getSecrerary(){
		return $this->renderHTML('secretary.twig');

	}
}

?>