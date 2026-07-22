<?php
// Direccionamiento dinámico (Obligatorio)
require_once $_SERVER['DOCUMENT_ROOT'] . '/validar/includes/dir.php';
require_once TEMPLATE_PATH . 'header.php';
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
                            No se encuentra el archivo digital (PDF), confirme que el Certificado dispone de firma digital.
                        </div>
                    </div>
                    <div class="card-footer text-muted">
                        <input type="submit" id="volver" class="btn btn-primary pull-right" value="Volver" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<?php
require_once TEMPLATE_PATH . 'footer.php';
?>