import { afterEach, describe, expect, it, vi } from 'vitest'
import { ApiError, get, post, unwrap } from '../src/api/client'

function mockFetch(response) {
  global.fetch = vi.fn().mockResolvedValue(response)
}

function jsonResponse(status, body) {
  return {
    ok: status >= 200 && status < 300,
    status,
    json: async () => body,
  }
}

afterEach(() => {
  vi.restoreAllMocks()
})

describe('api client error mapping', () => {
  it('maps a 422 into a validation error carrying the field errors', async () => {
    mockFetch(
      jsonResponse(422, {
        message: 'The given data was invalid.',
        errors: { phone: ['The phone field is required.'] },
      }),
    )

    const error = await post('/donors/register', {}).catch((e) => e)

    expect(error).toBeInstanceOf(ApiError)
    expect(error.isValidation).toBe(true)
    expect(error.errors.phone).toEqual(['The phone field is required.'])
  })

  it('maps 429 to rate_limited so the UI can explain the wait', async () => {
    mockFetch(jsonResponse(429, {}))

    const error = await post('/appointments/check', {}).catch((e) => e)

    expect(error.kind).toBe('rate_limited')
    expect(error.isValidation).toBe(false)
  })

  it('maps 404 and 500 to distinct kinds', async () => {
    mockFetch(jsonResponse(404, {}))
    expect((await get('/nope').catch((e) => e)).kind).toBe('not_found')

    mockFetch(jsonResponse(500, {}))
    expect((await get('/boom').catch((e) => e)).kind).toBe('server')
  })

  it('maps a rejected fetch to a network error rather than leaking the cause', async () => {
    global.fetch = vi.fn().mockRejectedValue(new TypeError('Failed to fetch'))

    const error = await get('/centres').catch((e) => e)

    expect(error).toBeInstanceOf(ApiError)
    expect(error.kind).toBe('network')
  })

  it('sends JSON headers only when there is a body', async () => {
    mockFetch(jsonResponse(200, { data: [] }))
    await get('/centres')
    expect(global.fetch.mock.calls[0][1].headers['Content-Type']).toBeUndefined()

    mockFetch(jsonResponse(201, { data: {} }))
    await post('/donors/register', { full_name: 'A' })
    expect(global.fetch.mock.calls[0][1].headers['Content-Type']).toBe('application/json')
  })
})

describe('unwrap', () => {
  it('returns the data envelope contents, or null when absent', () => {
    expect(unwrap({ data: [1, 2] })).toEqual([1, 2])
    expect(unwrap({})).toBeNull()
    expect(unwrap(null)).toBeNull()
  })
})
