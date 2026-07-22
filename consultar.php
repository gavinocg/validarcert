<?php
//Se define la direccion del api json de conexion
$url = "https://validar.rpcayambe.gob.ec/api/wscons.php";

if (isset($_POST['no_tramite']) && isset($_POST['no_certificado'])) {
    //Obtenemos las variables POST
    $no_tramite = $_POST['no_tramite'];
    $no_certificado = $_POST['no_certificado'];
} else if(isset($_GET['no_tramite']) && isset($_GET['no_certificado'])){    
    //Obtenemos las variables GET
    $no_tramite = $_GET['no_tramite'];
    $no_certificado = $_GET['no_certificado'];
}else{
    echo json_encode([
        'status' => 'ERROR',
        'message' => 'Sin parámetros.'
    ]);
    exit;
}

//Parametros de la consulta
$postData = array("no_tramite"=>$no_tramite, "no_certificado"=>$no_certificado);
$handler = curl_init();
curl_setopt($handler, CURLOPT_HTTPHEADER, array(
		'T-Api-Id: 3231a5ad-2d24-4dc2-b0f3-a791a8ff5eee',
		"Content-Type: multipart/form-data")
		);
curl_setopt($handler, CURLOPT_URL, $url);
curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
curl_setopt($handler, CURLOPT_POSTFIELDS, $postData);
$response = curl_exec($handler);
curl_close($handler);

print_r($response);