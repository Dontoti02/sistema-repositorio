/**
 * Theme Editor — Módulo JavaScript del Personalizador de Identidad Institucional
 * Sincronización en vivo, cálculo de contraste WCAG, conmutación responsive y persistencia AJAX.
 */

export function initThemeEditor() {
  const root = document.getElementById('appearance-app');
  if (!root) return;

  const csrfToken = root.dataset.csrf || '';
  const saveDraftUrl = root.dataset.saveDraftUrl;
  const publishUrl = root.dataset.publishUrl;
  const restoreUrl = root.dataset.restoreUrl;
  const uploadUrl = root.dataset.uploadUrl;
  const deleteAssetUrl = root.dataset.deleteAssetUrl;
  const resetPaletteUrl = root.dataset.resetPaletteUrl;

  const form = document.getElementById('theme-form');
  const iframe = document.getElementById('preview-iframe');
  const previewWrapper = document.getElementById('preview-wrapper');
  const statusIndicator = document.getElementById('theme-status-indicator');

  // 1. PESTAÑAS DEL CONFIGURADOR
  const tabButtons = root.querySelectorAll('.appearance-tab-btn');
  const tabPanels = root.querySelectorAll('.appearance-tab-panel');

  tabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.tab.replace('tab-', 'panel-');
      tabButtons.forEach(b => {
        b.classList.remove('active');
        b.setAttribute('aria-selected', 'false');
      });
      tabPanels.forEach(p => p.classList.remove('active'));

      btn.classList.add('active');
      btn.setAttribute('aria-selected', 'true');
      const activePanel = document.getElementById(targetId);
      if (activePanel) activePanel.classList.add('active');
    });
  });

  // 2. CONMUTADOR DE DISPOSITIVOS PARA VISTA PREVIA
  const deviceButtons = root.querySelectorAll('.device-btn');
  deviceButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      deviceButtons.forEach(b => {
        b.classList.remove('active');
        b.setAttribute('aria-pressed', 'false');
      });
      btn.classList.add('active');
      btn.setAttribute('aria-pressed', 'true');

      const device = btn.dataset.device;
      previewWrapper.className = 'preview-frame-wrapper';
      if (device === 'tablet') previewWrapper.classList.add('device-tablet');
      if (device === 'mobile') previewWrapper.classList.add('device-mobile');
    });
  });

  // 3. SINCRONIZACIÓN DE COLORES Y CONTRASTE EN VIVO
  function updateIframeCss() {
    if (!iframe || !iframe.contentWindow) return;

    const primary = document.getElementById('hex-brand_primary')?.value || '#113b70';
    const secondary = document.getElementById('hex-brand_secondary')?.value || '#2873bd';
    const accent = document.getElementById('hex-brand_accent')?.value || '#e4ae35';
    const headerBg = document.getElementById('hex-header_bg')?.value || '#ffffff';
    const footerBg = document.getElementById('hex-footer_bg')?.value || '#102d52';
    const radius = document.getElementById('sel-radius')?.value || '12px';

    const onPrimary = getContrastRatio(primary, '#ffffff') >= 4.5 ? '#ffffff' : '#152438';
    const onSecondary = getContrastRatio(secondary, '#ffffff') >= 4.5 ? '#ffffff' : '#152438';
    const onAccent = getContrastRatio(accent, '#ffffff') >= 4.5 ? '#ffffff' : '#172334';

    const css = `:root {
      --brand-primary: ${primary};
      --brand-secondary: ${secondary};
      --brand-accent: ${accent};
      --brand-on-primary: ${onPrimary};
      --brand-on-secondary: ${onSecondary};
      --brand-on-accent: ${onAccent};
      --header-bg: ${headerBg};
      --footer-bg: ${footerBg};
      --radius-md: ${radius};
    }`;

    iframe.contentWindow.postMessage({ type: 'UPDATE_CSS', css: css }, '*');
    if (statusIndicator) {
      statusIndicator.textContent = '● Cambios sin guardar (borrador pendiente)';
      statusIndicator.style.color = 'var(--warning)';
    }
  }

  // Vincular cada fila de color (picker nativo <-> campo HEX)
  ['brand_primary', 'brand_secondary', 'brand_accent', 'header_bg', 'footer_bg'].forEach(tokenKey => {
    const native = document.getElementById(`native-${tokenKey}`);
    const hex = document.getElementById(`hex-${tokenKey}`);

    if (native && hex) {
      native.addEventListener('input', () => {
        hex.value = native.value.toLowerCase();
        updateIframeCss();
      });

      hex.addEventListener('input', () => {
        let val = hex.value.trim();
        if (!val.startsWith('#')) val = '#' + val;
        if (/^#[0-9a-fA-F]{6}$/.test(val)) {
          native.value = val;
          updateIframeCss();
        }
      });
    }
  });

  // Radio de redondeo
  document.getElementById('sel-radius')?.addEventListener('change', updateIframeCss);

  // Modo de Hero
  document.getElementById('sel-hero-mode')?.addEventListener('change', (e) => {
    if (!iframe || !iframe.contentWindow) return;
    iframe.contentWindow.postMessage({
      type: 'UPDATE_HERO_MODE',
      mode: e.target.value
    }, '*');
  });

  // Textos en vivo
  ['inp-site-name', 'inp-hero-title', 'inp-hero-sub', 'inp-legal-footer'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', () => {
      if (!iframe || !iframe.contentWindow) return;
      iframe.contentWindow.postMessage({
        type: 'UPDATE_TEXTS',
        siteName: document.getElementById('inp-site-name')?.value,
        heroTitle: document.getElementById('inp-hero-title')?.value,
        heroSubtitle: document.getElementById('inp-hero-sub')?.value,
        legalFooter: document.getElementById('inp-legal-footer')?.value
      }, '*');
    });
  });

  // 4. PALETAS PREDEFINIDAS
  root.querySelectorAll('.preset-palette-card').forEach(card => {
    card.addEventListener('click', () => {
      const p = card.dataset.primary;
      const s = card.dataset.secondary;
      const a = card.dataset.accent;

      setTokenColor('brand_primary', p);
      setTokenColor('brand_secondary', s);
      setTokenColor('brand_accent', a);

      updateIframeCss();
      showToast('Paleta aplicada a la vista previa', 'info');
    });
  });

  function setTokenColor(key, value) {
    const native = document.getElementById(`native-${key}`);
    const hex = document.getElementById(`hex-${key}`);
    if (native) native.value = value;
    if (hex) hex.value = value;
  }

  // 5. RESTABLECER PALETA
  document.getElementById('btn-reset-palette')?.addEventListener('click', async () => {
    if (!confirm('¿Restablecer los colores a la configuración predeterminada?')) return;
    try {
      const res = await fetch(resetPaletteUrl, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken }
      });
      const data = await res.json();
      if (data.ok && data.tokens) {
        setTokenColor('brand_primary', data.tokens.brand_primary);
        setTokenColor('brand_secondary', data.tokens.brand_secondary);
        setTokenColor('brand_accent', data.tokens.brand_accent);
        setTokenColor('header_bg', data.tokens.header_bg);
        setTokenColor('footer_bg', data.tokens.footer_bg);
        updateIframeCss();
        showToast('Paleta restablecida', 'success');
      }
    } catch {
      showToast('Error al restablecer paleta', 'error');
    }
  });

  // 6. SUBIDA DE ACTIVOS (LOGOS / FAVICON / HERO)
  root.querySelectorAll('.asset-uploader').forEach(box => {
    const kind = box.dataset.kind;
    const fileInput = box.querySelector('.asset-file-input');

    box.addEventListener('click', () => fileInput?.click());

    fileInput?.addEventListener('change', async () => {
      if (!fileInput.files || fileInput.files.length === 0) return;
      const file = fileInput.files[0];

      const formData = new FormData();
      formData.append('kind', kind);
      formData.append('file', file);

      try {
        box.style.opacity = '0.5';
        const res = await fetch(uploadUrl, {
          method: 'POST',
          headers: { 'X-CSRF-Token': csrfToken },
          body: formData
        });
        const data = await res.json();
        box.style.opacity = '1';

        if (data.ok && data.asset) {
          showToast('Archivo subido correctamente', 'success');
          const previewBox = document.getElementById(`box-${kind}`);
          if (previewBox) {
            const img = previewBox.querySelector('.asset-preview-img') || previewBox.querySelector('img');
            if (img) img.src = data.asset.url;
            previewBox.style.display = 'flex';
          }
          // Recargar iframe para reflejar el nuevo activo
          if (iframe) iframe.src = iframe.src;
        } else {
          showToast(data.message || 'Error al subir archivo', 'error');
        }
      } catch {
        box.style.opacity = '1';
        showToast('Error de conexión al subir activo', 'error');
      }
    });
  });

  // 7. ELIMINACIÓN DE ACTIVOS
  root.querySelectorAll('.btn-delete-asset').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const kind = btn.dataset.kind;
      if (!confirm('¿Seguro que desea retirar este elemento gráfico?')) return;

      const formData = new FormData();
      formData.append('kind', kind);

      try {
        const res = await fetch(deleteAssetUrl, {
          method: 'POST',
          headers: { 'X-CSRF-Token': csrfToken },
          body: formData
        });
        const data = await res.json();
        if (data.ok) {
          const previewBox = document.getElementById(`box-${kind}`);
          if (previewBox) previewBox.style.display = 'none';
          showToast('Activo retirado', 'success');
          if (iframe) iframe.src = iframe.src;
        } else {
          showToast(data.message || 'Error al eliminar', 'error');
        }
      } catch {
        showToast('Error de conexión', 'error');
      }
    });
  });

  // 8. GUARDAR BORRADOR
  document.getElementById('btn-save-draft')?.addEventListener('click', async () => {
    const payload = serializeForm(form);
    try {
      const res = await fetch(saveDraftUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.ok) {
        showToast('Borrador guardado exitosamente', 'success');
        if (statusIndicator) {
          statusIndicator.textContent = '● Borrador guardado (pendiente de publicar)';
          statusIndicator.style.color = 'var(--info)';
        }
      } else {
        showToast(data.message || 'Error al guardar borrador', 'error');
      }
    } catch {
      showToast('Error al comunicarse con el servidor', 'error');
    }
  });

  // 9. PUBLICAR CAMBIOS
  document.getElementById('btn-publish-theme')?.addEventListener('click', async () => {
    if (!confirm('¿Confirma la publicación de esta apariencia en el portal público? Todos los visitantes verán los nuevos colores e identidad.')) {
      return;
    }

    const payload = serializeForm(form);
    try {
      const res = await fetch(publishUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.ok) {
        showToast('¡Apariencia publicada exitosamente!', 'success');
        if (statusIndicator) {
          statusIndicator.textContent = `✓ Todo publicado (v${data.published?.version || 1})`;
          statusIndicator.style.color = 'var(--success)';
        }
        if (iframe) iframe.src = iframe.src;
      } else {
        showToast(data.message || 'Error al publicar apariencia', 'error');
      }
    } catch {
      showToast('Error de conexión al publicar', 'error');
    }
  });

  // 10. RESTAURAR VERSIÓN ANTERIOR
  root.querySelectorAll('.btn-restore-theme').forEach(btn => {
    btn.addEventListener('click', async () => {
      const themeId = btn.dataset.themeId;
      if (!confirm('¿Restaurar esta versión histórica del tema? Se publicará inmediatamente.')) return;

      const formData = new FormData();
      formData.append('theme_id', themeId);

      try {
        const res = await fetch(restoreUrl, {
          method: 'POST',
          headers: { 'X-CSRF-Token': csrfToken },
          body: formData
        });
        const data = await res.json();
        if (data.ok) {
          showToast(data.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showToast(data.message || 'Error al restaurar versión', 'error');
        }
      } catch {
        showToast('Error de conexión', 'error');
      }
    });
  });

  // Helper para serializar inputs del formulario
  function serializeForm(formEl) {
    const formData = new FormData(formEl);
    const tokens = {};
    const layout = {};
    const texts = {};

    for (const [key, value] of formData.entries()) {
      if (key.startsWith('tokens[')) {
        const tokenName = key.substring(7, key.length - 1);
        tokens[tokenName] = value;
      } else if (key.startsWith('layout[')) {
        const layoutName = key.substring(7, key.length - 1);
        layout[layoutName] = value;
      } else if (key.startsWith('texts[')) {
        const textName = key.substring(6, key.length - 1);
        texts[textName] = value;
      }
    }
    return { tokens, layout, texts };
  }

  // Ratio de contraste WCAG
  function getContrastRatio(c1, c2) {
    const l1 = getLuminance(c1);
    const l2 = getLuminance(c2);
    const lighter = Math.max(l1, l2);
    const darker = Math.min(l1, l2);
    return (lighter + 0.05) / (darker + 0.05);
  }

  function getLuminance(hex) {
    hex = hex.replace('#', '');
    if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
    const r = parseInt(hex.substr(0, 2), 16) / 255;
    const g = parseInt(hex.substr(2, 2), 16) / 255;
    const b = parseInt(hex.substr(4, 2), 16) / 255;

    const a = [r, g, b].map(v => (v <= 0.03928) ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4));
    return 0.2126 * a[0] + 0.7152 * a[1] + 0.0722 * a[2];
  }

  function showToast(message, type = 'info') {
    const container = document.querySelector('.toasts') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(8px)';
      setTimeout(() => toast.remove(), 250);
    }, 4000);
  }

  function createToastContainer() {
    const c = document.createElement('div');
    c.className = 'toasts';
    c.setAttribute('role', 'status');
    c.setAttribute('aria-live', 'polite');
    document.body.appendChild(c);
    return c;
  }
}

// Auto-inicializar si está presente en el DOM
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initThemeEditor);
} else {
  initThemeEditor();
}
