<?php use App\Core\Auth; use App\Core\Csrf; $u = Auth::user(); ?>
<h1 class="text-2xl font-bold text-gray-900 mb-6">User Profile</h1>
<div class="max-w-2xl bg-white rounded-xl border shadow p-8">
  <div class="mb-6">
    <div class="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center text-white text-3xl font-bold"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
    <span class="inline-flex mt-3 px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-700 capitalize"><?= htmlspecialchars($u['role']) ?></span>
  </div>
  <form method="post" action="/profile" class="space-y-5">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
      <input name="name" required class="w-full border rounded-lg px-4 py-3 text-gray-900" value="<?= htmlspecialchars($u['name']) ?>">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
      <input type="email" name="email" required class="w-full border rounded-lg px-4 py-3 text-gray-900" value="<?= htmlspecialchars($u['email']) ?>">
    </div>
    <hr class="my-6">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">New password (optional)</label>
      <input type="password" name="password" minlength="6" class="w-full border rounded-lg px-4 py-3 text-gray-900">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
      <input type="password" name="confirm_password" minlength="6" class="w-full border rounded-lg px-4 py-3 text-gray-900">
    </div>
    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium">Save</button>
  </form>
</div>
