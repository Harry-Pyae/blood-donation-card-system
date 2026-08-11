import { beforeEach, describe, expect, it, vi } from 'vitest'
import { clearJourney, journey, rememberDonor } from '../src/stores/journey'

describe('journey state', () => {
  beforeEach(() => {
    clearJourney()
  })

  it('holds the donor reference for the booking step', () => {
    rememberDonor({ reference: 'BC-260811-ABCDE', phone: '+95 9421000111' })

    expect(journey.donorReference).toBe('BC-260811-ABCDE')
    expect(journey.donorPhone).toBe('+95 9421000111')
  })

  it('never writes donor details to persistent storage', () => {
    const setItem = vi.fn()
    vi.stubGlobal('localStorage', { setItem, getItem: () => null, removeItem: vi.fn() })
    vi.stubGlobal('sessionStorage', { setItem, getItem: () => null, removeItem: vi.fn() })

    rememberDonor({ reference: 'BC-260811-SECRET', phone: '+95 9421000999' })

    expect(setItem).not.toHaveBeenCalled()
    vi.unstubAllGlobals()
  })

  it('clears on request', () => {
    rememberDonor({ reference: 'BC-260811-ABCDE', phone: '+95 9421000111' })
    clearJourney()

    expect(journey.donorReference).toBeNull()
  })
})
