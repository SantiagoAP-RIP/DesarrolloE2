# Arquitectura del sistema

## Componentes

- Frontend: HTML y CSS servido por Apache.
- Backend: PHP 8, sesiones, CSRF y PDO.
- Persistencia: MariaDB/MySQL.
- Almacenamiento: `storage/documents`.
- IA: Ollama local mediante endpoint compatible con OpenAI.

## Flujo de datos

El navegador envia el archivo a PHP. PHP lo valida y almacena, extrae el texto, llama a Ollama, valida la respuesta JSON y guarda contenido, categoria, resumen, datos extraidos, estado y logs en MySQL.