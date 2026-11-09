/*
 * darthmax78/contao-retro – Spieleliste (retro_spieleliste, rg-31)
 * Sortieren per Klick auf den Spaltenkopf (zweiter Klick dreht die Richtung) und Suchen beim Tippen.
 * Leere Werte stehen beim Sortieren immer am Ende. Ohne Skript bleibt die Liste nach Name sortiert.
 * Mehrere Listen auf einer Seite und mehrfaches Einbinden des Skripts sind kein Problem.
 */
(function () {
  'use strict';

  var vergleich = new Intl.Collator('de', { numeric: true, sensitivity: 'base' });

  function wert(zelle, art) {
    if (!zelle) {
      return null;
    }
    if (art === 'zahl') {
      var z = parseFloat(zelle.getAttribute('data-wert'));
      return isNaN(z) ? null : z;
    }
    var t = zelle.textContent.trim();
    return t === '' ? null : t;
  }

  function einrichten(liste) {
    if (liste.hasAttribute('data-sl-bereit')) {
      return;
    }
    liste.setAttribute('data-sl-bereit', '');

    var tabelle = liste.querySelector('table');
    var tbody = tabelle.tBodies[0];
    var kopf = tabelle.tHead.rows[0];
    var zeilen = Array.prototype.slice.call(tbody.rows);
    var aktiv = { spalte: 0, auf: true };

    function sortieren(spalte, art, auf) {
      zeilen.sort(function (a, b) {
        var wa = wert(a.cells[spalte], art);
        var wb = wert(b.cells[spalte], art);
        if (wa === null && wb === null) {
          return vergleich.compare(a.cells[0].textContent, b.cells[0].textContent);
        }
        if (wa === null) {
          return 1;
        }
        if (wb === null) {
          return -1;
        }
        var e = art === 'zahl' ? wa - wb : vergleich.compare(wa, wb);
        if (e === 0) {
          return vergleich.compare(a.cells[0].textContent, b.cells[0].textContent);
        }
        return auf ? e : -e;
      });
      zeilen.forEach(function (z) { tbody.appendChild(z); });

      Array.prototype.forEach.call(kopf.cells, function (th, i) {
        if (i === spalte) {
          th.setAttribute('aria-sort', auf ? 'ascending' : 'descending');
        } else {
          th.removeAttribute('aria-sort');
        }
      });
    }

    kopf.addEventListener('click', function (e) {
      var knopf = e.target.closest('button.sl-sort');
      if (!knopf) {
        return;
      }
      var spalte = parseInt(knopf.getAttribute('data-sl-spalte'), 10);
      var auf = spalte === aktiv.spalte ? !aktiv.auf : true;
      aktiv = { spalte: spalte, auf: auf };
      sortieren(spalte, knopf.getAttribute('data-sl-art'), auf);
    });

    var werkzeug = liste.querySelector('.sl-werkzeug');
    var feld = liste.querySelector('[data-sl-suche]');
    var treffer = liste.querySelector('[data-sl-treffer]');

    if (werkzeug && feld) {
      werkzeug.hidden = false;
      // Suchtext je Zeile merken (am Element, weil sich die Reihenfolge beim Sortieren ändert)
      zeilen.forEach(function (z) { z.slText = z.textContent.toLowerCase(); });

      feld.addEventListener('input', function () {
        var begriffe = feld.value.toLowerCase().split(/\s+/).filter(Boolean);
        var anzahl = 0;
        zeilen.forEach(function (z) {
          var passt = begriffe.every(function (b) { return z.slText.indexOf(b) !== -1; });
          z.hidden = !passt;
          if (passt) {
            anzahl++;
          }
        });
        treffer.textContent = begriffe.length ? anzahl + ' von ' + zeilen.length : '';
      });
    }
  }

  function alle() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-spieleliste]'), einrichten);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', alle);
  } else {
    alle();
  }
})();
