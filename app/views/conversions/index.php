<?php
/** @var array $conversions */
/** @var array $configs */
/** @var array $landingPages */
/** @var bool $isAdmin */
/** @var ?array $detail */
/** @var mixed $detailPreview */
use App\Core\Csrf;

$appUrl = rtrim((string) (config('app')['url'] ?? ''), '/');
$scheme = (string) (config('app')['custom_domain_scheme'] ?? '');
if ($scheme === '') {
    $scheme = (config('app')['next_public_domain_verify_local_mode'] ?? false) ? 'http' : 'https';
}
$buildLpUrl = static function (array $lp) use ($scheme, $appUrl): string {
    $primary = null;
    foreach ($lp['domain_links'] ?? [] as $l) {
        if (($l['status'] ?? '') === 'verified' && !empty($l['is_primary'])) {
            $primary = $l['domain'];
            break;
        }
    }
    if ($primary === null) {
        foreach ($lp['domain_links'] ?? [] as $l) {
            if (($l['status'] ?? '') === 'verified') {
                $primary = $l['domain'];
                break;
            }
        }
    }
    if ($primary) {
        return $scheme . '://' . $primary . '/lp/' . rawurlencode((string) $lp['slug']);
    }
    return $appUrl . '/lp/' . rawurlencode((string) $lp['slug']);
};
$events = ['Purchase', 'CompletePayment', 'PlaceAnOrder', 'AddToCart', 'ViewContent', 'InitiateCheckout', 'CompleteRegistration', 'ClickButton', 'Contact', 'SubmitForm', 'Subscribe', 'Download', 'Search'];
?>
<div class="mb-6 flex flex-wrap justify-between gap-4">
  <h1 class="text-2xl font-bold text-gray-900">Conversions</h1>
  <a href="/conversions" class="text-sm text-gray-600">Clear filters</a>
</div>

<?php if ($configs === []): ?>
<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6 text-yellow-800">No active TikTok config. <a href="/config" class="underline font-medium">Create one</a>.</div>
<?php endif; ?>

