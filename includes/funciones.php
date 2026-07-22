<?php
@opcache_invalidate(__FILE__, true);
// Direccionamiento dinámico (Obligatorio)
include_once (__DIR__ . '/dir.php');
// PHPMailer
require_once LIB_PATH . 'PHPMailer6.3/src/Exception.php';
require_once LIB_PATH . 'PHPMailer6.3/src/PHPMailer.php';
require_once LIB_PATH . 'PHPMailer6.3/src/SMTP.php';
// Incluímos las librerías de manipulación de PDF
require_once LIB_PATH . 'fpdf/fpdf.php';
//require_once LIB_PATH . 'FPDF_Protection.php';
require_once LIB_PATH . 'fpdi/src/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

// Verificar estructura de correo
function ComprobarEmail($email)
{
    return (boolean) filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Set datos de conexión de correo de envio
function ConexionCorreo()
{
    date_default_timezone_set("America/Guayaquil");
    $mail = new PHPMailer(true);
    try {
        //Server settings [rpcayambe]
        $mail->SMTPDebug = 0; // Activar debug
        $mail->isSMTP();
        $mail->SMTPSecure = "ssl";
        $mail->Host = 'webserv.rpcayambe.gob.ec';
        $mail->SMTPAuth = true;
        $mail->Port = 465;
        $mail->Username = 'envio@mailing.rpcayambe.gob.ec';
        $mail->Password = 'Rpm.0101.Cay';
        $mail->CharSet = 'UTF-8';
        $mail->setFrom('info@rpcayambe.gob.ec', 'Registro de la Propiedad y Mercantil del Canton Cayambe');
        $mail->IsHTML(true);
    } catch (Exception $e) {
        echo "Error en definicion de parametros PHPMailer.";
    }
    return $mail;
}

function BuscarClaveArray($array, $campo, $valor)
{
    foreach ($array as $key => $elemento) {
        if ($elemento[$campo] === $valor) {
            return $key;
        }
    }
    return false;
}

// Extraer archivos zip a la raiz de un directorio
function Extraer($ruta_zip, $dest = '.')
{
    $zip = new ZipArchive;
    if ($zip->open($ruta_zip)) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (substr($entry, -1) == '/')
                continue; // skip directories
            $fp = $zip->getStream($entry);
            $ofp = fopen($dest . '/' . basename($entry), 'w');
            if (!$fp)
                throw new Exception('Unable to extract the file.');
            while (!feof($fp))
                fwrite($ofp, fread($fp, 8192));
            fclose($fp);
            fclose($ofp);
        }
        $zip->close();
    } else {
        return false;
    }
    return $zip;
}

// Identificar archivo en un directorio
function BuscarArchivo($path, $no_tramite)
{
    $dir = opendir($path);
    $files = array();
    while ($current = readdir($dir)) {
        if ($current != "." && $current != "..") {
            if (is_dir($path . $current)) {
                BuscarArchivo($path . $current . '/', $no_tramite);
            } else {
                $files[] = $current;
            }
        }
    }    
    foreach ($files as $f) {
        // Obtengo la parte numeroca del nombre de archivo
        $parte_num = explode('-', $f, 2);
        // Comparo que el numero de tramite sea igual a la parte numerica del nombre del archivo
        if (strlen($parte_num[0]) == strlen($no_tramite)) {
            // Busca desde el inicio coincidencia exacta
            if (substr($f, 0, strlen($no_tramite)) === $no_tramite) {                
                $pathPdf = $path . $f;
                return $pathPdf;
            }
        }
    }    
}

function buscarArchivoRecursivo($path, $no_tramite){    
    $dir = opendir($path);
    $files = array();
    while ($current = readdir($dir)){
        if( $current != "." && $current != "..") {
            if(is_dir($path.$current)) {
                buscarArchivoRecursivo($path.$current.'/', $no_tramite);
            }
            else {
                $files[] = $current;
            }
        }
    }
    for($i=0; $i<count( $files ); $i++){        
        $parte_num =preg_replace('/\D+/', '', $files[$i]);        
        if ($parte_num == trim($no_tramite) && strlen($parte_num) == strlen($no_tramite)) {            
            $pathPdf = $path . $files[$i];
            echo $pathPdf; // Impresion para captura por ob_start()
        }
    }  
}

