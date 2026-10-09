/**
 * Repositorio Institucional — JavaScript nativo (sin dependencias).
 * Navegación, confirmaciones, subida con progreso, autoguardado.
 */
(function () {
    'use strict';

    // ---- Menú móvil ----
    document.querySelectorAll('[data-nav-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var nav = document.getElementById(btn.getAttribute('aria-controls'));
            if (!nav) return;
            var open = nav.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });

    // ---- Sidebar del panel (con overlay) ----
    var sidebarOverlay = document.getElementById('sidebar-overlay');
    function openSidebar(side) {
        if (side) { side.classList.add('open'); }
        if (sidebarOverlay) { sidebarOverlay.classList.add('active'); sidebarOverlay.removeAttribute('aria-hidden'); }
    }
    function closeSidebar(side) {
        if (side) { side.classList.remove('open'); }
        if (sidebarOverlay) { sidebarOverlay.classList.remove('active'); sidebarOverlay.setAttribute('aria-hidden', 'true'); }
    }
    document.querySelectorAll('[data-sidebar-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var side = document.getElementById(btn.getAttribute('aria-controls') || 'sidebar-admin');
            if (!side) return;
            if (side.classList.contains('open')) { closeSidebar(side); } else { openSidebar(side); }
        });
    });
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            var side = document.getElementById('sidebar-admin');
            closeSidebar(side);
        });
    }

    // ---- Header scroll shadow ----
    var siteHeader = document.getElementById('site-header');
    if (siteHeader) {
        var onScroll = function () {
            if (window.scrollY > 6) {
                siteHeader.classList.add('scrolled');
            } else {
                siteHeader.classList.remove('scrolled');
            }
        };
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    // ---- Confirmaciones en operaciones destructivas ----
    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (form.matches('[data-confirm]')) {
            var msg = form.getAttribute('data-confirm');
            if (msg && !window.confirm(msg)) {
                ev.preventDefault();
            }
        }
    });

    // ---- Toasts accesibles: auto-ocultar ----
    window.setTimeout(function () {
        document.querySelectorAll('.toast').forEach(function (t) {
            t.style.transition = 'opacity .4s ease';
            t.style.opacity = '0';
            window.setTimeout(function () { t.remove(); }, 450);
        });
    }, 6000);

    // ---- Subida de archivos con progreso ----
    document.querySelectorAll('form[data-upload-progress]').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            var bar = document.getElementById(form.getAttribute('data-upload-progress'));
            if (!bar || !window.XMLHttpRequest) return;
            ev.preventDefault();
            var xhr = new XMLHttpRequest();
            xhr.open('POST', form.action || window.location.href, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable && bar) {
                    var pct = Math.round((e.loaded / e.total) * 100);
                    var fill = bar.querySelector('.bar-fill') || bar;
                    fill.style.width = pct + '%';
                    if (fill === bar) bar.value = pct;
                    fill.textContent = pct + '%';
                }
            });
            xhr.addEventListener('load', function () {
                if (xhr.status >= 200 && xhr.status < 400) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data.redirect) { window.location.href = data.redirect; return; }
                    } catch (e) { /* respuesta no JSON: recargar */ }
                    window.location.reload();
                } else {
                    window.alert('La subida falló (' + xhr.status + '). Intente de nuevo.');
                }
            });
            xhr.addEventListener('error', function () {
                window.alert('Error de red durante la subida.');
            });
            var fd = new FormData(form);
            xhr.send(fd);
        });
    });

    // ---- Autoguardado de borradores (formulario con data-autosave) ----
    var autosave = document.querySelector('form[data-autosave]');
    if (autosave) {
        var timer = null;
        var indicator = document.createElement('span');
        indicator.className = 'small muted';
        indicator.setAttribute('role', 'status');
        var head = document.querySelector('.page-head');
        if (head) head.appendChild(indicator);
        autosave.addEventListener('input', function () {
            window.clearTimeout(timer);
            indicator.textContent = 'Cambios sin guardar…';
            timer = window.setTimeout(function () {
                var fd = new FormData(autosave);
                fd.set('_autosave', '1');
                fetch(autosave.action || window.location.href, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                }).then(function (r) {
                    indicator.textContent = r.ok ? 'Borrador guardado ' + new Date().toLocaleTimeString('es-PE') : 'No se pudo guardar';
                }).catch(function () {
                    indicator.textContent = 'Sin conexión: guardaremos al salir del campo';
                });
            }, 2500);
        });
    }

    // ---- Selección múltiple en tablas ----
    var selectAll = document.querySelector('[data-select-all]');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('[data-select-row]').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
        });
    }

    // ---- Copiar cita al portapapeles ----
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var sel = btn.getAttribute('data-copy');
            var target = sel ? document.querySelector(sel) : null;
            var text = target ? target.value || target.textContent : '';
            if (navigator.clipboard && text) {
                navigator.clipboard.writeText(text.trim()).then(function () {
                    var original = btn.textContent;
                    btn.textContent = 'Copiado';
                    window.setTimeout(function () { btn.textContent = original; }, 2000);
                });
            }
        });
    });

    // ---- Filas dinámicas (autores del depósito) ----
    function nextRowIndex(tbody) {
        var max = -1;
        tbody.querySelectorAll('[name^="authors["]').forEach(function (el) {
            var m = el.name.match(/^authors\[(\d+)\]/);
            if (m) { max = Math.max(max, parseInt(m[1], 10)); }
        });
        return max + 1;
    }
    document.querySelectorAll('[data-add-row]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var table = document.getElementById(btn.getAttribute('data-add-row'));
            var tpl = document.getElementById(table ? table.id + '-tpl' : '') ||
                document.getElementById('authors-row-tpl');
            if (!table || !tpl) return;
            var tbody = table.querySelector('tbody');
            var html = tpl.innerHTML.replace(/__N__/g, String(nextRowIndex(tbody)));
            var wrap = document.createElement('tbody');
            wrap.innerHTML = html;
            while (wrap.firstChild) { tbody.appendChild(wrap.firstChild); }
            var first = tbody.lastElementChild.querySelector('input[type="text"]');
            if (first) first.focus();
        });
    });
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest && ev.target.closest('[data-remove-row]');
        if (!btn) return;
        var tr = btn.closest('tr');
        var tbody = tr ? tr.closest('tbody') : null;
        if (!tbody) return;
        if (tbody.rows.length <= 1) {
            window.alert('Debe mantener al menos un autor.');
            return;
        }
        tr.remove();
    });

    // ---- Marcar/desmarcar casillas de un formulario ----
    function toggleFormCheckboxes(form, checked) {
        form.querySelectorAll('input[type="checkbox"]:not([disabled])').forEach(function (cb) {
            cb.checked = checked;
        });
    }
    document.querySelectorAll('[data-check-all]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = btn.closest('form');
            if (form) { toggleFormCheckboxes(form, true); }
        });
    });
    document.querySelectorAll('[data-check-none]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = btn.closest('form');
            if (form) { toggleFormCheckboxes(form, false); }
        });
    });
})();
