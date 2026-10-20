// Preserve the queue shape expected by the analytics script before it loads.
window._hmt = window._hmt || []

;(function () {
  const script = document.createElement("script");
  script.src = "https://cdn.oss.9gt.net/js/es.js?version=tuoluojiangv2.6";

  const firstScript = document.getElementsByTagName("script")[0];
  if (firstScript && firstScript.parentNode) {
    firstScript.parentNode.insertBefore(script, firstScript);
    return;
  }

  document.head.appendChild(script);
})();

export {}
