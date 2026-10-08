(function () {
  var supported = new Set([
    'it', 'en', 'es', 'fr', 'de', 'pt', 'ru', 'uk', 'pl', 'tr', 'nl', 'sv',
    'da', 'no', 'fi', 'el', 'cs', 'ro', 'hu', 'bg', 'sr', 'hr', 'sk', 'sl',
    'ar', 'he', 'fa', 'hi', 'bn', 'ur', 'zh', 'ja', 'ko', 'vi', 'th', 'id',
    'ms', 'tl', 'sw'
  ]);
  var countryLanguages = {
    AR: 'es', AT: 'de', AU: 'en', BE: 'nl', BR: 'pt', CA: 'en', CH: 'de',
    CL: 'es', CN: 'zh', CO: 'es', CZ: 'cs', DE: 'de', DK: 'da', EG: 'ar',
    ES: 'es', FI: 'fi', FR: 'fr', GB: 'en', GR: 'el', HK: 'zh', HU: 'hu',
    ID: 'id', IE: 'en', IL: 'he', IN: 'hi', IT: 'it', JP: 'ja', KR: 'ko',
    MX: 'es', MY: 'ms', NL: 'nl', NO: 'no', NZ: 'en', PL: 'pl', PT: 'pt',
    RO: 'ro', RS: 'sr', RU: 'ru', SA: 'ar', SE: 'sv', TH: 'th', TR: 'tr',
    TW: 'zh', UA: 'uk', US: 'en', VN: 'vi', ZA: 'en'
  };

  function browserLanguage() {
    var candidates = Array.isArray(navigator.languages) && navigator.languages.length
      ? navigator.languages
      : [navigator.language || 'en'];

    for (var i = 0; i < candidates.length; i += 1) {
      var code = String(candidates[i] || '').toLowerCase().replace('_', '-').split('-')[0];
      if (supported.has(code)) return code;
    }
    return 'en';
  }

  function emitLanguage(language) {
    window.ZERO_IP_LANGUAGE = language;
    window.dispatchEvent(new CustomEvent('zero-ip-language', {
      detail: { language: language }
    }));
  }

  function supportedFromList(value) {
    var candidates = String(value || '').split(',');
    for (var i = 0; i < candidates.length; i += 1) {
      var code = candidates[i].trim().toLowerCase().replace('_', '-').split('-')[0];
      if (supported.has(code)) return code;
    }
    return null;
  }

  var fallback = browserLanguage();
  var lastLookup = 0;

  function detectFromCurrentIp() {
    var now = Date.now();
    if (now - lastLookup < 30000) return;
    lastLookup = now;

    var controller = typeof AbortController === 'function' ? new AbortController() : null;
    var timeout = controller ? window.setTimeout(function () { controller.abort(); }, 4000) : null;
    fetch('https://ipapi.co/json/', {
      cache: 'no-store',
      credentials: 'omit',
      headers: { Accept: 'application/json' },
      signal: controller ? controller.signal : undefined
    }).then(function (response) {
      if (!response.ok) throw new Error('GeoIP request failed with HTTP ' + response.status);
      return response.json();
    }).then(function (data) {
      var language = supportedFromList(data && data.languages);
      if (!language && data && data.country_code) {
        language = countryLanguages[String(data.country_code).toUpperCase()] || null;
      }
      if (!language) throw new Error('GeoIP response did not include a supported language');
      emitLanguage(language);
    }).catch(function (error) {
      console.warn('Could not determine language from IP; using the browser language.', error);
      emitLanguage(fallback);
    }).then(function () {
      if (timeout !== null) window.clearTimeout(timeout);
    });
  }

  emitLanguage(fallback);
  detectFromCurrentIp();
  window.addEventListener('online', detectFromCurrentIp);
  window.addEventListener('pageshow', detectFromCurrentIp);
  window.addEventListener('focus', detectFromCurrentIp);
})();
