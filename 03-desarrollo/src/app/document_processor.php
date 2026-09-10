<?php

declare(strict_types=1);

function extract_document_text(string $path, string $extension): string
{
    if ($extension === 'txt') {
        return (string) file_get_contents($path);
    }

    if ($extension === 'docx' && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($path) === true) {
            $xml = $zip->getFromName('word/document.xml') ?: '';
            $zip->close();
            return trim(html_entity_decode(strip_tags(str_replace('</w:p>', "\n", $xml))));
        }
    }

    if ($extension === 'pdf') {
        return extract_pdf_text($path);
    }

    return '';
}

function extract_pdf_text(string $path): string
{
    $raw = (string) file_get_contents($path);
    $text = [];
    preg_match_all('/stream\s*\r?\n(.*?)\r?\nendstream/s', $raw, $streams);
    foreach ($streams[1] ?? [] as $stream) {
        $decoded = @gzuncompress($stream);
        if ($decoded === false) {
            $decoded = @zlib_decode($stream);
        }
        if ($decoded === false || $decoded === null) {
            $decoded = $stream;
        }
        preg_match_all('/\((?:\\.|[^\\)])*\)/s', $decoded, $matches);
        foreach ($matches[0] ?? [] as $match) {
            $value = substr($match, 1, -1);
            $value = str_replace(['\\(', '\\)', '\\\\', '\\n', '\\r', '\\t'], ['(', ')', '\\', ' ', ' ', ' '], $value);
            if (preg_match('/[A-Za-zÁÉÍÓÚáéíóúÑñ]{2,}/u', $value)) {
                $text[] = $value;
            }
        }
    }
    $result = trim(preg_replace('/\s+/', ' ', implode(' ', $text)) ?? '');
    return mb_substr($result, 0, 12000);
}

function classify_document(string $text, string $fileName): string
{
    $haystack = strtolower($text . ' ' . $fileName);
    $rules = [
        'Finanzas' => ['factura', 'presupuesto', 'pago', 'contabilidad', 'financiero', 'proveedor'],
        'Recursos humanos' => ['empleado', 'contrato', 'nomina', 'nómina', 'vacaciones', 'talento'],
        'Operaciones' => ['proceso', 'inventario', 'logistica', 'logística', 'informe', 'procedimiento'],
    ];
    foreach ($rules as $category => $keywords) {
        foreach ($keywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return $category;
            }
        }
    }
    return 'General';
}

function summarize_document(string $text, string $fileName): string
{
    $clean = trim(preg_replace('/\s+/', ' ', $text) ?? '');
    if ($clean === '') {
        return 'No se pudo extraer texto legible de este archivo.';
    }
    $sentences = preg_split('/(?<=[.!?])\s+/', $clean) ?: [$clean];
    $summary = implode(' ', array_slice($sentences, 0, 3));
    return 'Resumen automático de ' . $fileName . ': ' . mb_substr($summary, 0, 700);
}

function extract_relevant_data(string $text, string $category): array
{
    $data = ['category' => $category, 'document_type' => 'General'];
    $patterns = [
        'emails' => '/[\w.+-]+@[\w-]+\.[\w.-]+/',
        'dates' => '/\b\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}\b/',
        'amounts' => '/(?:\$|USD|COP)\s?[\d.,]+/i',
    ];
    foreach ($patterns as $key => $pattern) {
        preg_match_all($pattern, $text, $matches);
        $data[$key] = array_values(array_unique($matches[0] ?? []));
    }
    $haystack = strtolower($text);
    if (preg_match('/factura|invoice|proveedor|subtotal|impuesto|iva/', $haystack)) {
        $data['document_type'] = 'Factura';
        $data['invoice_numbers'] = extract_matches($text, '/(?:factura|invoice|n[úu]mero)\s*[:#-]?\s*([A-Z0-9-]+)/i');
        $data['suppliers'] = extract_matches($text, '/(?:proveedor|supplier)\s*[:#-]?\s*([^\n,;]+)/i');
    } elseif (preg_match('/contrato|contract|obligaci[oó]n|cl[aá]usula/', $haystack)) {
        $data['document_type'] = 'Contrato';
        $data['parties'] = extract_matches($text, '/(?:entre|partes|empresa)\s*[:#-]?\s*([^\n.;]+)/i');
        $data['contract_dates'] = $data['dates'];
    } elseif (preg_match('/informe|report|conclusi[oó]n|indicador|resultado/', $haystack)) {
        $data['document_type'] = 'Informe';
        $data['responsibles'] = extract_matches($text, '/(?:responsable|elaborado por|autor)\s*[:#-]?\s*([^\n,;]+)/i');
        $data['conclusions'] = extract_matches($text, '/(?:conclusi[oó]n|conclusiones)\s*[:#-]?\s*([^\n]+)/i');
    }
    return $data;
}

