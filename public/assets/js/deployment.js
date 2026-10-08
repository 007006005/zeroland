(function () {
  var backendUrl = window.ZERO_BACKEND_URL || 'https://games.zerothelegend.com';
  var backend = new URL(backendUrl);
  var originalFetch = window.fetch.bind(window);

  window.ZERO_BACKEND_URL = backend.origin;
  window.zeroBackendUrl = function (path) {
    return new URL(path, backend.origin).toString();
  };

  window.fetch = function (input, init) {
    var requestUrl = input instanceof Request ? input.url : String(input);
    var url;

    try {
      url = new URL(requestUrl, document.baseURI);
    } catch (error) {
      return originalFetch(input, init);
    }

    if (url.pathname.startsWith('/portal/api/')) {
      url.pathname = url.pathname.slice('/portal'.length);
    }

    if (!url.pathname.includes('/api/') || url.origin === backend.origin) {
      return originalFetch(input, init);
    }

    url.protocol = backend.protocol;
    url.host = backend.host;
    var options = Object.assign({}, init || {}, { credentials: 'include' });

    if (input instanceof Request) {
      return originalFetch(new Request(url.toString(), input), options);
    }

    return originalFetch(url.toString(), options);
  };
})();
