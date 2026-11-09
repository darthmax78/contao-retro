/*
 * darthmax78/contao-retro – Quellen (retro_quellen, rg-24; Hervorhebung seit rg-31)
 * Hebt die angesprungene Quelle hervor (Klasse quelle-aktiv). Nötig, weil Sprung-Skripte
 * (Sprungleiste, sanftes Scrollen des Themes) den Klick abfangen und der Browser dann kein
 * neues :target setzt – die Umrahmung erschien sonst erst nach dem Neuladen.
 */
(function () {
  'use strict';

  function aktivieren(id) {
    var ziel = id ? document.getElementById(id) : null;
    if (!ziel || !ziel.closest('ol.quellen')) {
      return;
    }
    Array.prototype.forEach.call(document.querySelectorAll('ol.quellen > li.quelle-aktiv'), function (li) {
      li.classList.remove('quelle-aktiv');
    });
    ziel.classList.add('quelle-aktiv');
  }

  function ausHash(hash) {
    try {
      return decodeURIComponent((hash || '').replace(/^#/, ''));
    } catch (e) {
      return '';
    }
  }

  // Capture-Phase: läuft vor Skripten, die den Klick abfangen; nur Links auf diese Seite
  document.addEventListener('click', function (ereignis) {
    var a = ereignis.target.closest ? ereignis.target.closest('a[href*="#"]') : null;
    if (!a || a.pathname !== window.location.pathname || a.host !== window.location.host) {
      return;
    }
    aktivieren(ausHash(a.hash));
  }, true);

  // Sprünge über die Sprungleiste (stoppt den Klick vor diesem Skript) melden sich per Ereignis
  window.addEventListener('sa:gesprungen', function (ereignis) {
    aktivieren(ereignis.detail && ereignis.detail.id);
  });

  window.addEventListener('hashchange', function () {
    aktivieren(ausHash(window.location.hash));
  });

  aktivieren(ausHash(window.location.hash));
})();
