<?php
/** @var array{id:string,name:string,email:string,role:string}|null $user */
$u = $user ?? null;
$role = $u['role'] ?? 'user';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isActive = fn(string $href): string => $path === $href ? 'bg-cyan-500/15 text-cyan-400 border border-cyan-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white';
?>
<div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="sidebarOpen = false" x-cloak></div>
<aside
  class="fixed inset-y-0 left-0 z-40 flex flex-col bg-gray-900 text-white transition-transform duration-300 ease-in-out w-64 lg:translate-x-0 lg:static lg:z-auto"
  :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
>
  <div class="flex items-center justify-between p-3 border-b border-gray-700/50">
    <div class="flex items-center gap-2.5">
      <img src="/assets/images/tiktrack-icon.png" alt="TikTrack" class="h-9 w-9 object-contain shrink-0">
      <div class="leading-tight">
        <span class="text-sm font-bold text-white tracking-wide">TikTrack</span>
        <span class="block text-[10px] text-gray-400">Tiktok Analytics &amp; Tracking</span>
      </div>
    </div>
    <button type="button" @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white p-1">✕</button>
  </div>
  <nav class="flex-1 p-2 overflow-y-auto">
    <p class="px-3 pt-2 pb-1 text-[10px] font-semibold uppercase tracking-wider text-gray-500">Main</p>
    <div class="space-y-0.5">
      <a href="/dashboard" @click="sidebarOpen=false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?= $isActive('/dashboard') ?>">Dashboard</a>
      <a href="/landing-pages" @click="sidebarOpen=false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?= $isActive('/landing-pages') ?>">Landing Pages</a>
      <a href="/domains" @click="sidebarOpen=false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?= $isActive('/domains') ?>">Custom Domains</a>
      <a href="/conversions" @click="sidebarOpen=false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?= $isActive('/conversions') ?>">Conversions</a>
      <a href="/config" @click="sidebarOpen=false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?= $isActive('/config') ?>">TikTok Config</a>
    </div>
    <p class="px-3 pt-4 pb-1 text-[10px] font-semibold uppercase tracking-wider text-gray-500">Setting</p>
    <div class="space-y-0.5">
      <a href="/profile" @click="sidebarOpen=false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?= $isActive('/profile') ?>">User Profile</a>
      <?php if ($role === 'admin'): ?>
      <a href="/roles" @click="sidebarOpen=false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?= $isActive('/roles') ?>">Role Management</a>
      <?php endif; ?>
    </div>
  </nav>
  <div class="p-2 border-t border-gray-700/50">
    <form method="post" action="/logout" class="px-3">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
      <button type="submit" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-400 hover:bg-red-500/10 hover:text-red-400 w-full transition text-sm text-left">Logout</button>
    </form>
  </div>
</aside>
