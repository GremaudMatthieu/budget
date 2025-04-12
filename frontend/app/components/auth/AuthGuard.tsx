'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { authService } from '../../services/auth';
import { useAppContext } from '../../providers';
import { api } from '../../infrastructure/api';
import Loading from '../Loading';

interface AuthGuardProps {
  children: React.ReactNode;
}

export function AuthGuard({ children }: AuthGuardProps) {
  const router = useRouter();
  const { state, setState } = useAppContext();
  const [isLoading, setIsLoading] = useState(true);
  const [authenticated, setAuthenticated] = useState(false);

  useEffect(() => {
    const checkAuth = async () => {
      console.log('AuthGuard: Checking authentication state');
      
      // Check if JWT token exists
      const isTokenPresent = authService.isAuthenticated();
      console.log('AuthGuard: Token present:', isTokenPresent);
      
      try {
        if (isTokenPresent) {
          // If token exists but state doesn't reflect it, try to fetch user data
          if (!state.isAuthenticated || !state.user) {
            console.log('AuthGuard: Token exists but state is not authenticated, fetching user data');
            
            try {
              const userData = await api.queries.getCurrentUser();
              console.log('AuthGuard: User data fetched successfully');
              
              // Update app context
              setState(prev => ({
                ...prev,
                user: userData,
                isAuthenticated: true,
                loading: false
              }));
              
              setAuthenticated(true);
            } catch (err) {
              console.error('AuthGuard: Failed to fetch user data, redirecting to signin', err);
              // Token is invalid or expired and refresh failed
              await authService.logout();
              router.replace('/signin');
            }
          } else {
            // Already authenticated in state
            console.log('AuthGuard: Already authenticated in state');
            setAuthenticated(true);
          }
        } else {
          // No token, redirect to login
          console.log('AuthGuard: No token, redirecting to signin');
          if (state.isAuthenticated) {
            // State says authenticated but no token, fix state
            setState(prev => ({
              ...prev,
              user: null,
              isAuthenticated: false,
              loading: false
            }));
          }
          router.replace('/signin');
        }
      } finally {
        setIsLoading(false);
      }
    };
    
    checkAuth();
  }, [router, state.isAuthenticated, state.user, setState]);
  
  if (isLoading) {
    return <Loading message="Verifying authentication..." />;
  }
  
  return authenticated ? <>{children}</> : null;
}
