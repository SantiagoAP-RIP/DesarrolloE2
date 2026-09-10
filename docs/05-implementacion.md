# V. Implementación

## Requisitos

Windows, XAMPP con Apache, PHP 8, MariaDB/MySQL, extensión PDO MySQL, `fileinfo`, `mbstring` y, para DOCX, `ZipArchive`.

## Despliegue

Copiar el proyecto a `htdocs`, iniciar Apache/MySQL, importar el esquema, revisar permisos de `storage/documents` y abrir la URL local. Para respaldo, exportar la base con phpMyAdmin y conservar una copia de `storage/documents`.

## Mantenimiento

Revisar logs, actualizar dependencias del servidor, rotar credenciales, respaldar semanalmente y probar restauración. Para producción se debe agregar control de autorización por repositorio, antivirus, OCR y colas de procesamiento.