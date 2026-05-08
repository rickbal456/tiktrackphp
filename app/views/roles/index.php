<?php /** @var array $roles */ /** @var array $users */ use App\Core\Csrf; ?>
<div class="flex justify-between items-center mb-6">
  <h1 class="text-2xl font-bold text-gray-900">Role Management</h1>
</div>
<div class="bg-white rounded-xl border shadow p-6 mb-6">
  <h2 class="font-semibold mb-3">Add user/admin</h2>
  <form method="post" action="/roles/users" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div>
      <label class="text-sm text-gray-600">Nama</label>
      <input name="name" required class="border rounded-lg px-3 py-2 text-gray-900 block mt-1 w-full" placeholder="Nama lengkap">
    </div>
    <div>
      <label class="text-sm text-gray-600">Email</label>
      <input type="email" name="email" required class="border rounded-lg px-3 py-2 text-gray-900 block mt-1 w-full" placeholder="user@domain.com">
    </div>
    <div>
      <label class="text-sm text-gray-600">Password</label>
      <input type="password" name="password" required minlength="6" class="border rounded-lg px-3 py-2 text-gray-900 block mt-1 w-full">
    </div>
    <div>
      <label class="text-sm text-gray-600">Role</label>
      <select name="role" class="border rounded-lg px-3 py-2 text-gray-900 block mt-1 w-full">
        <option value="user">User</option>
        <option value="admin">Admin</option>
      </select>
    </div>
    <div class="md:col-span-4">
      <button type="submit" class="px-4 py-2 bg-cyan-600 text-white rounded-lg">Create User</button>
    </div>
  </form>
</div>
<div class="bg-white rounded-xl border shadow overflow-x-auto">
  <h2 class="font-semibold px-4 pt-4 pb-2">Daftar User</h2>
  <table class="w-full text-sm">
    <thead class="bg-gray-50">
      <tr>
        <th class="px-4 py-2 text-left">Nama</th>
        <th class="px-4 py-2 text-left">Email</th>
        <th class="px-4 py-2 text-left">Role</th>
        <th class="px-4 py-2 text-left">Dibuat</th>
        <th class="px-4 py-2 text-left">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      <?php foreach ($users as $u): ?>
      <tr>
        <td class="px-4 py-2 text-gray-900"><?= htmlspecialchars((string)$u['name']) ?></td>
        <td class="px-4 py-2 text-gray-600"><?= htmlspecialchars((string)$u['email']) ?></td>
        <td class="px-4 py-2"><span class="px-2 py-1 rounded-full text-xs capitalize <?= $u['role'] === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' ?>"><?= htmlspecialchars((string)$u['role']) ?></span></td>
        <td class="px-4 py-2 text-gray-500 text-xs"><?= htmlspecialchars((string)$u['created_at']) ?></td>
        <td class="px-4 py-2">
          <form method="post" action="/roles/users/delete" class="inline" onsubmit="return confirm('Hapus user ini?');">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$u['id']) ?>">
            <button class="text-red-600 hover:text-red-800">Hapus</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
