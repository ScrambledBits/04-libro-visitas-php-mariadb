<?php

declare(strict_types=1);

require __DIR__ . '/db.php';

$error = null;
$mensajes = [];

try {
    $pdo = conexion();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $autor = trim($_POST['autor'] ?? '');
        $texto = trim($_POST['texto'] ?? '');

        if ($autor === '' || $texto === '') {
            $error = 'El nombre y el mensaje son obligatorios.';
        } else {
            $sentencia = $pdo->prepare('INSERT INTO mensajes (autor, texto) VALUES (?, ?)');
            $sentencia->execute([mb_substr($autor, 0, 60), mb_substr($texto, 0, 500)]);
            header('Location: /', true, 303);
            exit;
        }
    }

    $mensajes = $pdo->query('SELECT autor, texto, creado_en FROM mensajes ORDER BY id DESC LIMIT 50')->fetchAll();
} catch (PDOException $e) {
    http_response_code(503);
    $error = 'No se pudo conectar a la base de datos: ' . $e->getMessage();
}

function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Libro de visitas</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; }
    form { display: grid; gap: .5rem; margin-bottom: 2rem; }
    input, textarea { padding: .5rem; font: inherit; }
    .mensaje { border-left: 4px solid #4a7; padding: .5rem 1rem; margin-bottom: 1rem; background: #f7f7f7; }
    .meta { color: #666; font-size: .85rem; }
    .error { color: #b00020; }
    footer { margin-top: 2rem; color: #888; font-size: .8rem; }
  </style>
</head>
<body>
  <h1>Libro de visitas</h1>

  <?php if ($error): ?>
    <p class="error"><?= e($error) ?></p>
  <?php endif; ?>

  <form method="post">
    <input name="autor" maxlength="60" placeholder="Tu nombre o apodo" required>
    <textarea name="texto" maxlength="500" rows="3" placeholder="Deja un mensaje" required></textarea>
    <button>Firmar</button>
  </form>

  <?php foreach ($mensajes as $m): ?>
    <div class="mensaje">
      <div><?= nl2br(e($m['texto'])) ?></div>
      <div class="meta">— <?= e($m['autor']) ?>, <?= e($m['creado_en']) ?></div>
    </div>
  <?php endforeach; ?>

  <footer>PHP <?= e(PHP_VERSION) ?> · servidor <?= e(gethostname() ?: '?') ?></footer>
</body>
</html>
