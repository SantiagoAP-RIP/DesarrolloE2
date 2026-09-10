<?php

require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/document_processor.php';

$page = $_GET['page'] ?? (current_user() ? 'dashboard' : 'login');

if ($page === 'health') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'app' => $config['app_name']]);
    exit;
}

if ($page === 'download') {
    require_auth();
    $statement = $db->prepare('SELECT d.* FROM documents d JOIN repositories r ON r.id = d.repository_id WHERE d.id = ? AND r.user_id = ? LIMIT 1');
    $statement->execute([(int) ($_GET['id'] ?? 0), current_user()['id']]);
    $document = $statement->fetch();
    $path = $document ? $config['upload_dir'] . DIRECTORY_SEPARATOR . $document['stored_name'] : '';
    if (!$document || !is_file($path)) {
        http_response_code(404);
        exit('Documento no encontrado.');
    }
    header('Content-Type: ' . $document['mime_type']);
    header('Content-Disposition: attachment; filename="' . basename($document['original_name']) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $statement = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $statement->execute([trim($_POST['email'] ?? '')]);
    $user = $statement->fetch();
    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        redirect('/');
    }
    $error = 'Correo o contraseña incorrectos.';
}

if ($page === 'logout') {
    session_destroy();
    redirect('/?page=login');
}

