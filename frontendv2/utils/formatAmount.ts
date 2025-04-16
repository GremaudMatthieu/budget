/**
 * Format an amount string for display
 * Ensures proper formatting of numeric values
 * 
 * @param amount The amount string to format
 * @returns Formatted amount string
 */
export default function formatAmount(amount: string): string {
  // Handle empty input
  if (!amount) {
    return '';
  }
  
  // Convert to number and back to string to normalize format
  const num = parseFloat(amount);
  
  // Return empty string if not a valid number
  if (isNaN(num)) {
    return '';
  }
  
  // If it's a whole number, return without decimal places
  if (Number.isInteger(num)) {
    return num.toString();
  }
  
  // Otherwise return with appropriate decimal places (max 2)
  return num.toFixed(2).replace(/\.?0+$/, '');
}