// Busca archivos en el repositorio fisico por numero de tramite
function BuscarArchivosFisico($path, $no_tramite)
{
    $resultados = array();
    if (!is_dir($path)) {
        return $resultados;
    }
    $dir = opendir($path);
    if (!$dir) {
        return $resultados;
    }
    while ($current = readdir($dir)) {
        if ($current == "." || $current == "..") continue;
        if (is_dir($path . $current)) continue;
        $parte_num = explode('-', $current, 2);
        $nombre_sin_ext = pathinfo($current, PATHINFO_FILENAME);
        $base = rtrim($nombre_sin_ext, 'cC');
        if ($base === $no_tramite) {
            $ruta = $path . $current;
            $fecha = date('Y-m-d H:i:s', filemtime($ruta));
            $prioridad = strlen($nombre_sin_ext) - strlen($base);
            $resultados[] = array(
                'nombre' => $current,
                'fecha' => $fecha,
                'ruta' => $ruta,
                'prioridad' => $prioridad
            );
        }
    }
    closedir($dir);
    usort($resultados, function($a, $b) {
        if ($a['prioridad'] != $b['prioridad']) {
            return $b['prioridad'] - $a['prioridad'];
        }
        return strcmp($a['fecha'], $b['fecha']);
    });
    return $resultados;
}

// Convierte una pagina de PDF a PNG usando Ghostscript
function PaginaAPng($pdf_path, $page_num, $output_dir, $dpi = 150)
{
    $output_path = $output_dir . '/page_' . $page_num . '.png';
    $cmd = sprintf(
        'gs -dNOPAUSE -dBATCH -dQUIET -dFirstPage=%d -dLastPage=%d -sDEVICE=png16m -r%d -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -sOutputFile=%s "%s" 2>&1',
        $page_num,
        $page_num,
        $dpi,
        $output_path,
        $pdf_path
    );
    exec($cmd, $output, $return_code);
    if ($return_code !== 0 || !file_exists($output_path)) {
        throw new Exception('Error al convertir página a PNG: ' . implode("\n", $output));
    }
    return $output_path;
}

// Inserta marca de agua rasterizada en PDF y lo retorna
function MostrarPdfMarcaAgua($ruta_pdf)
{
    ob_start();
    $tmp_dir = sys_get_temp_dir() . '/pdfwm_' . uniqid('', true);
    if (!mkdir($tmp_dir, 0700, true) && !is_dir($tmp_dir)) {
        throw new Exception('No se pudo crear directorio temporal');
    }

    try {
        // 1. Crear PDF intermedio con FPDI (no importa firmas electronicas) + FPDF (watermark vectorial)
        $tmp_pdf = $tmp_dir . '/wm.pdf';
        $pdf = new \setasign\Fpdi\Fpdi();
        $pages_count = $pdf->setSourceFile($ruta_pdf);

        $watermarkText = str_repeat('Sin validez legal - ', 15);

        for ($i = 1; $i <= $pages_count; $i++) {
            $tplIdx = $pdf->importPage($i);
            $pdf->AddPage();
            $pdf->useTemplate($tplIdx, 0, 0);

            $pdf->SetFont('Times', 'I', 12);
            $pdf->SetTextColor(192, 192, 192);

            for ($y = 10; $y < 507; $y += 10) {
                $angle = 45 * M_PI / 180;
                $c = cos($angle);
                $s = sin($angle);
                $cx = 0;
                $cy = 300 - $y;
                $pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
                $pdf->Text(0, $y, $watermarkText);
                $pdf->_out('Q');
            }
        }

        $pdf->Output('F', $tmp_pdf);
        unset($pdf);

        // 2. Rasterizar cada pagina del PDF intermedio a PNG y embeber en PDF final
        $new_pdf = new FPDF('P', 'mm', 'A4');
        $new_pdf->SetAutoPageBreak(false);

        for ($i = 1; $i <= $pages_count; $i++) {
            $png_path = PaginaAPng($tmp_pdf, $i, $tmp_dir);
            $new_pdf->AddPage();
            $new_pdf->Image($png_path, 0, 0, 210, 297);
        }

        $new_pdf->Output();
    } finally {
        if (is_dir($tmp_dir)) {
            $files = glob($tmp_dir . '/*');
            if ($files) {
                array_map('unlink', $files);
            }
            rmdir($tmp_dir);
        }
    }
    ob_end_flush();
}