<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'TikTrack') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-full flex flex-col bg-gradient-to-br from-gray-950 via-gray-900 to-gray-950">
  <div class="flex-1 flex items-center justify-center py-8">
    <?php require base_path('app/views/' . str_replace('.', '/', $__innerView) . '.php'); ?>
  </div>
  <footer class="py-4 text-center text-xs text-gray-500">
    &copy; <?= date('Y') ?> PT Goami Bagjatama Indonesia. All rights reserved.
  </footer>
</body>
</html>