function extract_matches(string $text, string $pattern): array
{
    preg_match_all($pattern, $text, $matches);
    return array_values(array_unique(array_map('trim', $matches[1] ?? [])));
}

function request_ai_analysis(array $config, string $fileName, string $text): array
{
    if (!$config['ai']['enabled'] || !$config['ai']['api_key']) {
        if ($config['ai']['required']) {
            throw new RuntimeException('La IA es obligatoria, pero falta la API key en config/local.php.');
        }
        return [];
    }

    $prompt = <<<PROMPT
Analiza el documento empresarial siguiente y responde únicamente con JSON válido, sin markdown.
Estructura exacta:
{"category":"Finanzas|Recursos humanos|Operaciones|General","document_type":"Factura|Contrato|Informe|General","summary":"resumen en español de máximo 700 caracteres","extracted_data":{"invoice_number":"string o null","supplier":"string o null","parties":[],"responsible":"string o null","dates":[],"amounts":[]}}
No inventes información. Usa null o listas vacías cuando no exista.
Nombre del archivo: {$fileName}
Contenido:
{$text}
PROMPT;

    $payload = json_encode(['model' => $config['ai']['model'], 'messages' => [['role' => 'system', 'content' => 'Eres un analista documental empresarial preciso.'], ['role' => 'user', 'content' => $prompt]], 'temperature' => 0.1, 'response_format' => ['type' => 'json_object']], JSON_UNESCAPED_UNICODE);
    $handle = curl_init($config['ai']['endpoint']);
    curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $config['ai']['api_key']], CURLOPT_POSTFIELDS => $payload, CURLOPT_TIMEOUT => 60]);
    $rawResponse = curl_exec($handle);
    $curlError = curl_error($handle);
    $httpCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
    if ($rawResponse === false || $curlError || $httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException('La API de IA no respondió correctamente (HTTP ' . $httpCode . ').');
    }
    $response = json_decode($rawResponse, true);
    $analysisContent = trim((string) ($response['choices'][0]['message']['content'] ?? ''));
    $analysis = decode_ai_json($analysisContent);
    if (!is_array($analysis) || empty($analysis['summary']) || empty($analysis['category'])) {
        throw new RuntimeException('La IA respondió, pero no entregó los campos category y summary en JSON.');
    }
    $analysis['category'] = normalize_category((string) $analysis['category']);
    $analysis['document_type'] = normalize_document_type((string) ($analysis['document_type'] ?? 'General'));
    return $analysis;
}

function normalize_category(string $category): string
{
    $allowed = ['Finanzas', 'Recursos humanos', 'Operaciones', 'General'];
    foreach ($allowed as $value) {
        if (stripos($category, $value) !== false) {
            return $value;
        }
    }
    return 'General';
}

function normalize_document_type(string $documentType): string
{
    $allowed = ['Factura', 'Contrato', 'Informe', 'General'];
    foreach ($allowed as $value) {
        if (stripos($documentType, $value) !== false) {
            return $value;
        }
    }
    return 'General';
}

function decode_ai_json(string $content): ?array
{
    $content = trim($content);
    $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content;
    $decoded = json_decode(trim($content), true);
    if (is_array($decoded)) {
        return $decoded;
    }

    $start = strpos($content, '{');
    $end = strrpos($content, '}');
    if ($start === false || $end === false || $end <= $start) {
        return null;
    }
    $decoded = json_decode(substr($content, $start, $end - $start + 1), true);
    return is_array($decoded) ? $decoded : null;
}

