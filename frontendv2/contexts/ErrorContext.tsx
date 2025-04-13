import React, { createContext, useContext, useState } from 'react';

type ErrorContextType = {
  error: string | null;
  setError: (error: string | null) => void;
  clearError: () => void;
};

const ErrorContext = createContext<ErrorContextType | undefined>(undefined);

export function useError() {
  const context = useContext(ErrorContext);
  if (context === undefined) {
    throw new Error('useError must be used within an ErrorProvider');
  }
  return context;
}

export function ErrorProvider({ children }: { children: React.ReactNode }) {
  const [error, setErrorState] = useState<string | null>(null);

  const setError = (newError: string | null) => {
    setErrorState(newError);
    
    // Auto-clear error after 5 seconds if not null
    if (newError) {
      setTimeout(() => {
        setErrorState(null);
      }, 5000);
    }
  };

  const clearError = () => {
    setErrorState(null);
  };

  const value = {
    error,
    setError,
    clearError
  };

  return (
    <ErrorContext.Provider value={value}>
      {children}
    </ErrorContext.Provider>
  );
}