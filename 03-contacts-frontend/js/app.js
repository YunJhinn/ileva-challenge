/**
 * Application state + rendering + event wiring for the contacts SPA.
 * Deliberately framework-free: the API surface (task 2) is small enough
 * that plain DOM manipulation stays readable, and it keeps this deployable
 * as static files with zero build step.
 */

const CONTACT_TYPE_LABELS = {
  phone: 'Telefone',
  email: 'E-mail',
  whatsapp: 'WhatsApp',
};

const state = {
  people: [],
  selectedPersonId: null,
  searchTerm: '',
  editingContactId: null, // null => creating a new contact
};

// --- DOM references -------------------------------------------------------

const els = {
  apiBaseLabel: document.getElementById('api-base-label'),
  apiStatus: document.getElementById('api-status'),

  peopleList: document.getElementById('people-list'),
  peopleEmpty: document.getElementById('people-empty'),
  personSearch: document.getElementById('person-search'),
  newPersonBtn: document.getElementById('new-person-btn'),

  detailPlaceholder: document.getElementById('detail-placeholder'),
  detailContent: document.getElementById('detail-content'),
  personName: document.getElementById('person-name'),
  personMeta: document.getElementById('person-meta'),
  editPersonBtn: document.getElementById('edit-person-btn'),
  deletePersonBtn: document.getElementById('delete-person-btn'),

  contactsList: document.getElementById('contacts-list'),
  contactsEmpty: document.getElementById('contacts-empty'),
  newContactBtn: document.getElementById('new-contact-btn'),

  personDialog: document.getElementById('person-dialog'),
  personForm: document.getElementById('person-form'),
  personDialogTitle: document.getElementById('person-dialog-title'),
  personNameInput: document.getElementById('person-name-input'),
  personNameError: document.getElementById('person-name-error'),

  contactDialog: document.getElementById('contact-dialog'),
  contactForm: document.getElementById('contact-form'),
  contactDialogTitle: document.getElementById('contact-dialog-title'),
  contactTypeInput: document.getElementById('contact-type-input'),
  contactValueInput: document.getElementById('contact-value-input'),
  contactValueError: document.getElementById('contact-value-error'),

  toast: document.getElementById('toast'),
};

// --- Helpers ---------------------------------------------------------------

function selectedPerson() {
  return state.people.find((p) => p.id === state.selectedPersonId) || null;
}

function showToast(message, isError = false) {
  els.toast.textContent = message;
  els.toast.classList.toggle('toast--error', isError);
  els.toast.hidden = false;
  clearTimeout(showToast._timer);
  showToast._timer = setTimeout(() => {
    els.toast.hidden = true;
  }, 3200);
}

