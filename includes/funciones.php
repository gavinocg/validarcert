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

// Convierte una página de PDF a PNG usando Ghostscript
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

// Extrae las coordenadas de los rectangulos de firma electronica por cada pagina
function ExtraerRectsFirma($pdf_path)
{
    $content = file_get_contents($pdf_path);

    $objects = [];
    preg_match_all('/(\d+)\s+\d+\s+obj(.*?)endobj/s', $content, $matches, PREG_SET_ORDER);
    foreach ($matches as $m) {
        $objects[(int)$m[1]] = $m[2];
    }

    $sig_rects_by_obj = [];
    foreach ($objects as $obj_num => $body) {
        if (preg_match('/\/FT\s*\/Sig/i', $body) && preg_match('/\/Rect\s*\[(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\]/i', $body, $r)) {
            $sig_rects_by_obj[$obj_num] = [(int)$r[1], (int)$r[2], (int)$r[3], (int)$r[4]];
        }
    }

    if (empty($sig_rects_by_obj)) {
        return [];
    }

    $page_rects = [];
    foreach ($objects as $obj_num => $body) {
        if (preg_match('/\/Type\s*\/Page/i', $body) && preg_match('/\/Annots\s*\[(.*?)\]/i', $body, $a)) {
            $page_idx = count($page_rects) + 1;
            preg_match_all('/(\d+)\s+\d+\s+R/i', $a[1], $refs);
            foreach ($refs[1] as $ref) {
                $ref_int = (int)$ref;
                if (isset($sig_rects_by_obj[$ref_int])) {
                    $page_rects[$page_idx][] = $sig_rects_by_obj[$ref_int];
                }
            }
        }
    }

    return $page_rects;
}

// Aplica marca de agua sobre una imagen PNG usando GD
function AplicarMarcaAguaImagen($img_path, $output_path, $qr_rects = null)
{
    $img = imagecreatefrompng($img_path);
    if (!$img) {
        throw new Exception('No se pudo cargar la imagen: ' . $img_path);
    }

    $height = imagesy($img);

    // Cubrir QR(s) con rectangulo negro si se proporcionaron coordenadas
    if ($qr_rects) {
        $factor = 150 / 72;
        $black = imagecolorallocate($img, 0, 0, 0);
        foreach ($qr_rects as $qr_rect) {
            $x1 = round($qr_rect[0] * $factor) - 30;
            $y1 = $height - round($qr_rect[3] * $factor) - 30;
            $x2 = round($qr_rect[2] * $factor) + 30;
            $y2 = $height - round($qr_rect[1] * $factor) + 30;
            imagefilledrectangle($img, $x1, $y1, $x2, $y2, $black);
        }
    }

    $font = '/usr/share/fonts/opentype/urw-base35/NimbusRoman-Italic.otf';
    if (!file_exists($font)) {
        $font = '/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf';
    }

    $gray = imagecolorallocatealpha($img, 192, 192, 192, 60);
    $font_size = 12;
    $watermarkText = str_repeat('Sin validez legal - ', 15);

    $text_w = imagesx($img);
    $step_y = round(10 * 150 / 25.4);

    for ($y = $step_y; $y < $height + $text_w; $y += $step_y) {
        imagettftext($img, $font_size, 45, 0, $y, $gray, $font, $watermarkText);
    }

    imagepng($img, $output_path);
    imagedestroy($img);
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
        $page_rects = ExtraerRectsFirma($ruta_pdf);

        $pdf_info = new \setasign\Fpdi\Fpdi();
        $pages_count = $pdf_info->setSourceFile($ruta_pdf);
        unset($pdf_info);

        $new_pdf = new FPDF('P', 'mm', 'A4');
        $new_pdf->SetAutoPageBreak(false);

        for ($i = 1; $i <= $pages_count; $i++) {
            $png_path = PaginaAPng($ruta_pdf, $i, $tmp_dir);
            $wm_path = $tmp_dir . '/wm_' . $i . '.png';
            $current_rects = isset($page_rects[$i]) ? $page_rects[$i] : null;
            AplicarMarcaAguaImagen($png_path, $wm_path, $current_rects);
            $new_pdf->AddPage();
            $new_pdf->Image($wm_path, 0, 0, 210, 297);
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