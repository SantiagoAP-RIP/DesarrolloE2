# Guia de instalacion

1. Instalar XAMPP con Apache, MariaDB y PHP.
2. Copiar el proyecto en `C:\xampp\htdocs\DesarrolloE2`.
3. Activar la extension ZIP en `C:\xampp\php\php.ini` con `extension=zip`.
4. Iniciar Apache y MySQL.
5. Importar `database/schema.sql` desde phpMyAdmin.
6. Instalar Ollama y descargar `llama3.2`.
7. Verificar que `config/local.php` use `http://127.0.0.1:11434/v1/chat/completions`.
8. Abrir `http://localhost/DesarrolloE2/`.

Usuario inicial: `admin@documind.local` / `password`.