function formatDate(isoLike) {
  const d = new Date(isoLike.replace(' ', 'T'));
  if (Number.isNaN(d.getTime())) return isoLike;
  return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

/** Runs an API call, shows a toast on ApiError, and re-throws so callers can decide what else to do. */
async function safely(promise, fallbackMessage) {
  try {
    return await promise;
  } catch (err) {
    showToast(errorMessage(err, fallbackMessage), true);
    throw err;
  }
}

/**
 * A caught error is either an ApiError (the API responded, just not with a
 * success status) or something else entirely - most commonly the browser's
 * own "Failed to fetch" when the API isn't reachable at all (wrong URL,
 * back-end not running, CORS, offline...). Both need a message the user can
 * actually see, so this never falls through silently.
 */
function errorMessage(err, fallback = 'Não foi possível conectar à API. Verifique se o back-end está rodando.') {
  return err instanceof ApiError ? err.message : fallback;
}

// --- Rendering ---------------------------------------------------------------

function renderPeopleList() {
  const term = state.searchTerm.trim().toLowerCase();
  const filtered = term
    ? state.people.filter((p) => p.name.toLowerCase().includes(term))
    : state.people;

  els.peopleList.innerHTML = '';
  els.peopleEmpty.hidden = state.people.length > 0;

  if (state.people.length > 0 && filtered.length === 0) {
    const li = document.createElement('li');
    li.className = 'empty-state';
    li.textContent = 'Nenhuma pessoa encontrada para essa busca.';
    els.peopleList.appendChild(li);
    return;
  }

  for (const person of filtered) {
    const li = document.createElement('li');
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'person-item' + (person.id === state.selectedPersonId ? ' person-item--active' : '');
    button.innerHTML = `
      <span class="person-item__name"></span>
      <span class="person-item__meta"></span>
    `;
    button.querySelector('.person-item__name').textContent = person.name;
    button.querySelector('.person-item__meta').textContent =
      `${person.contacts.length} contato${person.contacts.length === 1 ? '' : 's'}`;
    button.addEventListener('click', () => selectPerson(person.id));
    li.appendChild(button);
    els.peopleList.appendChild(li);
  }
}

function renderDetail() {
  const person = selectedPerson();

  if (!person) {
    els.detailPlaceholder.hidden = false;
    els.detailContent.hidden = true;
    return;
  }

  els.detailPlaceholder.hidden = true;
  els.detailContent.hidden = false;

  els.personName.textContent = person.name;
  els.personMeta.textContent = `Cadastrado em ${formatDate(person.created_at)}`;

  els.contactsList.innerHTML = '';
  els.contactsEmpty.hidden = person.contacts.length > 0;

  for (const contact of person.contacts) {
    const li = document.createElement('li');
    li.className = 'contact-item';
    li.innerHTML = `
      <div class="contact-item__info">
        <span class="badge badge--${contact.type}"></span>
        <span class="contact-item__value"></span>
      </div>
      <div class="contact-item__actions">
        <button type="button" class="btn btn--ghost btn--small" data-action="edit">Editar</button>
        <button type="button" class="btn btn--danger btn--small" data-action="delete">Excluir</button>
      </div>
    `;
    li.querySelector('.badge').textContent = CONTACT_TYPE_LABELS[contact.type] || contact.type;
    li.querySelector('.contact-item__value').textContent = contact.value;
    li.querySelector('[data-action="edit"]').addEventListener('click', () => openContactDialog('edit', contact));
    li.querySelector('[data-action="delete"]').addEventListener('click', () => handleDeleteContact(contact));
    els.contactsList.appendChild(li);
  }
}

// --- Data loading ---------------------------------------------------------

async function loadPeople({ preserveSelection = true } = {}) {
  const people = await safely(ApiClient.listPeople(), 'Não foi possível carregar as pessoas.');
  state.people = people;

  if (!preserveSelection || !selectedPerson()) {
    state.selectedPersonId = people.length > 0 ? people[0].id : null;
  }

  renderPeopleList();
  renderDetail();
}

function selectPerson(id) {
  state.selectedPersonId = id;
  renderPeopleList();
  renderDetail();
}

// --- Person dialog ---------------------------------------------------------

function openPersonDialog(mode) {
  els.personNameError.hidden = true;
  els.personForm.dataset.mode = mode;

  if (mode === 'edit') {
    const person = selectedPerson();
    els.personDialogTitle.textContent = 'Editar pessoa';
    els.personNameInput.value = person.name;
  } else {
    els.personDialogTitle.textContent = 'Nova pessoa';
    els.personNameInput.value = '';
  }

  els.personDialog.showModal();
  els.personNameInput.focus();
}

async function handlePersonSubmit(event) {
  event.preventDefault();
  const mode = els.personForm.dataset.mode;
  const name = els.personNameInput.value.trim();
  els.personNameError.hidden = true;

  try {
    if (mode === 'edit') {
      const person = selectedPerson();
      await ApiClient.updatePerson(person.id, name);
      showToast('Pessoa atualizada.');
    } else {
      const created = await ApiClient.createPerson(name);
      state.selectedPersonId = created.id;
      showToast('Pessoa criada.');
    }
    els.personDialog.close();
    await loadPeople();
  } catch (err) {
    if (err instanceof ApiError && err.fieldErrors.name) {
      els.personNameError.textContent = err.fieldErrors.name;
      els.personNameError.hidden = false;
    } else {
      showToast(errorMessage(err), true);
    }
  }
}

async function handleDeletePerson() {
  const person = selectedPerson();
  if (!person) return;
  if (!confirm(`Excluir "${person.name}" e todos os seus contatos?`)) return;

  try {
    await ApiClient.deletePerson(person.id);
    showToast('Pessoa excluída.');
    state.selectedPersonId = null;
    await loadPeople({ preserveSelection: false });
  } catch (err) {
    showToast(errorMessage(err), true);
  }
}

// --- Contact dialog ---------------------------------------------------------

function openContactDialog(mode, contact) {
  els.contactValueError.hidden = true;
  els.contactForm.dataset.mode = mode;
  state.editingContactId = mode === 'edit' ? contact.id : null;

  if (mode === 'edit') {
    els.contactDialogTitle.textContent = 'Editar contato';
    els.contactTypeInput.value = contact.type;
    els.contactValueInput.value = contact.value;
  } else {
    els.contactDialogTitle.textContent = 'Novo contato';
    els.contactTypeInput.value = 'phone';
    els.contactValueInput.value = '';
  }

  els.contactDialog.showModal();
  els.contactValueInput.focus();
}

async function handleContactSubmit(event) {
  event.preventDefault();
  const type = els.contactTypeInput.value;
  const value = els.contactValueInput.value.trim();
  els.contactValueError.hidden = true;

  const person = selectedPerson();
  if (!person) return;

  try {
    if (state.editingContactId) {
      await ApiClient.updateContact(state.editingContactId, type, value);
      showToast('Contato atualizado.');
    } else {
      await ApiClient.createContact(person.id, type, value);
      showToast('Contato adicionado.');
    }
    els.contactDialog.close();
    await loadPeople();
  } catch (err) {
    if (err instanceof ApiError && (err.fieldErrors.value || err.fieldErrors.type)) {
      els.contactValueError.textContent = err.fieldErrors.value || err.fieldErrors.type;
      els.contactValueError.hidden = false;
    } else {
      showToast(errorMessage(err), true);
    }
  }
}

async function handleDeleteContact(contact) {
  if (!confirm(`Excluir este contato (${contact.value})?`)) return;

  try {
    await ApiClient.deleteContact(contact.id);
    showToast('Contato excluído.');
    await loadPeople();
  } catch (err) {
    showToast(errorMessage(err), true);
  }
}

// --- Wiring ---------------------------------------------------------------

function wireEvents() {
  els.newPersonBtn.addEventListener('click', () => openPersonDialog('create'));
  els.editPersonBtn.addEventListener('click', () => openPersonDialog('edit'));
  els.deletePersonBtn.addEventListener('click', handleDeletePerson);
  els.newContactBtn.addEventListener('click', () => openContactDialog('create'));

  els.personForm.addEventListener('submit', handlePersonSubmit);
  els.contactForm.addEventListener('submit', handleContactSubmit);

  els.personSearch.addEventListener('input', (e) => {
    state.searchTerm = e.target.value;
    renderPeopleList();
  });

  document.querySelectorAll('[data-close-dialog]').forEach((btn) => {
    btn.addEventListener('click', () => btn.closest('dialog').close());
  });
}

async function checkApiHealth() {
  els.apiBaseLabel.textContent = window.APP_CONFIG.API_BASE_URL;
  try {
    await ApiClient.health();
    els.apiStatus.classList.add('status-dot--ok');
    els.apiStatus.title = 'API disponível';
  } catch {
    els.apiStatus.classList.add('status-dot--error');
    els.apiStatus.title = 'API indisponível';
    showToast('Não foi possível conectar à API. Verifique se o back-end está rodando.', true);
  }
}

async function init() {
  wireEvents();
  await checkApiHealth();
  await loadPeople({ preserveSelection: false });
}

init();
