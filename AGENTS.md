# AGENTS.md - Validar Certificado Digital

## Descripción del proyecto
Aplicación web PHP (procedural, sin framework) para que ciudadanos verifiquen certificados digitales emitidos por el Registro de la Propiedad y Mercantil del Cantón Cayambe (RPMC). El usuario ingresa un número de trámite y certificado, se consulta una base de datos Oracle y se muestra el PDF con marca de agua.

## Stack tecnológico
- **PHP 5.6+** - Backend procedural
- **Oracle Database** - Base de datos (vía OCI8)
- **Bootstrap 5.3**, **jQuery 3.6**, **Ionicons 4.5**, **SweetAlert2 9** - Frontend (CDN)
- **FPDF** + **FPDI (setasign/fpdi)** - Generación de PDF
- **Ghostscript 10.02.1** + **PHP GD** - Rasterización y marca de agua no removible
- **PHPMailer 6.3** - Correo SMTP (no usado activamente)
- **cURL** - Proxy de API interna

## Estructura del proyecto

```
public_html/
├── index.php              # Formulario de búsqueda (página principal)
├── consultar.php          # Proxy AJAX -> API interna
├── mostrar.php            # Visor de PDF con marca de agua rasterizada
├── error.php              # Página de error
├── api/
│   └── wscons.php         # API REST interna (consulta Oracle)
├── conf/
│   ├── config.php         # Constantes de BD (Oracle)
│   └── .htaccess          # Protege config.php
├── includes/
│   ├── dir.php            # Constantes de rutas
│   └── funciones.php      # Helpers (PDF, búsqueda archivos, email, ZIP)
├── lib/
│   ├── fpdf/              # FPDF (PDF generation)
│   ├── fpdi/              # FPDI (PDF import - solo para conteo de páginas)
│   └── PHPMailer6.3/      # PHPMailer (email)
└── img/
    └── certificado.png    # Imagen de ayuda
```

## Comandos

No hay `composer.json` ni `package.json` a nivel raíz. Las librerías están vendeadas manualmente.

## Convenciones de código

- PHP procedural, sin POO ni namespaces (excepto librerías externas)
- Nombres de funciones en español con `PascalCase` o `camelCase` según archivo
- Variables en español, en su mayoría en `snake_case`
- Sin tipado estricto declarado
- CDATA para jQuery, Bootstrap, etc.

## Flujo de marca de agua (MostrarPdfMarcaAgua)

1. **Ghostscript** elimina la firma electrónica (anotaciones `/FT/Sig`) del PDF original (`RemoverFirmaElectronica`)
2. **Ghostscript** rasteriza cada página del PDF limpio a PNG a 150 DPI (`PaginaAPng`)
3. **PHP GD** superpone "Sin validez legal" repetido en diagonal a 45° (18pt, 80% opaco, espaciado denso ~2.5mm) sobre la imagen PNG (`AplicarMarcaAguaImagen`)
4. **FPDF** embebe las imágenes watermarked en un nuevo PDF
5. Archivos temporales se limpian automáticamente
6. El resultado es un PDF rasterizado donde la marca de agua es parte de los píxeles, no una capa editable. La firma electrónica (incluyendo QR) ha sido eliminada antes de la rasterización.

### Requisitos del servidor
- Ghostscript (`gs`) en PATH o `/usr/bin/gs`
- PHP GD con soporte para `imagecreatefrompng()`, `imagettftext()`, `imagecolorallocatealpha()`, `imagefilter()` con `IMG_FILTER_PIXELATE`
- Fuente NimbusRoman-Italic.otf en `/usr/share/fonts/opentype/urw-base35/`
- Función `exec()` habilitada

## Pruebas

No existe configuración de pruebas a nivel de proyecto. Las librerías vendeadas tienen sus propias pruebas (PHPUnit en PHPMailer y FPDI).

## Consideraciones de seguridad

- Las credenciales de BD y SMTP están hardcodeadas en `conf/config.php` e `includes/funciones.php`
- La API key está hardcodeada en `consultar.php`
- `wscons.php` interpola `$_POST` directamente en SQL sin prepared statements (posible SQL injection)
- `.htaccess` protege `conf/config.php` de acceso directo
- La marca de agua es rasterizada (no texto vectorial), impidiendo su remoción con editores PDF
