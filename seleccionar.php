<!DOCTYPE html>
<html>
<head>
    <title>Seleccionar archivo - Validar certificado digital</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous" />
    <link href="https://unpkg.com/ionicons@4.5.10-0/dist/css/ionicons.min.css" rel="stylesheet">
</head>
<body>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/dir.php';
    require_once INCLUDES_PATH . 'funciones.php';

    $periodo_actual = date('Y');
    $no_tramite = isset($_GET['no_tramite']) ? trim($_GET['no_tramite']) : '';
    $repo_path = REPO_FISICO_PATH . $periodo_actual . "/";

    if (empty($no_tramite)) {
        header('Location: index.php');
        exit;
    }

    $archivos = BuscarArchivosFisico($repo_path, $no_tramite);

    if (count($archivos) === 0) {
        header('Location: mostrar.php?no_tramite=' . urlencode($no_tramite));
        exit;
    }

    if (count($archivos) === 1) {
        header('Location: mostrar.php?no_tramite=' . urlencode($no_tramite) . '&repo=fisico&archivo=' . urlencode($archivos[0]['nombre']));
        exit;
    }
    ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <span class="ion-md-document"></span>
                            Archivos encontrados para trámite: <strong><?php echo htmlspecialchars($no_tramite); ?></strong>
                        </h4>
                    </div>
                    <div class="card-body">
                        <p>Se encontraron múltiples versiones del archivo. Seleccione la que desea visualizar:</p>
                        <div class="list-group">
                            <?php
                            $mejor = $archivos[0];
                            foreach ($archivos as $archivo):
                                $es_mejor = ($archivo['nombre'] === $mejor['nombre']);
                                $link = 'mostrar.php?no_tramite=' . urlencode($no_tramite) . '&repo=fisico&archivo=' . urlencode($archivo['nombre']);
                            ?>
                            <a href="<?php echo $link; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $es_mejor ? 'active' : ''; ?>">
                                <span>
                                    <span class="ion-md-attach"></span>
                                    <?php echo htmlspecialchars($archivo['nombre']); ?>
                                    <?php if ($es_mejor): ?>
                                    <span class="badge bg-light text-dark ms-2">Recomendado</span>
                                    <?php endif; ?>
                                </span>
                                <small><?php echo htmlspecialchars($archivo['fecha']); ?></small>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="index.php" class="btn btn-secondary">
                            <span class="ion-md-arrow-back"></span> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
