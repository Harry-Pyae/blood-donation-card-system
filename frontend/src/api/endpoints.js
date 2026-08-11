import { get, post, unwrap } from './client'

export const fetchCentres = (o) => get('/centres', o).then((r) => unwrap(r) ?? [])
export const fetchNrcReference = (o) => get('/donors/nrc-reference', o).then(unwrap)
export const fetchBookingOptions = (o) => get('/appointments/options', o).then(unwrap)

export const registerDonor = (payload, o) =>
  post('/donors/register', payload, o).then(unwrap)

export const bookAppointment = (payload, o) =>
  post('/appointments', payload, o).then(unwrap)

export const checkAppointment = (payload, o) =>
  post('/appointments/check', payload, o).then(unwrap)

export const checkCard = (payload, o) => post('/cards/check', payload, o).then(unwrap)
