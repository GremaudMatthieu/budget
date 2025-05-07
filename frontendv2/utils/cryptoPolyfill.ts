// This polyfill adds crypto.getRandomValues() support needed by UUID generation in Expo Go
import { getRandomValues } from 'expo-crypto';

if (typeof global.crypto === 'undefined') {
  global.crypto = {} as Crypto;
}
if (typeof global.crypto.getRandomValues === 'undefined') {
  global.crypto.getRandomValues = getRandomValues as typeof global.crypto.getRandomValues;
}
// Note: This file must be imported before any UUID usage in your application