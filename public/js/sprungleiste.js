/*
 * Sprungleiste – darthmax78/contao-retro (rg-08), Herkunft: contao-inventar v1.4.2
 *
 * - fixiert die Leiste unter dem Header, sobald sie aus dem Bild scrollt
 *   (die Hülle behält ihre Höhe, damit nichts springt)
 * - markiert den Abschnitt, in dem man gerade liest (a.is-aktiv)
 * - scrollt beim Klick weich und berücksichtigt Header und Leiste
 * Der fixierte oder klebende Kopf wird themenunabhängig erkannt (Oneo, TAO …):
 * jedes position:fixed/sticky-Element, das oben am Fensterrand anliegt und
 * nicht höher als ein Drittel des Fensters ist.
 */
(function () {
    'use strict';

    if (window.saSprungleiste) {
        return;
    }
    window.saSprungleiste = true;

    var SELEKTOREN = 'header, .header, .page-header, .header-bar, .header-navigation, [class*="header"], [class*="sticky"]';
    var kopfKandidaten = null;

    function kandidatenSammeln() {
        kopfKandidaten = Array.prototype.filter.call(document.querySelectorAll(SELEKTOREN), function (el) {
            return !el.closest('.sa-sprung-huelle');
        });
    }

    function kopfUnterkante() {
        if (!kopfKandidaten) {
            kandidatenSammeln();
        }
        var unten = 0;
        var grenze = window.innerHeight / 3;
        kopfElemente = [];
        for (var i = 0; i < kopfKandidaten.length; i++) {
            var el = kopfKandidaten[i];
            var position = window.getComputedStyle(el).position;
            if (position !== 'fixed' && position !== 'sticky') {
                continue;
            }
            var r = el.getBoundingClientRect();
            // nur sichtbare Leisten am oberen Rand (keine Overlays, keine ausgeblendeten Köpfe)
            if (r.height > 0 && r.height < grenze && r.top <= 1 && r.bottom > 0) {
                unten = Math.max(unten, r.bottom);
                kopfElemente.push(el);
            }
        }
        return unten;
    }

    var kopfElemente = [];

    /*
     * Ebene des Kopfes auf oberster Ebene des Dokuments: Ausklappmenüs (RockSolid
     * Mega-Menü) hängen im Kopf und können nie höher liegen als er. Die Leiste muss
     * also darunter bleiben. Maßgeblich ist der äußerste Vorfahr mit eigenem z-index.
     */
    function kopfEbene() {
        var ebene = null;
        kopfElemente.forEach(function (el) {
            var z = null;
            for (var k = el; k && k !== document.documentElement; k = k.parentElement) {
                var stil = window.getComputedStyle(k);
                var wert = parseInt(stil.zIndex, 10);
                if (!isNaN(wert) && stil.position !== 'static') {
                    z = wert;
                }
            }
            if (z !== null) {
                ebene = ebene === null ? z : Math.max(ebene, z);
            }
        });
        return ebene;
    }

    /*
     * Leiste direkt unter den Kopf legen. Liegt der Kopf zu tief (z-index 0–2),
     * wird stattdessen der Kopf auf 20 angehoben, damit die Leiste über dem Inhalt bleibt.
     */
    function ebeneSetzen(nav) {
        var z = kopfEbene();
        if (z === null) {
            nav.style.zIndex = '';
            return;
        }
        if (z - 1 >= 2) {
            nav.style.zIndex = String(z - 1);
        } else {
            kopfElemente.forEach(function (el) {
                var aussen = el;
                for (var k = el; k && k !== document.body; k = k.parentElement) {
                    var p = window.getComputedStyle(k).position;
                    if (p === 'fixed' || p === 'sticky') {
                        aussen = k;
                    }
                }
                aussen.style.zIndex = '20';
            });
            nav.style.zIndex = '19';
        }
    }

    var sanft = !(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    function einrichten(huelle) {
        var nav = huelle.querySelector('.sa-sprung');
        if (!nav) {
            return;
        }
        var liste = nav.querySelector('.sa-sprung-liste');
        var links = Array.prototype.slice.call(nav.querySelectorAll('a'));
        var ziele = links.map(function (a) {
            return document.getElementById(decodeURIComponent(a.hash.slice(1)));
        });
        var aktiv = -2;
        var ebeneGesetzt = false;
        var angefordert = false;

        function markieren(index) {
            if (index === aktiv) {
                return;
            }
            aktiv = index;
            links.forEach(function (a, i) {
                if (i === index) {
                    a.classList.add('is-aktiv');
                    a.setAttribute('aria-current', 'location');
                    liste.scrollLeft = a.offsetLeft - (liste.clientWidth - a.offsetWidth) / 2;
                } else {
                    a.classList.remove('is-aktiv');
                    a.removeAttribute('aria-current');
                }
            });
        }

        function aktualisieren() {
            angefordert = false;
            var oben = kopfUnterkante();
            var rahmen = huelle.getBoundingClientRect();
            if (!ebeneGesetzt && kopfElemente.length) {
                ebeneSetzen(nav);
                ebeneGesetzt = true;
            }

            huelle.style.minHeight = nav.offsetHeight + 'px';

            if (rahmen.top < oben) {
                nav.classList.add('is-fixiert');
                nav.style.top = oben + 'px';
                nav.style.left = rahmen.left + 'px';
                nav.style.width = rahmen.width + 'px';
            } else {
                nav.classList.remove('is-fixiert');
                nav.style.top = '';
                nav.style.left = '';
                nav.style.width = '';
            }

            var grenze = oben + nav.offsetHeight + 24;
            var index = -1;
            ziele.forEach(function (ziel, i) {
                if (ziel && ziel.getBoundingClientRect().top <= grenze) {
                    index = i;
                }
            });
            markieren(index);
        }

        function anfordern() {
            if (!angefordert) {
                angefordert = true;
                window.requestAnimationFrame(aktualisieren);
            }
        }

        links.forEach(function (a, i) {
            a.addEventListener('click', function (ereignis) {
                var ziel = ziele[i];
                if (!ziel) {
                    return;
                }
                ereignis.preventDefault();
                var y = ziel.getBoundingClientRect().top + window.pageYOffset
                    - kopfUnterkante() - nav.offsetHeight - 16;
                window.scrollTo({ top: y, behavior: sanft ? 'smooth' : 'auto' });
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, '', a.hash);
                }
            });
        });

        window.addEventListener('scroll', anfordern, { passive: true });
        window.addEventListener('resize', function () {
            kopfKandidaten = null;
            anfordern();
        });
        aktualisieren();
    }

    function start() {
        Array.prototype.forEach.call(document.querySelectorAll('.sa-sprung-huelle'), einrichten);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
