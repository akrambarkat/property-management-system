import { mockData, mockCurrencies, mockSettings } from './mock'

let mockMode = false

export function setMockMode(enabled) {
  mockMode = enabled
  localStorage.setItem('mock_mode', enabled ? '1' : '0')
}

export function initMockMode() {
  mockMode = localStorage.getItem('mock_mode') === '1'
}

export function isMockMode() {
  return mockMode
}

const endpoints = [
  { method: 'get', path: '/locations', data: mockData.locations },
  { method: 'post', path: '/locations', data: mockData.locations[0] },
  { method: 'get', path: '/buildings', data: mockData.buildings },
  { method: 'post', path: '/buildings', data: mockData.buildings[0] },
  { method: 'get', path: '/units', data: mockData.units },
  { method: 'post', path: '/units', data: mockData.units[0] },
  { method: 'get', path: '/tenants', data: mockData.tenants },
  { method: 'post', path: '/tenants', data: mockData.tenants[0] },
  { method: 'get', path: '/contracts', data: mockData.contracts },
  { method: 'post', path: '/contracts', data: mockData.contracts[0] },
  { method: 'get', path: '/invoices', data: mockData.invoices },
  { method: 'post', path: '/invoices', data: mockData.invoices[0] },
  { method: 'get', path: '/payments', data: mockData.payments },
  { method: 'post', path: '/payments', data: mockData.payments[0] },
  { method: 'get', path: '/utility-readings', data: mockData.utility_readings },
  { method: 'post', path: '/utility-readings', data: mockData.utility_readings[0] },
  { method: 'get', path: '/expenses', data: mockData.expenses },
  { method: 'post', path: '/expenses', data: mockData.expenses[0] },
  { method: 'get', path: '/maintenance', data: mockData.maintenance_requests },
  { method: 'post', path: '/maintenance', data: mockData.maintenance_requests[0] },
  { method: 'get', path: '/users', data: mockData.users },
  { method: 'post', path: '/users', data: mockData.users[0] },
  { method: 'get', path: '/currencies', data: mockCurrencies },
  { method: 'get', path: '/settings', data: mockSettings },
  { method: 'get', path: '/reports/dashboard', data: mockData.report },
  { method: 'get', path: '/reports/profit-loss', data: mockData.report },
  { method: 'get', path: '/reports/income', data: mockData.report },
  { method: 'get', path: '/reports/expenses', data: mockData.report },
]

export function getMockResponse(method, url) {
  return new Promise((resolve) => {
    const cleanUrl = url.replace(/\/\d+(\/edit)?$/, '').split('?')[0]
    const endpoint = endpoints.find(e => e.method === method && cleanUrl.endsWith(e.path))

    setTimeout(() => {
      if (endpoint) {
        resolve({ status: 200, statusText: 'OK', data: { success: true, data: endpoint.data, message: 'تم بنجاح' } })
      } else {
        resolve({ status: 200, statusText: 'OK', data: { success: true, data: [], message: 'تم' } })
      }
    }, 200)
  })
}