if ($page === 'dashboard') {
    require_auth();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $action = $_POST['action'] ?? '';
        if ($action === 'create_repository') {
            $statement = $db->prepare('INSERT INTO repositories (user_id, name, description) VALUES (?, ?, ?)');
            $statement->execute([current_user()['id'], trim($_POST['name'] ?? ''), trim($_POST['description'] ?? '')]);
            redirect('/');
        }
        if ($action === 'upload') {
            $file = $_FILES['document'] ?? null;
            $repositoryId = (int) ($_POST['repository_id'] ?? 0);
            $allowed = ['pdf', 'docx', 'txt'];
            $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
            $repositoryStatement = $db->prepare('SELECT id FROM repositories WHERE id = ? AND user_id = ?');
            $repositoryStatement->execute([$repositoryId, current_user()['id']]);
            if (!$file || !$repositoryStatement->fetchColumn() || $file['error'] !== UPLOAD_ERR_OK || !in_array($extension, $allowed, true) || $file['size'] > $config['max_upload_bytes']) {
                $error = 'Archivo inválido. Usa PDF, DOCX o TXT de máximo 10 MB.';
            } else {
                $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
                move_uploaded_file($file['tmp_name'], $config['upload_dir'] . DIRECTORY_SEPARATOR . $storedName);
                $statement = $db->prepare('INSERT INTO documents (repository_id, original_name, stored_name, mime_type, extension, size_bytes, processing_status) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $statement->execute([$repositoryId, $file['name'], $storedName, $file['type'], $extension, $file['size'], 'processing']);
                try {
                    process_document($db, $config, (int) $db->lastInsertId());
                    redirect('/');
                } catch (Throwable $exception) {
                    $error = 'El archivo se cargó, pero no pudo procesarse: ' . $exception->getMessage();
                }
            }
        }
        if ($action === 'delete_document') {
            $statement = $db->prepare('DELETE d FROM documents d JOIN repositories r ON r.id = d.repository_id WHERE d.id = ? AND r.user_id = ?');
            $statement->execute([(int) $_POST['document_id'], current_user()['id']]);
            redirect('/');
        }
        if ($action === 'delete_repository') {
            $statement = $db->prepare('DELETE FROM repositories WHERE id = ? AND user_id = ?');
            $statement->execute([(int) $_POST['repository_id'], current_user()['id']]);
            redirect('/');
        }
        if ($action === 'ask') {
            $questionResult = answer_question($db, $config, trim($_POST['question'] ?? ''));
        }
    }

    $repositories = $db->prepare('SELECT r.*, COUNT(d.id) AS document_count FROM repositories r LEFT JOIN documents d ON d.repository_id = r.id WHERE r.user_id = ? GROUP BY r.id ORDER BY r.created_at DESC');
    $repositories->execute([current_user()['id']]);
    $repositories = $repositories->fetchAll();
    $documentsStatement = $db->prepare('SELECT d.*, r.name AS repository_name FROM documents d JOIN repositories r ON r.id = d.repository_id WHERE r.user_id = ? ORDER BY d.created_at DESC LIMIT 20');
    $documentsStatement->execute([current_user()['id']]);
    $documents = $documentsStatement->fetchAll();
    $statsStatement = $db->prepare("SELECT COUNT(*) AS total, SUM(d.processing_status = 'completed') AS processed, COUNT(DISTINCT d.category) AS categories FROM documents d JOIN repositories r ON r.id = d.repository_id WHERE r.user_id = ?");
    $statsStatement->execute([current_user()['id']]);
    $stats = $statsStatement->fetch();
    $searchQuery = trim($_GET['q'] ?? '');
    $searchResults = [];
    if ($searchQuery !== '') {
        $searchResults = answer_question($db, $config, $searchQuery);
    }
}
?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($config['app_name']) ?></title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; }
        body { margin: 0; background: #f3f6f8; color: #14212b; }
        main { max-width: 1180px; margin: 0 auto; padding: 36px 20px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 24px; }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin: 18px 0; }
        .layout { display: grid; grid-template-columns: 1fr 2fr; gap: 18px; align-items: start; }
        .metric { font-size: 30px; font-weight: 800; color: #126782; }
        .muted { color: #61737c; }
        table { width: 100%; border-collapse: collapse; } th, td { padding: 12px 8px; text-align: left; border-bottom: 1px solid #e3eaed; }
        form.inline { display: inline; } .danger { background: #9d3939; padding: 7px 10px; }
        @media (max-width: 720px) { .layout, .grid { grid-template-columns: 1fr; } .topbar { align-items: flex-start; flex-direction: column; } }
        .panel { background: white; border: 1px solid #dbe4e8; border-radius: 12px; padding: 28px; box-shadow: 0 8px 24px #19364212; }
        input, button { font: inherit; padding: 11px 13px; border-radius: 7px; border: 1px solid #c7d4da; }
        button { background: #126782; color: white; border: 0; cursor: pointer; }
        label { display: block; margin: 14px 0 6px; font-weight: 600; }
        .error { color: #a62929; margin: 12px 0; }
    </style>
</head>
<body><main>
<?php if ($page === 'login'): ?>
    <section class="panel">
        <h1>DocuMind</h1>
        <p>Gestión y análisis inteligente de documentos empresariales.</p>
        <?php if (!empty($error)): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <label for="email">Correo</label>
            <input id="email" name="email" type="email" required autocomplete="email">
            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
            <p><button type="submit">Iniciar sesión</button></p>
        </form>
    </section>
<?php else: ?>
    <div class="topbar"><div><h1>DocuMind</h1><span class="muted">Centro inteligente de documentos</span></div><a href="?page=logout">Cerrar sesión</a></div>
    <?php if (!empty($error)): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <div class="grid">
        <section class="panel"><span class="muted">Documentos</span><div class="metric"><?= (int) ($stats['total'] ?? 0) ?></div></section>
        <section class="panel"><span class="muted">Procesados</span><div class="metric"><?= (int) ($stats['processed'] ?? 0) ?></div></section>
        <section class="panel"><span class="muted">Categorías detectadas</span><div class="metric"><?= (int) ($stats['categories'] ?? 0) ?></div></section>
    </div>
    <section class="panel" style="margin-bottom:18px"><h2>Consultar documentos</h2><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="ask"><label for="question">Pregunta en lenguaje natural</label><input id="question" name="question" style="width:70%" placeholder="¿Qué documentos mencionan pagos o contratos?" required><button>Preguntar</button></form><?php if (!empty($questionResult)): ?><p><strong>Respuesta:</strong> <?= e($questionResult['answer']) ?></p><p class="muted">Fuentes: <?= e(implode(', ', $questionResult['sources'])) ?></p><?php endif; ?></section>
    <div class="layout">
        <div>
            <section class="panel"><h2>Repositorios</h2><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create_repository"><label for="repo-name">Nombre</label><input id="repo-name" name="name" required><label for="repo-description">Descripción</label><input id="repo-description" name="description"><p><button>Crear repositorio</button></p></form><?php foreach ($repositories as $repository): ?><hr><strong><?= e($repository['name']) ?></strong><span class="muted"> (<?= (int) $repository['document_count'] ?> documentos)</span><form class="inline" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_repository"><input type="hidden" name="repository_id" value="<?= (int) $repository['id'] ?>"><button class="danger" title="Eliminar repositorio">Eliminar</button></form><?php endforeach; ?></section>
            <section class="panel"><h2>Cargar documento</h2><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="upload"><label for="repository">Repositorio</label><select id="repository" name="repository_id" required><?php foreach ($repositories as $repository): ?><option value="<?= (int) $repository['id'] ?>"><?= e($repository['name']) ?></option><?php endforeach; ?></select><label for="document">Archivo</label><input id="document" name="document" type="file" accept=".pdf,.docx,.txt" required><p><button>Procesar documento</button></p></form></section>
        </div>
        <section class="panel"><h2>Documentos recientes</h2><?php if (!$documents): ?><p class="muted">Todavía no hay documentos cargados.</p><?php else: ?><table><thead><tr><th>Archivo</th><th>Categoría</th><th>Estado</th><th>Resumen y extracción</th><th></th></tr></thead><tbody><?php foreach ($documents as $document): ?><?php $extracted = json_decode($document['extracted_data'] ?: '{}', true) ?: []; ?><tr><td><?= e($document['original_name']) ?><br><small class="muted"><?= e($document['repository_name']) ?></small></td><td><?= e($document['category'] ?: 'Pendiente') ?><br><small class="muted"><?= e($extracted['document_type'] ?? 'General') ?></small></td><td><?= e($document['processing_status']) ?><?php if ($document['processing_error']): ?><br><small class="error"><?= e($document['processing_error']) ?></small><?php endif; ?></td><td><?= e(mb_substr($document['summary'] ?: 'Sin resumen', 0, 120)) ?><br><small class="muted">Correos: <?= count($extracted['emails'] ?? []) ?> | Fechas: <?= count($extracted['dates'] ?? []) ?> | Valores: <?= count($extracted['amounts'] ?? []) ?></small></td><td><a href="?page=download&amp;id=<?= (int) $document['id'] ?>" title="Descargar">Descargar</a><form class="inline" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_document"><input type="hidden" name="document_id" value="<?= (int) $document['id'] ?>"><button class="danger" title="Eliminar">Eliminar</button></form></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></section>
    </div>
<?php endif; ?>
</main></body>
</html>