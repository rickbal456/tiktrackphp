<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
  <title><?= htmlspecialchars($title ?? 'TikTrack') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-gray-50 flex" x-data="{ sidebarOpen: false }">
  <?php require base_path('app/views/partials/toast.php'); ?>
  <?php require base_path('app/views/partials/sidebar.php'); ?>
  <div class="flex-1 flex flex-col min-w-0 w-full">
    <button type="button" @click="sidebarOpen = true" class="lg:hidden fixed top-3 left-3 z-50 p-2 rounded-lg bg-gray-900 text-white shadow-lg border border-gray-700/50">Menu</button>
    <main class="flex-1 p-4 pt-14 lg:pt-6 lg:p-6 overflow-auto">
      <?php require base_path('app/views/' . str_replace('.', '/', $__innerView) . '.php'); ?>
    </main>
    <footer class="py-3 px-4 text-center text-xs text-gray-400 border-t border-gray-200 bg-white">
      &copy; <?= date('Y') ?> PT Goami Bagjatama Indonesia. All rights reserved.
    </footer>
  </div>
</body>
</html>
