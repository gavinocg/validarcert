<!DOCTYPE html>
<html>

<head>
    <title>Validar certificado digital (Beta)</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!-- Bootstrap CSS v5.3.0 min-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous" />
    <!-- Ion icons v5 -->
    <link href="https://unpkg.com/ionicons@4.5.10-0/dist/css/ionicons.min.css" rel="stylesheet">
</head>

<body>
    <form id="consulta-form" action="consultar.php" method="post">
        <div class="container">
            <div class="row vh-50 justify-content-center align-items-center">
                <div class="col-auto bg-light p-5 ">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="ion-md-finger-print card-title"> Validar Certificado digital</h4>
                            <h4></h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 row justify-content-center">
                                <label for="no_tramite" class="form-label">Número de trámite:
                                    <i id="openBtn1" style="color:orange" class="ion-md-help-circle"></i></label>
                                <div class="mb-3 row">
                                    <input id="no_tramite" name="no_tramite" class="form-control" type="number"
                                        placeholder="Ingrese el número de trámite" autofocus required />
                                </div>
                                <label for="no_certificado" class="form-label">Número de Certificado: <i id="openBtn2"
                                        style="color:orange" class="ion-md-help-circle"></i></label>
                                <div class="mb-3 row">
                                    <input id="no_certificado" name="no_certificado" class="form-control" type="number"
                                        placeholder="Ingrese el número de certificado" required />
                                </div>
                                <div class="mb-3 row">
                                    <button class="btn btn-primary pull-right">Consultar</button>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <span style="font-size:12px"><b>Desripción:</b> Permite verificar y validar los datos de
                                Certificados emitidos de forma digital (versión Beta).</span>
                        </div>
                    </div>
                    <div class="col-auto bg-light">
                        <div id="resultados">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Jquery v3.6.4 -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"
        integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>

    <!-- Bootstrap CSS v5.3.0 bundle-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
        crossorigin="anonymous"></script>

    <script type="text/javascript">
        $("#consulta-form").submit(function (e) {
            e.preventDefault();
            $.ajax({
                'type': 'POST',
                'url': $("#consulta-form").attr('action'),
                'data': $("#consulta-form").serialize(),
                'success': function (responseText) {
                    var json_data = JSON.parse(responseText);
                    var salida = [];
                    console.log(json_data);
                    salida.push('<table class="table">');
                    salida.push('<th colspan="2"><span class="ion-md-search" style="font-size:26px; color:#2874A6;"> Resultado:</span></th>');
                    if (json_data.status == 'NODATA') {
                        salida.push('<tr colspan="2"><td>Número de trámite o número de certificado incorrecto, favor verificar.</td></tr>');
                        $("#resultados").html(salida.join(''));
                    } else if (json_data.status == 'OK') {
                        $.each(json_data.results, function (i, row) {
                            $.each(row, function (key, data) {
                                salida.push('<tr>');
                                salida.push('<th>' + key.toUpperCase().replace('_', ' ') + '</th>');
                                salida.push('<td>' + data + '</td>');
                                salida.push('</tr>');
                            });
                            salida.push('<tr>');
                            salida.push('<td><div class="d-grid gap-2"><button type="button" class="btn btn-secondary icon ion-md-search" onclick="location.reload()"> Nueva búsqueda</button></div></td>');
                            salida.push('<td><div class="d-grid gap-2"><button type="button" class="btn btn-success icon ion-md-eye" data-bs-toggle="modal" data-bs-target="#modalVentana" data-bs-notramite="' + row["NO_TRAMITE"] + '"> Ver Certificado digital</button></div></td>');
                            salida.push('</tr>');
                            salida.push('</table>');
                        });
                        console.log(salida);
                        $("#resultados").html(salida.join(''));
                    } else {
                        $("#resultados").html('');
                    }
                }
            });
            return false;
        });
    </script>

    <!-- CDN Swalert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>

    <!-- Modal Ventana -->
    <div class="modal fade" id="modalVentana" tabindex="-1" aria-labelledby="modalVentanaLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="modalVentanaLabel">Muy importante</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <input type="hidden" id="txtNoTramite">
                </div>
                <div class="modal-body">
                    <div class="row">
                        <ul class="list-unstyled">
                            <li>Tome en cuenta las siguientes consideraciones:
                                <ul style="text-align: justify; list-inline-padding:3rem;">
                                    <li>La información que se muestra en ésta herramienta es la conferida por
                                        el Registro de la Propiedad y Mercantil del Cantón Cayambe en el Certificado,
                                        la misma que reposa en las bases de datos del sistema informático de la
                                        Institución.</li>
                                    <li>En los certificados digitales, por seguridad se mostrará el documento sin la firma
                                        electrónica de la Máxima autoridad y con marca de agua.</li>
                                    <li>Se sugiere utilizar la herramienta FirmaEc para verificar la validez de la firma
                                        electrónica del Certificado digital que tiene en su poder.</li>
                                    <li>Usted es el responsable de comparar y verificar que la información
                                        proporcionada en ésta herramienta sea exacta a la contenida en el Certificado
                                        que tiene en su poder, de existir inconsistencias o alteraciones es su deber
                                        reportarlas inmediatamente al RPMC a través de los contactos (02) 236-0299 o
                                        (02) 211-1065.
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="chkConsideraciones" autofocus>
                        <label class="form-check-label" for="chkConsideraciones">
                            <b>He leído y entendido las consideraciones indicadas anteriormente.</b>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-success icon ion-md-eye" id="btnVerCertificado" disabled> Ver
                        Certificado</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Ejemplo de funcion ajax
    <script language="javascript">
        function mostrar(no_trmite) {
            $.ajax({
                url: 'mostrar.php',
                type: "post",
                data: {
                    no_trmite: no_trmite
                },
                success: function (respuesta) {
                    alert("Se mostro exitosamente.");
                },
                error: function () {
                    alert("Error");
                }
            });
        }
    </script> 
    -->

    <!-- Validaciones java de componentes-->
    <script language="javascript">

        // Elementos del DOM
        const
            $btnVerCertificado = document.querySelector("#btnVerCertificado"),
            $txtNoTramite = document.querySelector("#txtNoTramite");

        // Mostrar ventana #modalCargar y pasar la fecha de generacion de facturas
        var modalVentana = document.getElementById('modalVentana');
        modalVentana.addEventListener('show.bs.modal', function (event) {
            // Button that triggered the modal
            var button = event.relatedTarget;
            // Extract info from data-bs-* attributes
            var num_tram = button.getAttribute('data-bs-notramite');
            // Update the modal's content.            
            var modalBodyInput = modalVentana.querySelector('.modal-body input');
            modalBodyInput.value = num_tram;
            $txtNoTramite.setAttribute('value', num_tram);
        });

        // Va
        $("#chkConsideraciones").change(function () {
            if (this.checked) {
                $btnVerCertificado.removeAttribute('disabled');
            } else {
                $btnVerCertificado.setAttribute('disabled', 'true');
            }
        });

        // Validacion de boton Ver Certificado
        $btnVerCertificado.addEventListener("click", async () => {
            // Obtengo numero de tramite
            var num_tram = document.getElementById("txtNoTramite").value;
            // Muestra certificado en ventana en blanco
            window.open('mostrar.php?no_tramite=' + num_tram, "_blank");
        });

        $('#modalVentana').on('shown.bs.modal', function () {
            $(this).find('[autofocus]').focus();
        });

        $('#openBtn1').click(function (e) {
            e.preventDefault();

            Swal.fire({

                imageUrl: 'img/certificado.png',
                imageWidth: 800,
                imageAlt: 'Custom image',
                width: 'auto'
            });
        });

        $('#openBtn2').click(function (e) {
            e.preventDefault();

            Swal.fire({

                imageUrl: 'img/certificado.png',
                imageWidth: 800,
                imageAlt: 'Custom image',
                width: 'auto'
            });
        });

    </script>

</body>

</html>