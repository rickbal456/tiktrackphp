<?php
/** @var array<int, array<string, mixed>> $configs */
/** @var bool $isAdmin */
use App\Core\Csrf;
$events = ['Purchase', 'CompletePayment', 'AddToCart', 'ViewContent', 'AddToWishlist', 'InitiateCheckout', 'PlaceAnOrder', 'CompleteRegistration', 'Contact', 'SubmitForm', 'Subscribe', 'Download'];
$edit = $_GET['edit'] ?? null;
$editRow = null;
if ($edit) {
  foreach ($configs as $c) {
    if ($c['id'] === $edit) {
      $editRow = $c;
      break;
    }
  }
}
?>
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold text-gray-900">TikTok Configuration</h1>
  <a href="/config<?= $editRow ? '' : '?new=1' ?>" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"><?= $editRow || isset($_GET['new']) ? 'Cancel' : 'Add Config' ?></a>
</div>

<?php if ($editRow || isset($_GET['new'])): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
  <h2 class="text-lg font-semibold mb-4"><?= $editRow ? 'Edit' : 'New' ?> Configuration</h2>
  <form method="post" action="<?= $editRow ? '/config/update' : '/config' ?>" class="space-y-4">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <?php if ($editRow): ?><input type="hidden" name="id" value="<?= htmlspecialchars($editRow['id']) ?>"><?php endif; ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">TikTok Access Token</label>
      <input type="text" name="access_token" required class="w-full px-4 py-3 border rounded-lg font-mono text-sm text-gray-900" value="<?= $editRow ? htmlspecialchars((string) $editRow['access_token']) : '' ?>">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Pixel ID</label>
      <input type="text" name="pixel_id" required class="w-full px-4 py-3 border rounded-lg text-gray-900" value="<?= $editRow ? htmlspecialchars((string) $editRow['pixel_id']) : '' ?>">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Default Event Name</label>
      <select name="event_name" class="w-full px-4 py-3 border rounded-lg text-gray-900">
        <?php foreach ($events as $ev): ?>
        <option value="<?= htmlspecialchars($ev) ?>" <?= ($editRow && ($editRow['event_name'] ?? '') === $ev) ? 'selected' : '' ?>><?= htmlspecialchars($ev) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg"><?= $editRow ? 'Update' : 'Create' ?></button>
  </form>
</div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
  <table class="w-full">
    <thead class="bg-gray-50">
      <tr>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pixel ID</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Access Token</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
        <?php if ($isAdmin): ?><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th><?php endif; ?>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-200">
      <?php if ($configs === []): ?>
      <tr><td colspan="<?= $isAdmin ? 6 : 5 ?>" class="px-6 py-8 text-center text-gray-500">No configurations yet.</td></tr>
      <?php else: foreach ($configs as $cfg):
        $tok = (string) $cfg['access_token'];
        $mask = strlen($tok) <= 8 ? '****' : substr($tok, 0, 4) . '****' . substr($tok, -4);
      ?>
      <tr class="hover:bg-gray-50">
        <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= htmlspecialchars((string) $cfg['pixel_id']) ?></td>
        <td class="px-6 py-4 text-sm text-gray-500 font-mono"><span class="token-mask"><?= htmlspecialchars($mask) ?></span><button type="button" class="ml-2 text-xs text-blue-600 token-toggle" data-full="<?= htmlspecialchars($tok) ?>">show</button></td>
        <td class="px-6 py-4 text-sm"><?= htmlspecialchars((string) $cfg['event_name']) ?></td>
        <?php if ($isAdmin): ?><td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars((string) ($cfg['user_name'] ?? '')) ?></td><?php endif; ?>
        <td class="px-6 py-4">
          <form method="post" action="/config/toggle" class="inline">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string) $cfg['id']) ?>">
            <button type="submit" class="text-xs px-2 py-1 rounded-full <?= (int)$cfg['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>"><?= (int)$cfg['is_active'] ? 'Active' : 'Inactive' ?></button>
          </form>
        </td>
        <td class="px-6 py-4 flex gap-2">
          <a href="/config?edit=<?= urlencode((string) $cfg['id']) ?>" class="text-blue-600">Edit</a>
          <form method="post" action="/config/delete" onsubmit="return confirm('Delete?');" class="inline">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string) $cfg['id']) ?>">
            <button type="submit" class="text-red-600">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<script>
document.querySelectorAll('.token-toggle').forEach(btn => {
  btn.addEventListener('click', () => {
    const cell = btn.closest('td');
    const span = cell.querySelector('.token-mask');
    const full = btn.dataset.full;
    if (btn.textContent === 'show') { span.textContent = full; btn.textContent = 'hide'; }
    else { span.textContent = full.slice(0,4)+'****'+full.slice(-4); btn.textContent = 'show'; }
  });
});
</script>
