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

1. **FPDI** importa cada página del PDF original (FPDI **no** importa anotaciones, por lo que las firmas electrónicas desaparecen automáticamente)
2. **FPDF** superpone "Sin validez legal" repetido en diagonal a 45° (Times Italic 12pt, gris claro RGB 192, espaciado 10mm) → se guarda como PDF intermedio
3. **Ghostscript** rasteriza cada página del PDF intermedio a PNG a 150 DPI (`PaginaAPng`)
4. **FPDF** embebe las imágenes rasterizadas en un nuevo PDF (el watermark queda fundido en los píxeles)
5. Archivos temporales se limpian automáticamente
6. El resultado es un PDF rasterizado sin firmas electrónicas, donde la marca de agua es parte de los píxeles, no una capa editable

### Requisitos del servidor
- Ghostscript (`gs`) en PATH o `/usr/bin/gs`
- Fuente Times Italic (incluida en FPDF)
- Función `exec()` habilitada

## Pruebas

No existe configuración de pruebas a nivel de proyecto. Las librerías vendeadas tienen sus propias pruebas (PHPUnit en PHPMailer y FPDI).

## Consideraciones de seguridad

- Las credenciales de BD y SMTP están hardcodeadas en `conf/config.php` e `includes/funciones.php`
- La API key está hardcodeada en `consultar.php`
- `wscons.php` interpola `$_POST` directamente en SQL sin prepared statements (posible SQL injection)
- `.htaccess` protege `conf/config.php` de acceso directo
- La marca de agua es rasterizada (no texto vectorial), impidiendo su remoción con editores PDF
