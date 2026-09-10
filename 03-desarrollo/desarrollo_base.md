# III. Desarrollo

La entrada web está en `public/index.php`; la configuración está en `config/config.php`; la conexión, sesión y CSRF en `app/bootstrap.php`; el flujo de análisis en `app/document_processor.php`; el esquema en `database/schema.sql`.

La aplicación usa `declare(strict_types=1)`, PDO, `password_hash/password_verify`, nombres de archivo aleatorios, validación de extensión y límite de 10 MB. Los secretos no están escritos en el código.

## Git y bitácora sugerida

Registrar cada avance con fecha, responsable, requisito abordado, archivos modificados, evidencia y pendiente. Los commits deben ser pequeños: `setup`, `auth`, `repositories`, `document-processing`, `search`, `tests`, `docs`.