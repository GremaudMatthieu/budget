export function normalizeAmountInput(value: string): string {
  let v = value.replace(/,/g, '.');
  if (v.startsWith('.')) v = '0' + v;
  const parts = v.split('.');
  // Limit integer part to 10 digits
  let intPart = parts[0].slice(0, 10);
  if (parts.length > 1) {
    // Limit decimal part to 2 digits
    let decPart = parts[1].slice(0, 2);
    v = intPart + '.' + decPart;
  } else {
    v = intPart;
  }
  return v;
} 