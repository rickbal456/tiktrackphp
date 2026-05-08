<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
</head>
<body style="margin:0;padding:0;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#000;color:#fff;font-family:system-ui,-apple-system,sans-serif">
  <div style="text-align:center">
    <div style="width:40px;height:40px;border:3px solid rgba(255,255,255,0.2);border-top-color:#fff;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 16px"></div>
    <p style="font-size:16px;opacity:0.8" id="lp-status">Redirecting...</p>
    <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
  </div>
  <script>
    (function () {
      var sections = <?= json_encode($sections, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?>;
      var ttclid = <?= json_encode($ttclid, JSON_THROW_ON_ERROR) ?>;
      var pixelId = <?= json_encode($pixelId, JSON_THROW_ON_ERROR) ?>;
      var eventName = <?= json_encode($eventName, JSON_THROW_ON_ERROR) ?>;
      var eventId = <?= json_encode($eventId, JSON_THROW_ON_ERROR) ?>;
      var statusEl = document.getElementById('lp-status');

      function replaceClickid(url) {
        var r = ttclid ? encodeURIComponent(ttclid) : '';
        url = url.replace(/\{clickid\}/gi, r);
        url = url.replace(/%7Bclickid%7D/gi, r);
        url = url.replace(/%257Bclickid%257D/gi, r);
        return url;
      }

      function doRedirects() {
        var sorted = sections.slice().sort(function (a, b) { return a.sortOrder - b.sortOrder; })
          .filter(function (s) { return s.urls && s.urls.length; });
        if (!sorted.length) return;
        sorted.forEach(function (section) {
          setTimeout(function () {
            var idx = Math.floor(Math.random() * section.urls.length);
            var url = section.urls[idx];
            url = replaceClickid(url);
            if (statusEl) statusEl.textContent = 'Opening ' + section.name + '...';
            window.location.href = url;
          }, section.delayMs || 1);
        });
      }

      if (!pixelId) {
        doRedirects();
        return;
      }
      var script = document.createElement('script');
      script.innerHTML = "!function (w, d, t) { w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=[\"page\",\"track\",\"identify\",\"instances\",\"debug\",\"on\",\"off\",\"once\",\"ready\",\"alias\",\"group\",\"enableCookie\",\"disableCookie\",\"holdConsent\",\"revokeConsent\",\"grantConsent\"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};ttq.load=function(e,n){var r=\"https://analytics.tiktok.com/i18n/pixel/events.js\",o=n&&n.partner;ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=r,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var a=document.createElement(\"script\");a.type=\"text/javascript\",a.async=!0,a.src=r+\"?sdkid=\"+e+\"&lib=\"+t;var s=document.getElementsByTagName(\"script\")[0];s.parentNode.insertBefore(a,s)}; ttq.load('" + pixelId.replace(/'/g, "\\'") + "'); ttq.page(); }(window, document, 'ttq');";
      document.head.appendChild(script);
      var fired = false;
      var wait = setInterval(function () {
        if (window.ttq && typeof window.ttq.track === 'function') {
          clearInterval(wait);
          window.ttq.track(eventName, {}, { event_id: eventId });
          fired = true;
          doRedirects();
        }
      }, 50);
      setTimeout(function () {
        if (!fired) {
          clearInterval(wait);
          doRedirects();
        }
      }, 3000);
    })();
  </script>
</body>
</html>
