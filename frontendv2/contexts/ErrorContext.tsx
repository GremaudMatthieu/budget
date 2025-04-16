import React, { createContext, useContext, useState, ReactNode } from 'react';
import { ErrorMessage } from '@/components/ErrorMessage';
import { View } from 'react-native';

type ErrorContextType = {
  error: string | null;
  setError: (message: string | null) => void;
};

const ErrorContext = createContext<ErrorContextType | undefined>(undefined);

export const useErrorContext = (): ErrorContextType => {
  const context = useContext(ErrorContext);
  
  if (context === undefined) {
    throw new Error('useErrorContext must be used within an ErrorProvider');
  }
  
  return context;
};

type ErrorProviderProps = {
  children: ReactNode;
};

export const ErrorProvider: React.FC<ErrorProviderProps> = ({ children }) => {
  const [error, setError] = useState<string | null>(null);
  
  const clearError = () => {
    setError(null);
  };

  return (
    <ErrorContext.Provider value={{ error, setError }}>
      {error && (
        <View className="absolute top-2 left-2 right-2 z-50">
          <ErrorMessage message={error} onDismiss={clearError} />
        </View>
      )}
      {children}
    </ErrorContext.Provider>
  );
};
