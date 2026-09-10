# IV. Pruebas

| ID | Caso | Resultado esperado |
|---|---|---|
| CP-01 | Iniciar sesión válido | Acceso al dashboard |
| CP-02 | Contraseña inválida | Mensaje de error y sin acceso |
| CP-03 | Crear repositorio | Repositorio visible |
| CP-04 | Cargar TXT | Estado completado |
| CP-05 | Cargar DOCX | Texto y resumen almacenados |
| CP-06 | Cargar PDF | Archivo aceptado y procesado |
| CP-07 | Extensión no permitida | Archivo rechazado |
| CP-08 | Archivo mayor de 10 MB | Archivo rechazado |
| CP-09 | Pregunta relacionada | Respuesta con fuente |
| CP-10 | Pregunta sin coincidencias | Mensaje de no encontrado |
| CP-11 | CSRF inválido | Solicitud rechazada |
| CP-12 | Eliminar documento | Documento deja de aparecer |

Las evidencias deben capturarse durante ejecuciones reales y completar las columnas fecha, datos usados, resultado obtenido, evidencia y defecto asociado.