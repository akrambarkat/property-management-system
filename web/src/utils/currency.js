export function formatCurrency(amount, currency) {
  const symbols = {
    ILS: '₪',
    JOD: 'د.أ',
    USD: '$'
  }
  const symbol = symbols[currency] || '₪'
  const formatted = Number(amount).toLocaleString('ar-SA', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
  return `${formatted} ${symbol}`
}

export function convertAmount(amountInILS, targetCurrency, currencies) {
  const currency = currencies.find(c => c.code === targetCurrency)
  if (!currency) return amountInILS
  return amountInILS * currency.rate
}

export function getCurrencySymbol(code) {
  const symbols = { ILS: '₪', JOD: 'د.أ', USD: '$' }
  return symbols[code] || '₪'
}
