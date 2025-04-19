/**
 * Handles UTC date adjustments to ensure correct month/year display
 * 
 * When dates are stored in UTC, there's often a day difference that can 
 * affect month boundaries when displayed locally. This utility provides
 * methods to compensate for this behavior.
 */

/**
 * Adjusts a UTC date to ensure the correct month is displayed.
 * When a date is stored in UTC as YYYY-MM-01T00:00:00Z, it might display 
 * as the previous month in some timezones.
 */
export function normalizeMonthYear(date: Date): { month: number; year: number } {
  // Set the date to noon to avoid timezone issues at day boundaries
  const normalizedDate = new Date(date);
  normalizedDate.setHours(12, 0, 0, 0);
  
  // Get the UTC month/year
  return {
    month: normalizedDate.getMonth() + 1, // Convert to 1-based month
    year: normalizedDate.getFullYear()
  };
}

/**
 * Creates a UTC date that's safe for storage and will display correctly
 * in all timezones for the given month and year.
 */
export function createUtcSafeDate(year: number, month: number): Date {
  // Create a date for the 15th day of the month to avoid timezone boundary issues
  // Using the middle of the month ensures it will be the correct month in all timezones
  const date = new Date(Date.UTC(year, month - 1, 15, 12, 0, 0, 0));
  return date;
}

/**
 * Gets the key format for month/year used in the API response calendar
 */
export function getMonthYearKey(year: number, month: number): { yearKey: string, monthKey: string } {
  return {
    yearKey: year.toString(),
    monthKey: month.toString()
  };
}

/**
 * Format month and year for display, considering timezone adjustments
 */
export function formatMonthYear(year: number, month: number): string {
  const date = new Date(year, month - 1, 15); // Using 15th of month to avoid boundary issues
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
}