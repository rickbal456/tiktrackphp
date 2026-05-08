<?php
/** @var array $stats */
/** @var array<int, array<string, mixed>> $recentConversions */
/** @var bool $isAdmin */
?>
<h1 class="text-2xl font-bold text-gray-900 mb-6"><?= $isAdmin ? 'Admin Dashboard' : 'Dashboard' ?></h1>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
  <?php
  $icons = [
    'Total Conversions' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
    'Sent' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>',
    'Failed' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>',
    'Pending' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    'Configs' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    'Users' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
    'Landing Pages' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm10 0a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1v-6z"/></svg>',
  ];
  $cards = [
    ['Total Conversions', $stats['totalConversions'], 'bg-blue-500'],
    ['Sent', $stats['sentConversions'], 'bg-green-500'],
    ['Failed', $stats['failedConversions'], 'bg-red-500'],
    ['Pending', $stats['pendingConversions'], 'bg-yellow-500'],
    ['Configs', $stats['totalConfigs'], 'bg-purple-500'],
  ];
  if ($isAdmin) {
    $cards[] = ['Users', $stats['totalUsers'], 'bg-indigo-500'];
  }
  $cards[] = ['Landing Pages', $stats['totalLandingPages'], 'bg-teal-500'];
  foreach ($cards as [$label, $val, $bg]):
  ?>
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center gap-4">
      <div class="<?= $bg ?> p-3 rounded-lg w-12 h-12 flex items-center justify-center"><?= $icons[$label] ?></div>
      <div>
        <p class="text-sm text-gray-500"><?= htmlspecialchars($label) ?></p>
        <p class="text-2xl font-bold text-gray-900"><?= (int) $val ?></p>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200">
  <div class="p-6 border-b border-gray-200">
    <h2 class="text-lg font-semibold text-gray-900">Recent Conversions</h2>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pixel</th>
          <?php if ($isAdmin): ?><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th><?php endif; ?>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <?php if ($recentConversions === []): ?>
        <tr><td colspan="<?= $isAdmin ? 5 : 4 ?>" class="px-6 py-8 text-center text-gray-500">No conversions yet</td></tr>
        <?php else: foreach ($recentConversions as $conv): ?>
        <tr class="hover:bg-gray-50">
          <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars((string) $conv['event_name']) ?></td>
          <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars((string) $conv['pixel_id']) ?></td>
          <?php if ($isAdmin): ?><td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars((string) $conv['user_name']) ?></td><?php endif; ?>
          <td class="px-6 py-4">
            <?php $s = (string) $conv['status']; ?>
            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?= $s === 'sent' ? 'bg-green-100 text-green-700' : ($s === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') ?>"><?= htmlspecialchars($s) ?></span>
          </td>
          <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars((string) $conv['created_at']) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
