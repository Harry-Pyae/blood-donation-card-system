const BASE_URL = '/api/v1'
const TIMEOUT_MS = 15000

/**
 * Normalised transport error.
 *
 * `kind` is a stable machine token the UI maps to a translated message, so no
 * server text is ever shown raw to a donor.
 */
export class ApiError extends Error {
  constructor(kind, { status = null, errors = null, cause = null } = {}) {
    super(kind)
    this.name = 'ApiError'
    this.kind = kind
    this.status = status
    /** @type {Record<string, string[]>|null} Laravel 422 field errors. */
    this.errors = errors
    this.cause = cause
  }

  get isValidation() {
    return this.kind === 'validation'
  }
}

function classify(status) {
  if (status === 404) return 'not_found'
  if (status === 429) return 'rate_limited'
  if (status >= 500) return 'server'

  return 'request'
}

async function request(path, { method = 'GET', body = null, signal = null } = {}) {
  const timer = new AbortController()
  const timeoutId = setTimeout(() => timer.abort('timeout'), TIMEOUT_MS)

  // Caller aborts (navigation away) and our timeout both need to cancel it.
  const onExternalAbort = () => timer.abort('external')
  signal?.addEventListener('abort', onExternalAbort)

  let response

  try {
    response = await fetch(`${BASE_URL}${path}`, {
      method,
      signal: timer.signal,
      headers: {
        Accept: 'application/json',
        ...(body ? { 'Content-Type': 'application/json' } : {}),
      },
      ...(body ? { body: JSON.stringify(body) } : {}),
    })
  } catch (error) {
    if (signal?.aborted) throw error

    throw new ApiError(timer.signal.reason === 'timeout' ? 'timeout' : 'network', {
      cause: error,
    })
  } finally {
    clearTimeout(timeoutId)
    signal?.removeEventListener('abort', onExternalAbort)
  }

  if (response.status === 422) {
    const payload = await response.json().catch(() => ({}))

    throw new ApiError('validation', {
      status: 422,
      errors: payload.errors ?? {},
    })
  }

  if (!response.ok) {
    throw new ApiError(classify(response.status), { status: response.status })
  }

  if (response.status === 204) return null

  try {
    return await response.json()
  } catch (error) {
    throw new ApiError('malformed', { status: response.status, cause: error })
  }
}

export function get(path, options) {
  return request(path, { ...options, method: 'GET' })
}

export function post(path, body, options) {
  return request(path, { ...options, method: 'POST', body })
}

/** Unwraps the `{ data: ... }` envelope every API Resource returns. */
export function unwrap(payload) {
  return payload?.data ?? null
}
