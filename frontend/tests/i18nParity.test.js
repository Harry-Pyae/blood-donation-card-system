import { describe, expect, it } from 'vitest'
import en from '../src/i18n/en'
import my from '../src/i18n/my'

/** Recursively collects dotted key paths so both locales can be compared. */
function paths(object, prefix = '') {
  return Object.entries(object).flatMap(([key, value]) => {
    const path = prefix ? `${prefix}.${key}` : key

    return typeof value === 'object' && value !== null ? paths(value, path) : [path]
  })
}

describe('translation parity', () => {
  it('defines exactly the same keys in English and Myanmar', () => {
    const enPaths = paths(en).sort()
    const myPaths = paths(my).sort()

    expect(myPaths.filter((p) => !enPaths.includes(p))).toEqual([])
    expect(enPaths.filter((p) => !myPaths.includes(p))).toEqual([])
  })

  it('has no empty strings in either locale', () => {
    for (const [name, messages] of [
      ['en', en],
      ['my', my],
    ]) {
      for (const path of paths(messages)) {
        const value = path.split('.').reduce((acc, key) => acc[key], messages)
        expect(value, `${name}.${path} is empty`).not.toBe('')
      }
    }
  })
})
