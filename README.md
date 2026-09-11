# DocuMind

Sistema inteligente de gestion y analisis documental empresarial.

## Estructura academica

- `00-ingenieria-de-requerimientos/`: requisitos, historias y actas.
- `01-analisis/`: especificacion funcional y diagramas pendientes.
- `02-diseño/`: arquitectura, mockups y diagramas pendientes.
- `03-desarrollo/`: codigo y documentacion tecnica.
- `04-pruebas/`: casos, evidencias y repositorio de documentos.
- `05-implementacion/`: instalacion, manuales y evidencias.

## Aplicacion

La aplicacion usa PHP 8, Apache, MariaDB/MySQL y Ollama con `llama3.2`. Procesa PDF, DOCX y TXT, genera categoria, resumen y datos relevantes, y guarda los resultados en MySQL.

Consulta la instalacion completa en `05-implementacion/guia_instalacion.md`.# DocuMind

Sistema inteligente de gestión y análisis documental para el proyecto integrador de Desarrollo de Aplicaciones Empresariales.

## Stack

- PHP 8 con PDO y sesiones.
- Apache y MariaDB/MySQL mediante XAMPP.
- HTML/CSS/JavaScript sin compilación adicional.
- Extracción local para TXT, DOCX y PDF.
- Clasificación, resumen y extracción de datos con reglas reproducibles.
- IA local mediante Ollama y API compatible con OpenAI.

## Instalación local

1. Copia el proyecto en `C:\xampp\htdocs\DesarrolloE2`.
2. Inicia Apache y MySQL desde el panel de XAMPP.
3. En phpMyAdmin importa `database/schema.sql`, o ejecuta:
   `C:\xampp\mysql\bin\mysql.exe -u root < database\schema.sql`
4. Revisa `config/config.php` si tu usuario o contraseña de MySQL son distintos.
5. Abre `http://localhost/DesarrolloE2/`.

Usuario inicial: `admin@documind.local`  
Contraseña inicial: `password`

## IA generativa 

El procesamiento está preparado para usar una API compatible con OpenAI. Para activar la IA:

1. Descarga Ollama desde `https://ollama.com/download`.
2. Ejecuta `ollama pull llama3.2`.
3. DocuMind ya está configurado en `config/local.php` para usar Ollama en `127.0.0.1:11434`.
4. Carga un documento de prueba. Si la IA falla, el documento queda en estado `failed` y se registra el error.

La IA recibe el texto extraído y devuelve en JSON la categoría, tipo de documento, resumen y datos relevantes. Esos resultados se almacenan en MySQL y luego se utilizan en la consulta documental.

Nunca publiques la clave en Git. La aplicación guarda texto, categoría, resumen, datos extraídos, estado y registro de procesamiento en MySQL.

## Documentación académica

- [Análisis](docs/01-analisis.md)
- [Diseño](docs/02-diseno.md)
- [Desarrollo](docs/03-desarrollo.md)
- [Pruebas](docs/04-pruebas.md)
- [Implementación](docs/05-implementacion.md)
- [Matriz de trazabilidad](docs/06-trazabilidad.md)
