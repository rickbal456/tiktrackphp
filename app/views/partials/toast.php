<?php
use App\Core\Auth;
$success = Auth::getFlash('success');
$error = Auth::getFlash('error');
?>
<?php if ($success): ?>
<div class="fixed top-4 right-4 z-[100] px-4 py-3 rounded-lg bg-green-600 text-white shadow-lg text-sm max-w-md"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="fixed top-4 right-4 z-[100] px-4 py-3 rounded-lg bg-red-600 text-white shadow-lg text-sm max-w-md"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
