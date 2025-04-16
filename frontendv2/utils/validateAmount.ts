/**
 * Validate if a string is a valid amount
 * - Must be a number
 * - Can have at most one decimal point
 * - Cannot start with multiple zeros
 * - Can be negative
 * 
 * @param amount The amount string to validate
 * @returns True if the amount is valid
 */
export default function validateAmount(amount: string): boolean {
  // Empty string is valid (for clearing input)
  if (amount === '') {
    return true;
  }
  
  // Check for valid numeric format
  // Allows: 
  // - Optional negative sign
  // - Digits
  // - Optional decimal point followed by digits
  const regex = /^-?(?:0|[1-9]\d*)(?:\.\d*)?$/;
  
  return regex.test(amount);
}