<div class="bg-white rounded-xl border shadow-sm p-6 mb-6 space-y-4">
  <div class="flex flex-wrap gap-4 items-end">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Config</label>
      <select id="cfg-select" class="border rounded-lg px-3 py-2 text-gray-900 min-w-[200px]">
        <?php foreach ($configs as $c): ?>
        <option value="<?= htmlspecialchars((string) $c['id']) ?>"><?= htmlspecialchars((string) $c['pixel_id']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <form method="post" action="/conversions/import" enctype="multipart/form-data" class="flex items-end gap-2">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
      <input type="hidden" name="config_id" id="csv-config-id" value="">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Import CSV</label>
        <input type="file" name="csv" accept=".csv" required class="text-sm">
      </div>
      <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Upload</button>
    </form>
    <form method="post" action="/conversions/send-all">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
      <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Send All Pending</button>
    </form>
  </div>
  <p class="text-xs text-gray-500">CSV headers: eventName,eventTime,eventId,ttclid,url,value,currency</p>
</div>

<div class="bg-white rounded-xl border shadow-sm p-6 mb-6">
  <h2 class="text-lg font-semibold mb-4">Add conversion</h2>
  <form method="post" action="/conversions" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <input type="hidden" name="config_id" id="manual-config-id" value="">
    <div>
      <label class="text-sm font-medium text-gray-700">Event</label>
      <select name="event_name" class="w-full border rounded-lg px-3 py-2 mt-1 text-gray-900">
        <?php foreach ($events as $e): ?><option value="<?= htmlspecialchars($e) ?>"><?= htmlspecialchars($e) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm font-medium text-gray-700">Event time</label>
      <input type="datetime-local" name="event_time" required class="w-full border rounded-lg px-3 py-2 mt-1 text-gray-900" value="<?= date('Y-m-d\TH:i') ?>">
    </div>
    <div>
      <label class="text-sm font-medium text-gray-700">ttclid</label>
      <input name="ttclid" required class="w-full border rounded-lg px-3 py-2 mt-1 text-gray-900">
    </div>
    <div>
      <label class="text-sm font-medium text-gray-700">Value</label>
      <input name="value" type="number" step="0.01" class="w-full border rounded-lg px-3 py-2 mt-1 text-gray-900">
    </div>
    <div>
      <label class="text-sm font-medium text-gray-700">Currency</label>
      <select name="currency" class="w-full border rounded-lg px-3 py-2 mt-1 text-gray-900">
        <?php foreach (['IDR','USD','EUR'] as $cur): ?><option value="<?= $cur ?>"><?= $cur ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="text-sm font-medium text-gray-700">Landing page (optional)</label>
      <select id="lp-picker" class="w-full border rounded-lg px-3 py-2 mt-1 text-gray-900">
        <option value="">—</option>
        <?php foreach ($landingPages as $lp): ?>
        <option data-url="<?= htmlspecialchars($buildLpUrl($lp)) ?>" value="<?= htmlspecialchars((string) $lp['id']) ?>"><?= htmlspecialchars((string) $lp['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="text-sm font-medium text-gray-700">Page URL</label>
      <input name="url" id="page-url" required type="url" class="w-full border rounded-lg px-3 py-2 mt-1 text-gray-900" placeholder="https://...">
    </div>
    <div class="md:col-span-3">
      <label class="text-sm font-medium text-gray-700">Event ID</label>
      <input name="event_id" class="w-full border rounded-lg px-3 py-2 mt-1 font-mono text-sm text-gray-900" value="manual_<?= bin2hex(random_bytes(8)) ?>">
    </div>
    <div class="md:col-span-3 flex gap-3">
      <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg">Create</button>
    </div>
  </form>
</div>

<form method="post" action="/conversions/send" id="bulk-form">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
  <div class="bg-white rounded-xl border shadow-sm overflow-x-auto">
    <div class="p-4 border-b flex justify-between items-center">
      <h2 class="text-lg font-semibold">List</h2>
      <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm">Send selected</button>
    </div>
    <table class="w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left"><input type="checkbox" id="check-all"></th>
          <th class="px-3 py-2 text-left">Event</th>
          <th class="px-3 py-2 text-left">Pixel</th>
          <th class="px-3 py-2 text-left">Status</th>
          <th class="px-3 py-2 text-left">Date</th>
          <th class="px-3 py-2 text-left"></th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php foreach ($conversions as $c): ?>
        <tr>
          <td class="px-3 py-2"><input type="checkbox" name="ids[]" value="<?= htmlspecialchars((string) $c['id']) ?>" class="row-check"></td>
          <td class="px-3 py-2"><?= htmlspecialchars((string) $c['event_name']) ?></td>
          <td class="px-3 py-2 text-gray-500"><?= htmlspecialchars((string) $c['pixel_id']) ?></td>
          <td class="px-3 py-2"><span class="text-xs px-2 py-0.5 rounded-full <?= ($c['status'] ?? '') === 'sent' ? 'bg-green-100 text-green-700' : (($c['status'] ?? '') === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-800') ?>"><?= htmlspecialchars((string) ($c['status'] ?? '')) ?></span></td>
          <td class="px-3 py-2 text-gray-500"><?= htmlspecialchars((string) $c['created_at']) ?></td>
          <td class="px-3 py-2"><a href="/conversions?detail=<?= urlencode((string) $c['id']) ?>" class="text-blue-600">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</form>

<?php if ($detail): ?>
<div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" id="detail-modal">
  <div class="bg-white rounded-xl max-w-4xl w-full max-h-[90vh] overflow-auto p-6 shadow-xl">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">Conversion <?= htmlspecialchars((string) $detail['id']) ?></h3>
      <a href="/conversions" class="text-gray-500 text-2xl leading-none">&times;</a>
    </div>
    <h4 class="text-sm font-semibold mb-2">Request preview</h4>
    <pre class="bg-gray-900 text-green-300 text-xs p-4 rounded-lg overflow-x-auto mb-4"><?= htmlspecialchars(json_encode($detailPreview, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
    <h4 class="text-sm font-semibold mb-2">API response</h4>
    <pre class="bg-gray-50 border text-xs p-4 rounded-lg overflow-x-auto"><?php
      $resp = $detail['response'] ?? null;
      if ($resp) {
        $j = is_string($resp) ? json_decode($resp, true) : $resp;
        echo htmlspecialchars(json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
      } else {
        echo '— pending / not sent —';
      }
    ?></pre>
  </div>
</div>
<?php endif; ?>

<script>
(function(){
  var sel = document.getElementById('cfg-select');
  var manual = document.getElementById('manual-config-id');
  var csv = document.getElementById('csv-config-id');
  function sync(){ if(sel){ var v=sel.value; if(manual) manual.value=v; if(csv) csv.value=v; } }
  if(sel){ sel.addEventListener('change', sync); sync(); }
  var lp = document.getElementById('lp-picker');
  var url = document.getElementById('page-url');
  if(lp && url){ lp.addEventListener('change', function(){
    var opt = lp.options[lp.selectedIndex];
    var u = opt && opt.getAttribute('data-url');
    if(u) url.value = u;
  }); }
  var all = document.getElementById('check-all');
  if(all){ all.addEventListener('change', function(){
    document.querySelectorAll('.row-check').forEach(function(c){ c.checked = all.checked; });
  }); }
})();
</script>
