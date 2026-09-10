# Especificacion funcional

La aplicacion DocuMind gestiona repositorios empresariales y procesa documentos PDF, DOCX y TXT. El usuario autenticado puede crear repositorios, cargar archivos, descargarlos, eliminarlos y consultar su contenido.

## Flujo principal

Archivo -> extraccion de texto -> Ollama/IA local -> categoria, tipo, resumen y datos relevantes -> almacenamiento MySQL -> busqueda y preguntas.

## Requisitos implementados

- Autenticacion y sesiones.
- Repositorios con aislamiento por usuario.
- Carga y descarga de documentos.
- Procesamiento con IA local.
- Clasificacion, resumen y extraccion.
- Busqueda y preguntas con fuentes.
- Dashboard, estados y logs.