# Historias de usuario

## HU-01 - Autenticacion

Como usuario empresarial quiero iniciar sesion para acceder de forma segura a mis repositorios.

**Criterios:** credenciales validas permiten el acceso; credenciales invalidas muestran error; las operaciones requieren CSRF.

## HU-02 - Procesamiento documental

Como usuario quiero cargar un PDF, DOCX o TXT para obtener categoria, resumen y datos relevantes mediante IA.

**Criterios:** el archivo se valida, se extrae su texto, se procesa con Ollama y los resultados quedan guardados en MySQL.

## HU-03 - Consulta

Como usuario quiero preguntar sobre mis documentos para encontrar informacion sin leerlos uno por uno.

**Criterios:** la respuesta muestra fuentes; cuando no hay coincidencias se informa claramente.