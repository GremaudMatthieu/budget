'use client';

import { useEffect, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import Loading from '../../../components/Loading';
import { authService } from '../../../services/auth';
import { useAppContext } from '../../../providers';
import { api } from '../../../infrastructure/api';

export default function GoogleCallbackPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { setState } = useAppContext();
  const [error, setError] = useState<string | null>(null);
  const [debugInfo, setDebugInfo] = useState<string>('');
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api';

  useEffect(() => {
    const validateTokenWithBackend = async () => {
      try {
        // Get tokens from URL parameters (provided by your backend)
        const token = searchParams.get('token');
        const refreshToken = searchParams.get('refresh_token');
        const email = searchParams.get('email');

        if (!token) {
          setError('Authentication failed: No token received');
          return;
        }

        setDebugInfo(`Received token from OAuth redirect. Email: ${email || 'Not provided'}`);
        console.log('OAuth callback: Received token, validating with backend');

        // First, store the tokens
        authService.setToken(token);
        if (refreshToken) {
          localStorage.setItem('refreshToken', refreshToken);
        }

        // Critical step: Fetch the actual user data using the token
        try {
          const userData = await api.queries.getCurrentUser();
          console.log('OAuth callback: Successfully fetched user data', userData);
          setDebugInfo(prev => `${prev}\nSuccessfully fetched user data`);

          // Update app state with user data from API
          setState(prevState => ({
            ...prevState,
            isAuthenticated: true,
            user: userData,
            loading: false
          }));

          // Redirect after a short delay to ensure state is updated
          setTimeout(() => {
            console.log('OAuth callback: Redirecting to envelopes page');
            router.replace('/envelopes');
          }, 100);
        } catch (err) {
          console.error('OAuth callback: Error fetching user data:', err);
          setDebugInfo(prev => `${prev}\nError fetching user data: ${err.message || String(err)}`);
          setError('Failed to retrieve user information. Please try again.');
          
          // Clean up the invalid token
          authService.removeToken();
          localStorage.removeItem('refreshToken');
        }
      } catch (err) {
        console.error('OAuth callback general error:', err);
        setDebugInfo(prev => `${prev}\nGeneral error: ${err.message || String(err)}`);
        setError(err instanceof Error ? err.message : 'Authentication failed');
      }
    };

    validateTokenWithBackend();
  }, [searchParams, setState, router, API_URL]);

  if (error) {
    return (
      <div className="flex flex-col items-center justify-center min-h-screen bg-gray-100">
        <div className="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md">
          <h1 className="text-2xl font-bold text-center text-red-600">Authentication Error</h1>
          <p className="text-gray-700 text-center">{error}</p>
          {debugInfo && (
            <div className="mt-4 p-4 bg-gray-100 rounded overflow-auto max-h-60 text-xs">
              <h3 className="font-bold mb-2">Debug Information:</h3>
              <pre className="whitespace-pre-wrap">{debugInfo}</pre>
            </div>
          )}
          <div className="flex justify-center">
            <button
              onClick={() => router.push('/signin')}
              className="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              Return to Sign In
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="flex flex-col items-center justify-center min-h-screen bg-gray-100">
      <Loading message="Completing authentication..." />
      {debugInfo && (
        <div className="mt-4 p-4 bg-white rounded shadow overflow-auto max-h-60 text-xs max-w-md">
          <h3 className="font-bold mb-2">Debug Information:</h3>
          <pre className="whitespace-pre-wrap">{debugInfo}</pre>
        </div>
      )}
    </div>
  );
}