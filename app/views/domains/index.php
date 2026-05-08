<?php
/** @var array<int, array<string, mixed>> $domains */
use App\Core\Csrf;
$cfg = config('app');
$local = ($cfg['next_public_domain_verify_local_mode'] ?? false) === true;
?>
<div class="space-y-6">
  <div>
    <h1 class="text-2xl font-bold text-gray-900">Custom Domains</h1>
    <p class="text-gray-500 mt-1">Add domains, verify DNS, attach to landing pages.</p>
  </div>

  <div class="bg-white rounded-xl shadow p-6 space-y-3">
    <h2 class="text-lg font-semibold">Add Domain</h2>
    <form method="post" action="/domains" class="flex flex-col sm:flex-row gap-3">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
      <input name="domain" class="flex-1 px-3 py-2 border rounded-lg text-gray-900" placeholder="example.com" required>
      <button class="px-4 py-2 bg-cyan-600 text-white rounded-lg">Add Domain</button>
    </form>
    <div class="text-xs text-gray-500 bg-gray-50 p-3 rounded-lg">
      <p>Set A record to: <span class="font-mono"><?= htmlspecialchars($cfg['next_public_vps_ip'] ?: '[VPS_IP]') ?></span></p>
      <p>Cloudflare proxy ON, SSL mode <strong>Full</strong>.</p>
      <?php if ($local): ?><p class="text-amber-700 font-medium">Local verify mode is on.</p><?php endif; ?>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Domain</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">LP links</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Error</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <?php if ($domains === []): ?>
        <tr><td colspan="5" class="px-6 py-10 text-center text-gray-400">No domains yet.</td></tr>
        <?php else: foreach ($domains as $d): ?>
        <tr>
          <td class="px-6 py-4 font-medium"><?= htmlspecialchars((string) $d['domain']) ?></td>
          <td class="px-6 py-4"><span class="text-xs px-2 py-1 rounded-full <?= ($d['status'] ?? '') === 'verified' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-800' ?>"><?= htmlspecialchars((string) ($d['status'] ?? '')) ?></span></td>
          <td class="px-6 py-4 text-sm"><?= (int) ($d['lp_count'] ?? 0) ?></td>
          <td class="px-6 py-4 text-xs text-gray-500 max-w-xs truncate"><?= htmlspecialchars((string) ($d['verification_error'] ?? '-')) ?></td>
          <td class="px-6 py-4 flex gap-2">
            <form method="post" action="/domains/verify"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>"><input type="hidden" name="id" value="<?= htmlspecialchars((string) $d['id']) ?>"><button class="text-cyan-700 text-sm">Verify</button></form>
            <form method="post" action="/domains/delete" onsubmit="return confirm('Delete?');"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>"><input type="hidden" name="id" value="<?= htmlspecialchars((string) $d['id']) ?>"><button class="text-red-600 text-sm">Delete</button></form>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
