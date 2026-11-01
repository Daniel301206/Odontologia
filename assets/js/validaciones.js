document.addEventListener('DOMContentLoaded', () => {
  const searchForm = document.querySelector('[data-table-filter]');
  const searchInput = searchForm?.querySelector('.search-input');
  let filterTable = () => {};

  if (searchForm && searchInput) {
    filterTable = () => {
      const table = document.querySelector(searchForm.dataset.tableFilter);
      const tbody = table?.querySelector('tbody');
      if (!tbody) return;

      let emptyRow = tbody.querySelector('.search-empty');
      if (!emptyRow) {
        emptyRow = document.createElement('tr');
        emptyRow.className = 'search-empty';
        const cell = document.createElement('td');
        cell.colSpan = table.querySelectorAll('thead th').length;
        emptyRow.append(cell);
        tbody.append(emptyRow);
      }

      const query = searchInput.value.trim().toLocaleLowerCase();
      emptyRow.querySelector('td').textContent = query
        ? 'No se encontraron resultados.'
        : 'No hay registros para mostrar.';
      let visibleRows = 0;
      tbody.querySelectorAll('tr:not(.search-empty)').forEach((row) => {
        const matches = row.textContent.toLocaleLowerCase().includes(query);
        row.hidden = !matches;
        if (matches) visibleRows += 1;
      });
      emptyRow.hidden = visibleRows > 0;
    };

    // El submit cubre Enter y el botón Buscar; el evento input filtra en vivo.
    searchForm.addEventListener('submit', (event) => {
      event.preventDefault();
      filterTable();
    });
    searchInput.addEventListener('input', filterTable);
    filterTable();
  }

  const dialog = document.querySelector('#record-dialog');
  const form = dialog?.querySelector('form[data-ajax-save]');
  const title = dialog?.querySelector('#editor-title');
  const formFeedback = dialog?.querySelector('#form-feedback');

  const showFormMessage = (message, isError = false) => {
    if (!formFeedback) return;
    formFeedback.replaceChildren();
    if (!message) return;

    const notice = document.createElement('div');
    notice.className = isError ? 'notice error' : 'notice';
    notice.setAttribute(isError ? 'role' : 'status', isError ? 'alert' : 'status');
    notice.textContent = message;
    formFeedback.append(notice);
  };

  const clearForm = () => {
    if (!form) return;
    form.querySelectorAll('select[data-current-value-select] option[data-current-value]').forEach((option) => option.remove());
    form.reset();
    form.querySelector('[name="id"]').value = '';
    form.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach((field) => {
      field.value = '';
    });
    showFormMessage('');
  };

  const openForNew = () => {
    clearForm();
    if (title) title.textContent = `Nuevo ${dialog.dataset.moduleTitle}`;
    dialog.showModal();
  };

  const openForEdit = (link) => {
    let record;
    try {
      record = JSON.parse(link.dataset.record || '{}');
    } catch (error) {
      console.error('No se pudieron leer los datos de esta fila.', error);
      return;
    }

    clearForm();
    form.querySelectorAll('[name]').forEach((field) => {
      if (field.name === 'id') {
        field.value = record[dialog.dataset.primaryKey] ?? link.dataset.id ?? '';
      } else if (field.name in record) {
        let value = record[field.name] ?? '';
        if (field.type === 'datetime-local' && value) {
          value = String(value).replace(' ', 'T').slice(0, 16);
        }
        if (field.matches('select[data-current-value-select]') && value !== ''
          && !Array.from(field.options).some((option) => option.value === String(value))) {
          const currentOption = document.createElement('option');
          currentOption.value = String(value);
          currentOption.textContent = `Valor actual: ${value}`;
          currentOption.dataset.currentValue = 'true';
          field.add(currentOption, 1);
        }
        field.value = value;
      }
    });

    if (title) title.textContent = `Editar ${dialog.dataset.moduleTitle}`;
    dialog.dataset.editing = 'true';
    dialog.showModal();
  };

  document.querySelector('[data-open-record-dialog]')?.addEventListener('click', openForNew);
  dialog?.querySelectorAll('[data-close-record-dialog]').forEach((button) => {
    button.addEventListener('click', () => dialog.close());
  });
  dialog?.addEventListener('click', (event) => {
    if (event.target === dialog) dialog.close();
  });

  // Intercepta Editar y carga el registro asociado a esa fila en el modal.
  document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-edit-record]');
    if (!link || !dialog || !form) return;
    event.preventDefault();
    openForEdit(link);
  });

  // Conserva la edición del servidor como respaldo si el modal llega con ?edit=ID.
  if (dialog?.dataset.editing === 'true') dialog.showModal();

  document.querySelectorAll('form[data-validate]').forEach((validatedForm) => {
    validatedForm.addEventListener('submit', async (event) => {
      if (!validatedForm.checkValidity()) {
        event.preventDefault();
        validatedForm.reportValidity();
        return;
      }
      if (!validatedForm.matches('[data-ajax-save]')) return;

      event.preventDefault();
      const submitButton = validatedForm.querySelector('[type="submit"]');
      submitButton.disabled = true;
      showFormMessage('');

      try {
        const target = new URL(window.location.href);
        target.searchParams.delete('edit');
        target.searchParams.delete('q');
        target.hash = '';
        const response = await fetch(target, {
          method: 'POST',
          body: new FormData(validatedForm),
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const html = await response.text();
        const updatedDocument = new DOMParser().parseFromString(html, 'text/html');
        const errorNotice = updatedDocument.querySelector('#module-feedback .notice.error, .main-content > .notice.error');
        const successNotice = updatedDocument.querySelector('#module-feedback .notice[role="status"]');
        const updatedTable = updatedDocument.querySelector('#records-panel');

        if (!response.ok || errorNotice || !successNotice || !updatedTable) {
          showFormMessage(errorNotice?.textContent.trim() || html.trim() || 'No se pudo guardar el registro.', true);
          return;
        }

        document.querySelector('#records-panel')?.replaceWith(updatedTable);
        filterTable();
        const currentFeedback = document.querySelector('#module-feedback');
        const updatedFeedback = updatedDocument.querySelector('#module-feedback');
        if (currentFeedback && updatedFeedback) currentFeedback.replaceChildren(...updatedFeedback.childNodes);

        clearForm();
        if (title) title.textContent = `Nuevo ${dialog.dataset.moduleTitle}`;
        dialog.dataset.editing = 'false';
        dialog.close();
      } catch (error) {
        showFormMessage('No se pudo conectar con el servidor. Inténtalo nuevamente.', true);
      } finally {
        submitButton.disabled = false;
      }
    });
  });
});
