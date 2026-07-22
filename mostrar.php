<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mostrar</title>
    <!-- Bootstrap CSS v5.3.0 min-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous" />
    <!-- Funcion para cerrar la pestaña-->
    <script language="javascript" type="text/javascript">
        function cerrar() {
            window.open('', '_parent', '');
            window.close();
        } 
    </script>
</head>

<body>
    <?php
    // Direccionamiento dinámico (Obligatorio)
    require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/dir.php';
    require_once INCLUDES_PATH . 'funciones.php';
    // Se define variables locales
    $periodo_actual = date('Y');
    $repo_path = REPO_PATH . $periodo_actual . "/";
    $respuesta = "";
    // Se obtiene las variables de GET / POST
    if (isset ($_POST['no_tramite'])) {
        $no_tramite = $_POST['no_tramite'];
    } else if (isset ($_GET['no_tramite'])) {
        $no_tramite = $_GET['no_tramite'];
    } else {
        $respuesta .= "<li>No se ha recibido número de trámite para la búsqueda.</li>";
    }

    // Detectar si viene del repositorio fisico
    $repo_fisico = isset($_GET['repo']) && $_GET['repo'] === 'fisico';
    $archivo_nombre = isset($_GET['archivo']) ? $_GET['archivo'] : '';

    if ($repo_fisico && !empty($archivo_nombre)) {
        $repo_path = REPO_FISICO_PATH . $periodo_actual . "/";
        $ruta_archivo = $repo_path . $archivo_nombre;
        if (!file_exists($ruta_archivo)) {
            $respuesta .= "<li>El archivo solicitado no existe en el repositorio físico.</li>";
        }
    } elseif ($repo_fisico && empty($archivo_nombre)) {
        header('Location: seleccionar.php?no_tramite=' . urlencode($no_tramite));
        exit;
    }

    if (substr($no_tramite, -2) != substr($periodo_actual, -2)) {
        $respuesta .= "<li>Recuerde que solo puede validar Certificados digitales emitidos durante el período en curso (<b>" . $periodo_actual . "</b>).</li>";
    }

    // Definimos un nuevo handler de errores y warnings
    set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
        throw new ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
    }, E_WARNING);
    try {
        if ($repo_fisico && !empty($archivo_nombre)) {
            $archivo = $ruta_archivo;
        } else {
            $archivo = BuscarArchivo($repo_path, $no_tramite);
        }
        MostrarPdfMarcaAgua($archivo);
    } catch (Exception $e) {
        // Si no se encuentra en el repo digital, buscar en el fisico
        if (!$repo_fisico) {
            $fisico_path = REPO_FISICO_PATH . $periodo_actual . "/";
            $fisicos = BuscarArchivosFisico($fisico_path, $no_tramite);
            if (count($fisicos) > 0) {
                header('Location: seleccionar.php?no_tramite=' . urlencode($no_tramite));
                exit;
            }
        }
        $respuesta .= "<li>Confirme que el número de trámite corresponde a un documento emitido.</li>"
            ?>
        <form action="index.php" method="post">
            <div class="container">
                <div class="row vh-50 justify-content-center align-items-center">
                    <div class="col-auto bg-light p-5">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Estimado Usuario(a):</h4>
                            </div>
                            <div class="card-body">
                                <div class="login-form">
                                    <div class="row">
                                        <ul class="list-unstyled">
                                            <li>No se puede mostrar el archivo digital (PDF) del Certificado consultado, favor tomar en
                                                cuenta lo siguiente:
                                                <ul style="text-align: justify; list-inline-padding:3rem;">
                                                    <?php echo $respuesta; ?>
                                                    <li>Para realizar consultas de Certificados digitales emitidos en
                                                        períodos anteriores debe acercarse a nuestras oficinas.
                                                    </li>
                                                    <li>Confirme que el Certificado digital dispone de firma electrónica
                                                        válida
                                                        (se sugiere utilizar la herramienta FirmaEc)</li>
                                                    <li>Si sospecha de una falsificación favor reportarla vía telefónica al
                                                        (02)
                                                        236-0299 Extensiones 1606 o 1202.</li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </div>
                                    <br>
                                    <?php
                                    //echo "Error: " . $e;
                                    //echo "<br>Archivo: " . $archivo;
                                    ?>
                                </div>
                            </div>
                            <div class="card-footer text-muted">
                                <a class="btn btn-success" href="javascript:cerrar();">Cerrar</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </body>

    </html>

    <?php
    }
    // Restauramos el handler normal
    restore_error_handler();
    ?>