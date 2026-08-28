/**
 * Thin fetch wrapper around the Contacts REST API (task 2).
 * Keeps every HTTP detail (base URL, headers, error shape) in one place so
 * app.js only ever deals with plain JS objects / thrown ApiError instances.
 */

class ApiError extends Error {
  /**
   * @param {string} message
   * @param {number} status
   * @param {Record<string, string>} [fieldErrors]
   */
  constructor(message, status, fieldErrors = {}) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.fieldErrors = fieldErrors;
  }
}

const ApiClient = (() => {
  const baseUrl = window.APP_CONFIG.API_BASE_URL.replace(/\/+$/, '');

  /**
   * @param {string} path
   * @param {RequestInit} [options]
   */
  async function request(path, options = {}) {
    const response = await fetch(`${baseUrl}${path}`, {
      headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
      ...options,
    });

    if (response.status === 204) {
      return null;
    }

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
      const message = payload?.message || payload?.error || `Request failed with status ${response.status}`;
      throw new ApiError(message, response.status, payload?.errors || {});
    }

    return payload?.data ?? payload;
  }

  return {
    health: () => request('/health'),

    listPeople: () => request('/people'),
    getPerson: (id) => request(`/people/${id}`),
    createPerson: (name) => request('/people', { method: 'POST', body: JSON.stringify({ name }) }),
    updatePerson: (id, name) => request(`/people/${id}`, { method: 'PUT', body: JSON.stringify({ name }) }),
    deletePerson: (id) => request(`/people/${id}`, { method: 'DELETE' }),

    createContact: (personId, type, value) =>
      request(`/people/${personId}/contacts`, { method: 'POST', body: JSON.stringify({ type, value }) }),
    updateContact: (id, type, value) =>
      request(`/contacts/${id}`, { method: 'PUT', body: JSON.stringify({ type, value }) }),
    deleteContact: (id) => request(`/contacts/${id}`, { method: 'DELETE' }),
  };
})();
