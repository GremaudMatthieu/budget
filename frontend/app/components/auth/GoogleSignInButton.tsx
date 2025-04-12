'use client';

import { useState } from 'react';
import Image from 'next/image';

interface GoogleSignInButtonProps {
  className?: string;
}

export default function GoogleSignInButton({ className = '' }: GoogleSignInButtonProps) {
  const [isLoading, setIsLoading] = useState(false);

  const handleGoogleSignIn = async () => {
    setIsLoading(true);
    try {
      // Redirect to the backend OAuth2 endpoint
      // Fix: Remove '/api' from the path as it's likely already included in NEXT_PUBLIC_API_URL
      window.location.href = `${process.env.NEXT_PUBLIC_API_URL}/connect/google`;
    } catch (error) {
      console.error('Google Sign-in error:', error);
      setIsLoading(false);
    }
  };

  return (
    <button
      onClick={handleGoogleSignIn}
      disabled={isLoading}
      className={`flex items-center justify-center w-full px-4 py-2 space-x-3 text-sm text-gray-700 transition-colors duration-300 border border-gray-300 rounded-md bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 ${className}`}
    >
      {isLoading ? (
        <div className="w-5 h-5 border-2 border-gray-300 border-t-indigo-600 rounded-full animate-spin"></div>
      ) : (
        <Image 
          src="/images/google-logo.svg" 
          alt="Google Logo" 
          width={20} 
          height={20} 
        />
      )}
      <span>{isLoading ? 'Signing in...' : 'Sign in with Google'}</span>
    </button>
  );
}