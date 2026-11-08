<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?? 'Admin' ?> - An Chapters</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
  <div style="width:250px;flex-shrink:0;">
    <?php include __DIR__ . '/sidebar.php'; ?>
  </div>
  <div class="flex-grow-1">
    <nav class="navbar bg-white shadow-sm px-4 py-3">
      <span class="fw-bold" style="color:var(--ac-brown);"><?= $page_title ?? 'Admin' ?></span>
      <span class="text-muted">Hai, <?= htmlspecialchars($_SESSION['nama']) ?> (Admin)</span>
    </nav>
    <div class="p-4">
