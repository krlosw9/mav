<?php 

namespace App\Controllers;

use App\Models\{Vehiculo, Personas};
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Zend\Diactoros\Response\RedirectResponse;

class FilesValidatorController extends BaseController{
	
	public function filesValidator($file, $temporaryFileName=null) {
    	$responseMessage = null; $error=0; $retorno = array(); $fileName=null;
    	$maximumSize = 2000000; $textMaximumSize = "2 MB";

    	if($file->getError() == UPLOAD_ERR_OK){
	    	$allowedMimetypes = array('image/gif', 'image/jpeg', 'image/png', 'image/bmp', 'application/pdf');
	 	
	 		//si no se encuenta alguno de los Mimetypes 
		    if (!in_array($file->getClientMediaType(), $allowedMimetypes)){
		        $error=1;
		        $responseMessage = "Error, solo se permite imágenes o PDF";
		    }
		    if ($file->getSize() > $maximumSize) {
		    	$error=1;
		    	$responseMessage .= " y no puede pesar mas de $textMaximumSize.";
		    }
		}else{
			if ($file->getError() == 4) {
				$error=4;
				$responseMessage = "Error, el comprobante adjunto es obligatorio";
			}else{
				$error=1;
	    		$responseMessage = "Error (ID:".$file->getError()."), el archivo no puede superar los $textMaximumSize ";	
			}
		}

		if ($error==0) {
			$divideCadena = explode("/", $file->getClientMediaType());
			$extension=$divideCadena[1];
			$fileName = $temporaryFileName.'.'.$extension;
		}

		$retorno['error']=$error;
	    $retorno['message']=$responseMessage;
	    $retorno['fileName']=$fileName;

	    return $retorno;
	    
	 	//para que funcione esta validacion se necesita que el $file sea un array del tipo $_FILE en este momento el parametro es un objeto zend/diactoros
	 	/*
		 	//trae la propiedad ancho y alto de el archivo temporal en el servidor de $file (tmp_name es el nombre del archivo temporal en el servidor) y lo guarda como array en $image
		    $image = getimagesize($file['tmp_name']);
		 	$image_width = $image[0];
		    $image_height = $image[1];

		    $maximum = array(
		        'width' => '1024',
		        'height' => '768'
		    );
		 
		    $responseMessage = "Image dimensions are too large. Maximum size is {$maximum['width']} by {$maximum['height']} pixels. Uploaded image is $image_width by $image_height pixels.";
		 
		    if ( $image_width > $maximum['width'] || $image_height > $maximum['height'] ) {
		        //add in the field 'error' of the $file array the message
		        $file['message'] = $responseMessage;
		        $file['error'] = $error;
		        return $file;
		    }else {
		        return $file;
		    }
	    */
	}

}

?>