function process_document(PDO $db, array $config, int $documentId): void
{
    $statement = $db->prepare('SELECT * FROM documents WHERE id = ?');
    $statement->execute([$documentId]);
    $document = $statement->fetch();
    if (!$document) {
        throw new RuntimeException('Documento no encontrado.');
    }
    $path = $config['upload_dir'] . DIRECTORY_SEPARATOR . $document['stored_name'];
    try {
        $text = extract_document_text($path, $document['extension']);
            if ($text === '') {
                throw new RuntimeException('No se pudo extraer texto legible del documento.');
            }
        $category = classify_document($text, $document['original_name']);
        $summary = summarize_document($text, $document['original_name']);
        $extracted = extract_relevant_data($text, $category);
        $aiAnalysis = request_ai_analysis($config, $document['original_name'], $text);
        if ($aiAnalysis) {
            $category = $aiAnalysis['category'];
            $summary = $aiAnalysis['summary'];
            $extracted = array_merge($extracted, $aiAnalysis['extracted_data'] ?? []);
            $extracted['document_type'] = $aiAnalysis['document_type'] ?? ($extracted['document_type'] ?? 'General');
        }
        $update = $db->prepare('UPDATE documents SET content = ?, category = ?, summary = ?, extracted_data = ?, processing_status = ?, processed_at = NOW() WHERE id = ?');
        $update->execute([$text, $category, $summary, json_encode($extracted, JSON_UNESCAPED_UNICODE), 'completed', $documentId]);
        $log = $db->prepare("INSERT INTO processing_logs (document_id, level, message) VALUES (?, 'info', ?)");
        $log->execute([$documentId, $aiAnalysis ? 'Procesamiento completado mediante IA generativa.' : 'Procesamiento local completado.']);
    } catch (Throwable $exception) {
        $update = $db->prepare('UPDATE documents SET processing_status = ?, processing_error = ?, processed_at = NOW() WHERE id = ?');
        $update->execute(['failed', $exception->getMessage(), $documentId]);
        $log = $db->prepare("INSERT INTO processing_logs (document_id, level, message) VALUES (?, 'error', ?)");
        $log->execute([$documentId, $exception->getMessage()]);
        throw $exception;
    }
}

function answer_question(PDO $db, array $config, string $question): array
{
    $terms = preg_split('/\s+/', strtolower(trim($question))) ?: [];
    $terms = array_values(array_filter($terms, static fn (string $term): bool => strlen($term) > 3));
    $where = [];
    $parameters = [];
    foreach (array_slice($terms, 0, 8) as $term) {
        $where[] = '(d.content LIKE ? OR d.summary LIKE ? OR d.original_name LIKE ?)';
        $like = '%' . $term . '%';
        array_push($parameters, $like, $like, $like);
    }
    $matches = [];
    if ($where) {
        $statement = $db->prepare('SELECT d.id, d.original_name, d.category, d.summary, d.content FROM documents d WHERE ' . implode(' OR ', $where) . ' ORDER BY d.processed_at DESC LIMIT 5');
        $statement->execute($parameters);
        $matches = $statement->fetchAll();
    }
    if (!$matches) {
        return ['answer' => 'No encontré documentos relacionados con esa pregunta.', 'sources' => []];
    }
    $context = implode("\n\n", array_map(static fn (array $document): string => $document['original_name'] . ': ' . ($document['content'] ?: $document['summary']), $matches));
    if ($config['ai']['enabled'] && $config['ai']['api_key']) {
        $payload = json_encode(['model' => $config['ai']['model'], 'messages' => [['role' => 'system', 'content' => 'Responde en español usando únicamente el contexto entregado. Si no existe la respuesta, dilo claramente.'], ['role' => 'user', 'content' => "Contexto:\n$context\n\nPregunta: $question"]], 'temperature' => 0.2]);
        $handle = curl_init($config['ai']['endpoint']);
        curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $config['ai']['api_key']], CURLOPT_POSTFIELDS => $payload, CURLOPT_TIMEOUT => 30]);
        $response = json_decode((string) curl_exec($handle), true);
        curl_close($handle);
        $answer = $response['choices'][0]['message']['content'] ?? null;
        if ($answer) {
            return ['answer' => $answer, 'sources' => array_column($matches, 'original_name')];
        }
    }
    $first = $matches[0];
    return ['answer' => 'Encontré información relacionada en "' . $first['original_name'] . '": ' . mb_substr($first['summary'] ?: $first['content'], 0, 600), 'sources' => array_column($matches, 'original_name')];
}