# I. Análisis

## Problema

La empresa conserva documentos en carpetas sin clasificación ni mecanismos para localizar rápidamente información. DocuMind convierte ese almacenamiento pasivo en un repositorio consultable.

## Objetivo

Gestionar documentos empresariales y procesarlos para obtener categoría, resumen, datos relevantes y respuestas a preguntas en lenguaje natural.

## Actores

- Administrador: administra repositorios, documentos y configuración.
- Usuario autenticado: carga, consulta, descarga y elimina documentos autorizados.

## Requisitos funcionales

RF-01 Autenticar usuarios con correo y contraseña.
RF-02 Crear repositorios.
RF-03 Cargar PDF, DOCX y TXT.
RF-04 Procesar automáticamente el contenido.
RF-05 Clasificar documentos en Finanzas, Recursos humanos, Operaciones o General.
RF-06 Generar resumen y extraer correos, fechas y valores.
RF-07 Buscar y preguntar sobre el contenido.
RF-08 Mostrar indicadores y estados de procesamiento.
RF-09 Eliminar documentos.
RF-10 Registrar eventos de procesamiento.

## No funcionales

Seguridad básica con sesiones, consultas preparadas, CSRF y secretos fuera del repositorio. La aplicación debe ejecutarse en XAMPP y permitir reproducir la base de datos mediante un script.

## Historia principal

Como usuario autenticado, quiero cargar un documento y recibir un resumen y una categoría para encontrar información empresarial sin revisar manualmente cada archivo.

**Criterios:** solo se aceptan extensiones permitidas; el documento queda almacenado; el estado termina en `completed` o `failed`; la ficha muestra resultado y fuente.

## Riesgos

La extracción de PDF escaneado requiere OCR; se mitiga documentando el alcance y dejando el punto de extensión para OCR. Una API externa puede no estar disponible; se mitiga con análisis local reproducible.