/**
 * Deposit Wizard — Asistente de Depósito Guiado de 6 Pasos
 * Validación progresiva, stepper interactivo, reordenación accesible de autores y resumen previo.
 */
export function initDepositWizard() {
  const wizard = document.getElementById('deposit-wizard');
  if (!wizard) return;

  const form = wizard.querySelector('form');
  const steps = wizard.querySelectorAll('.wizard-pane');
  const stepIndicators = wizard.querySelectorAll('.wizard-step');
  const prevBtn = wizard.querySelector('#wizard-prev-btn');
  const nextBtn = wizard.querySelector('#wizard-next-btn');
  const submitBtn = wizard.querySelector('#wizard-submit-btn');

  let currentStep = 1;
  const totalSteps = steps.length;

  function showStep(stepNumber) {
    if (stepNumber < 1 || stepNumber > totalSteps) return;
    currentStep = stepNumber;

    // Actualizar paneles
    steps.forEach((pane, idx) => {
      const active = (idx + 1) === currentStep;
      pane.style.display = active ? 'block' : 'none';
      pane.setAttribute('aria-hidden', active ? 'false' : 'true');
    });

    // Actualizar stepper
    stepIndicators.forEach((ind, idx) => {
      const num = idx + 1;
      ind.classList.remove('active', 'completed');
      if (num === currentStep) {
        ind.classList.add('active');
        ind.setAttribute('aria-current', 'step');
      } else if (num < currentStep) {
        ind.classList.add('completed');
        ind.removeAttribute('aria-current');
      } else {
        ind.removeAttribute('aria-current');
      }
    });

    // Botones de navegación
    if (prevBtn) {
      prevBtn.style.display = currentStep > 1 ? 'inline-flex' : 'none';
    }
    if (nextBtn) {
      nextBtn.style.display = currentStep < totalSteps ? 'inline-flex' : 'none';
    }
    if (submitBtn) {
      submitBtn.style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
    }

    // Si entramos al paso 6 (Resumen), actualizar resumen
    if (currentStep === 6) {
      updateSummary();
    }

    // Scroll suave a la parte superior del asistente
    wizard.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function validateCurrentStep() {
    const activePane = steps[currentStep - 1];
    if (!activePane) return true;

    const requiredFields = activePane.querySelectorAll('input[required], select[required], textarea[required]');
    let valid = true;

    requiredFields.forEach(field => {
      if (!field.value || field.value.trim() === '') {
        field.classList.add('is-invalid');
        field.style.borderColor = 'var(--danger)';
        valid = false;
      } else {
        field.classList.remove('is-invalid');
        field.style.borderColor = '';
      }
    });

    if (!valid) {
      alert('Por favor complete los campos obligatorios marcados antes de continuar.');
    }
    return valid;
  }

  function updateSummary() {
    const summaryBox = document.getElementById('wizard-summary-content');
    if (!summaryBox) return;

    const colSelect = document.getElementById('collection_id');
    const colName = colSelect ? colSelect.options[colSelect.selectedIndex]?.text : '';
    const title = document.getElementById('title')?.value || 'Sin título';
    const accessSelect = document.getElementById('access_level');
    const accessName = accessSelect ? accessSelect.options[accessSelect.selectedIndex]?.text : '';

    // Autores
    const authorInputs = document.querySelectorAll('#authors-table tbody tr input[name*="[full_name]"]');
    const authorNames = Array.from(authorInputs).map(i => i.value.trim()).filter(n => n !== '');

    summaryBox.innerHTML = `
      <div style="background:var(--surface-subtle);padding:20px;border-radius:var(--radius-sm);border:1px solid var(--border);">
        <p style="margin-bottom:8px;"><strong>Colección:</strong> ${colName || 'No seleccionada'}</p>
        <p style="margin-bottom:8px;"><strong>Título:</strong> ${title}</p>
        <p style="margin-bottom:8px;"><strong>Autores:</strong> ${authorNames.length > 0 ? authorNames.join(', ') : 'No especificados'}</p>
        <p style="margin-bottom:0;"><strong>Modalidad de acceso:</strong> ${accessName}</p>
      </div>
    `;
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      if (validateCurrentStep()) {
        showStep(currentStep + 1);
      }
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      showStep(currentStep - 1);
    });
  }

  // Permitir clic en pasos completados
  stepIndicators.forEach((ind, idx) => {
    ind.addEventListener('click', () => {
      const stepNum = idx + 1;
      if (stepNum < currentStep) {
        showStep(stepNum);
      }
    });
  });

  // Reordenación y gestión accesible de autores
  const authorsTable = document.getElementById('authors-table');
  if (authorsTable) {
    authorsTable.addEventListener('click', (e) => {
      const btn = e.target.closest('button');
      if (!btn) return;

      const row = btn.closest('tr');
      if (!row) return;

      if (btn.hasAttribute('data-remove-row')) {
        const tbody = authorsTable.querySelector('tbody');
        if (tbody && tbody.children.length > 1) {
          row.remove();
        } else {
          alert('Debe existir al menos un autor en el depósito.');
        }
      } else if (btn.hasAttribute('data-move-up')) {
        const prev = row.previousElementSibling;
        if (prev) row.parentNode.insertBefore(row, prev);
      } else if (btn.hasAttribute('data-move-down')) {
        const next = row.nextElementSibling;
        if (next) row.parentNode.insertBefore(next, row);
      }
    });
  }

  // Inicializar en el paso 1
  showStep(1);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initDepositWizard);
} else {
  initDepositWizard();
}
