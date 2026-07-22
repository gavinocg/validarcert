<?php
require dirname(__FILE__) . '/../conf/config.php';

//Permite acceso de rutas fuera del dominio 
//T-Api-Id header de identificacion
//Api-key = 3231a5ad-2d24-4dc2-b0f3-a791a8ff5eee

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, T-Api-Id, Content-Type, Accept");
header('Content-Type:application/json;charset=utf-8');
$headers = getallheaders();

if ($headers['T-Api-Id'] == '') {
    echo json_encode([
        'status' => 'ERROR',
        'message' => 'Error de Acceso'
    ]);
    exit;
}

if ($headers['T-Api-Id'] != '3231a5ad-2d24-4dc2-b0f3-a791a8ff5eee') {
    echo json_encode([
        'status' => 'ERROR',
        'message' => 'API Inválida'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'ERROR',
        'message' => 'Error de envio'
    ]);
    exit;
}

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
        'message' => 'Sin parametros.'
    ]);
    exit;
}

//Se crea la conexion oci con los parametros de config.php
$conn = oci_connect(USER, PASSWORD, SERVER,'AL32UTF8');

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

$query = "select t.cod_tramsec as no_tramite, ta.acto as certificado, t.tipo_registro as tipo_certificado, 
to_char(t.fecha_reg,'dd/mm/yyyy','nls_date_language=spanish') as fecha_solicitud,    
TRIM( NVL(p.pri_apellido||' ','')|| NVL(p.seg_apellido||' ','')|| NVL(p.pri_nombre||' ','')|| NVL(p.seg_nombre||' ','')) as solicitante
from certificado c inner join tramite t on c.cod_tram=t.cod_tram inner join persona p on t.cod_persol=p.cod_per
inner join tramite_acto ta on c.cod_tram=ta.cod_tram inner join tramite_seg ts on c.cod_tram=ts.cod_tram
where ts.estado = 'ACTIVO' and t.cod_tramsec='{$no_tramite}' and c.cod_cersec='{$no_certificado}'";

//Consulta a la base de datos Oracle
$stid = oci_parse($conn, $query);

oci_execute($stid);
$data = [];
while (($row = oci_fetch_array($stid, OCI_ASSOC))) {
    $data[] = $row;    
}
oci_free_statement($stid); //libero la memoria
oci_close($conn); //cierro la conexion

if (count($data) == 0) {
    echo json_encode([
        'status' => 'NODATA',
        'message' => 'Número de trámite no existe.'
    ]);
    exit;
}

echo json_encode([
    'status' => 'OK',
    'results' => $data
]);
exit;