# II. Diseño

## Arquitectura

Navegador → Apache/PHP → PDO → MariaDB. Los archivos se almacenan fuera de la carpeta pública en `storage/documents`. El procesador extrae texto, clasifica, resume y guarda resultados. El adaptador IA opcional envía únicamente el contexto recuperado.

## Flujo

1. El usuario carga un archivo.
2. PHP valida extensión, tamaño y destino.
3. Se guarda el binario con nombre aleatorio.
4. Se extrae texto según el formato.
5. Se calculan categoría, resumen y datos relevantes.
6. MySQL guarda el resultado y el log.
7. La consulta recupera documentos por términos y genera una respuesta con fuentes.

## Modelo de datos

`users` 1:N `repositories`; `repositories` 1:N `documents`; `documents` 1:N `processing_logs`.

## Decisiones

PHP y MariaDB reducen dependencias y permiten ejecución directa en XAMPP. PDO y consultas preparadas protegen el acceso a datos. La estrategia local hace reproducible la sustentación; la API externa puede activarse por configuración.