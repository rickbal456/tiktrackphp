<?php use App\Core\Csrf; ?>
<div class="w-full max-w-md px-6">
  <div class="bg-white rounded-2xl shadow-xl p-8">
    <div class="text-center mb-6">
      <img src="/assets/images/tiktrack-logo-teks.png" alt="TikTrack" class="w-40 h-auto mx-auto mb-2" onerror="this.style.display='none'">
      <p class="text-gray-500 text-sm">Create your TikTrack account</p>
    </div>
    <form method="post" action="/register" class="space-y-5">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
        <input type="text" name="name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-gray-900">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-gray-900">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input type="password" name="password" minlength="6" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-gray-900">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
        <input type="password" name="confirm_password" minlength="6" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-gray-900">
      </div>
      <button type="submit" class="w-full py-3 bg-gradient-to-r from-cyan-500 to-pink-500 text-white rounded-lg font-medium">Sign Up</button>
    </form>
    <p class="text-center text-gray-500 mt-6">
      Already have an account? <a href="/login" class="text-cyan-600 font-medium">Sign in</a>
    </p>
  </div>
</div>
