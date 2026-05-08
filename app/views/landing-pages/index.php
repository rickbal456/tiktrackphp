<?php
/** @var array $landingPages */
/** @var array $configs */
/** @var array $verifiedDomains */
use App\Core\Csrf;
$editId = $_GET['edit'] ?? null;
$editRow = null;
if ($editId) {
    foreach ($landingPages as $p) {
        if ((string) $p['id'] === (string) $editId) {
            $editRow = $p;
            break;
        }
    }
}
$showForm = isset($_GET['new']) || $editRow;
$eventOpts = ['ClickButton', 'CompletePayment', 'AddToCart', 'ViewContent', 'InitiateCheckout', 'PlaceAnOrder', 'CompleteRegistration', 'Contact', 'SubmitForm', 'Subscribe', 'Download'];
?>
<div class="flex justify-between items-center mb-6">
  <div>
    <h1 class="text-2xl font-bold text-gray-900">Landing Pages</h1>
    <p class="text-gray-500 text-sm mt-1">Redirect pages + TikTok tracking</p>
  </div>
  <a href="/landing-pages<?= $showForm ? '' : '?new=1' ?>" class="px-4 py-2 bg-blue-600 text-white rounded-lg"><?= $showForm ? 'Close' : 'New' ?></a>
</div>

<?php if ($showForm): ?>
<?php
$selDomains = [];
if ($editRow) {
    foreach ($editRow['domain_links'] ?? [] as $l) {
        $selDomains[] = (string) $l['custom_domain_id'];
    }
}
$maxSec = 6;
?>
<div class="bg-white rounded-xl border shadow p-6 mb-8">
  <h2 class="text-lg font-semibold mb-4"><?= $editRow ? 'Edit' : 'Create' ?></h2>
  <form method="post" action="<?= $editRow ? '/landing-pages/update' : '/landing-pages' ?>" class="space-y-4">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <?php if ($editRow): ?><input type="hidden" name="id" value="<?= htmlspecialchars((string) $editRow['id']) ?>"><?php endif; ?>
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium">Name</label>
        <input name="name" required class="w-full border rounded-lg px-3 py-2 mt-1 text-gray-900" value="<?= $editRow ? htmlspecialchars((string) $editRow['name']) : '' ?>">
      </div>
      <div>
        <label class="text-sm font-medium">Slug</label>
        <input name="slug" required class="w-full border rounded-lg px-3 py-2 mt-1 font-mono text-gray-900" value="<?= $editRow ? htmlspecialchars((string) $editRow['slug']) : '' ?>">
      </div>
      <div>
        <label class="text-sm font-medium">TikTok config</label>
        <select name="config_id" class="w-full border rounded-lg px-3 py-2 mt-1 text-gray-900">
          <option value="">— none —</option>
          <?php foreach ($configs as $c): ?>
          <option value="<?= htmlspecialchars((string) $c['id']) ?>" <?= ($editRow && (string)($editRow['config_id'] ?? '') === (string)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars((string) $c['pixel_id']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Event name</label>
        <select name="event_name" class="w-full border rounded-lg px-3 py-2 mt-1 text-gray-900">
          <?php foreach ($eventOpts as $e): ?>
          <option value="<?= htmlspecialchars($e) ?>" <?= ($editRow && ($editRow['event_name'] ?? '') === $e) ? 'selected' : '' ?>><?= htmlspecialchars($e) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Page title</label>
        <input name="page_title" class="w-full border rounded-lg px-3 py-2 mt-1 text-gray-900" value="<?= $editRow ? htmlspecialchars((string) $editRow['page_title']) : 'Please Wait...' ?>">
      </div>
      <div>
        <label class="text-sm font-medium">Click ID param</label>
        <input name="click_id_param" class="w-full border rounded-lg px-3 py-2 mt-1 text-gray-900" value="<?= $editRow ? htmlspecialchars((string) $editRow['click_id_param']) : 'ttclid' ?>">
      </div>
      <?php if ($editRow): ?>
      <div class="md:col-span-2 flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" id="is_active" <?= (int)($editRow['is_active'] ?? 0) ? 'checked' : '' ?>>
        <label for="is_active" class="text-sm">Active</label>
      </div>
      <?php endif; ?>
    </div>
    <div>
      <label class="text-sm font-medium">Verified domains</label>
      <div class="grid md:grid-cols-2 gap-2 border rounded-lg p-3 bg-gray-50 mt-1 max-h-40 overflow-auto">
        <?php if ($verifiedDomains === []): ?>
        <p class="text-sm text-gray-500">No verified domains. Add in Custom Domains.</p>
        <?php else: foreach ($verifiedDomains as $d): ?>
        <label class="flex gap-2 text-sm text-gray-800">
          <input type="checkbox" name="domain_ids[]" value="<?= htmlspecialchars((string) $d['id']) ?>" <?= in_array((string)$d['id'], $selDomains, true) ? 'checked' : '' ?>>
          <span class="font-mono"><?= htmlspecialchars((string) $d['domain']) ?></span>
        </label>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <div class="border-t pt-4">
      <h3 class="font-semibold text-gray-800 mb-2">Sections (platforms)</h3>
      <?php for ($i = 0; $i < $maxSec; $i++):
        $sec = ($editRow && isset($editRow['sections'][$i])) ? $editRow['sections'][$i] : null;
        $urls = '';
        if ($sec) {
            $arr = json_decode((string)($sec['urls'] ?? '[]'), true);
            $urls = is_array($arr) ? implode("\n", $arr) : '';
        }
      ?>
      <div class="border rounded-lg p-3 mb-3 bg-gray-50 grid md:grid-cols-3 gap-3">
        <div>
          <label class="text-xs text-gray-600">Platform name</label>
          <input name="sections[<?= $i ?>][name]" class="w-full border rounded px-2 py-1 text-sm text-gray-900" value="<?= $sec ? htmlspecialchars((string)$sec['name']) : '' ?>">
        </div>
        <div>
          <label class="text-xs text-gray-600">Delay ms</label>
          <input type="number" name="sections[<?= $i ?>][delay_ms]" class="w-full border rounded px-2 py-1 text-sm text-gray-900" value="<?= $sec ? (int)$sec['delay_ms'] : 1 ?>" min="1">
        </div>
        <div class="flex items-end">
          <input type="hidden" name="sections[<?= $i ?>][is_active]" value="0">
          <label class="text-xs flex gap-2 text-gray-600"><input type="checkbox" name="sections[<?= $i ?>][is_active]" value="1" <?= (!$sec || (int)$sec['is_active']) ? 'checked' : '' ?>> Active</label>
        </div>
        <div class="md:col-span-3">
          <label class="text-xs text-gray-600">URLs (one per line, use {clickid})</label>
          <textarea name="sections[<?= $i ?>][urls_text]" rows="3" class="w-full border rounded px-2 py-1 text-xs font-mono text-gray-900"><?= htmlspecialchars($urls) ?></textarea>
        </div>
        <input type="hidden" name="sections[<?= $i ?>][sort_order]" value="<?= $i ?>">
      </div>
      <?php endfor; ?>
    </div>
    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg"><?= $editRow ? 'Update' : 'Create' ?></button>
  </form>
</div>
<?php endif; ?>

<?php $appUrl = rtrim(config('app')['url'] ?? 'http://localhost:8080', '/'); ?>
<div class="bg-white rounded-xl border shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-gray-50">
      <tr>
        <th class="px-4 py-2 text-left">Name</th>
        <th class="px-4 py-2 text-left">Link</th>
        <th class="px-4 py-2 text-left">Pixel</th>
        <th class="px-4 py-2 text-left">Domains</th>
        <th class="px-4 py-2 text-left">Status</th>
        <th class="px-4 py-2 text-left">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      <?php foreach ($landingPages as $lp):
        $lpLink = $appUrl . '/lp/' . urlencode((string) $lp['slug']);
        $domainLinks = [];
        foreach ($lp['domain_links'] ?? [] as $dl) {
            if (($dl['status'] ?? '') === 'verified' && !empty($dl['domain'])) {
                $domainLinks[] = 'https://' . $dl['domain'] . '/lp/' . urlencode((string) $lp['slug']);
            }
        }
      ?>
      <tr>
        <td class="px-4 py-2 font-medium"><?= htmlspecialchars((string) $lp['name']) ?></td>
        <td class="px-4 py-2">
          <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
              <a href="<?= htmlspecialchars($lpLink) ?>" target="_blank" class="font-mono text-xs text-blue-600 hover:underline truncate max-w-[220px]"><?= htmlspecialchars($lpLink) ?></a>
              <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($lpLink, ENT_QUOTES) ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500)" class="px-2 py-0.5 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded border">Copy</button>
            </div>
            <?php foreach ($domainLinks as $dLink): ?>
            <div class="flex items-center gap-2">
              <a href="<?= htmlspecialchars($dLink) ?>" target="_blank" class="font-mono text-xs text-cyan-600 hover:underline truncate max-w-[220px]"><?= htmlspecialchars($dLink) ?></a>
              <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($dLink, ENT_QUOTES) ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500)" class="px-2 py-0.5 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded border">Copy</button>
            </div>
            <?php endforeach; ?>
          </div>
        </td>
        <td class="px-4 py-2"><?= htmlspecialchars((string) ($lp['pixel_id'] ?? '—')) ?></td>
        <td class="px-4 py-2"><?= count($lp['domain_links'] ?? []) ?></td>
        <td class="px-4 py-2"><?= (int)$lp['is_active'] ? '<span class="text-green-700">Active</span>' : '<span class="text-gray-500">Off</span>' ?></td>
        <td class="px-4 py-2 flex gap-2">
          <a href="/landing-pages?edit=<?= urlencode((string)$lp['id']) ?>" class="text-blue-600">Edit</a>
          <form method="post" action="/landing-pages/delete" onsubmit="return confirm('Delete?');" class="inline">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$lp['id']) ?>">
            <button class="text-red-600">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
