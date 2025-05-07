import React, { createContext, useContext, useState, ReactNode, useEffect } from 'react';
import { ErrorMessage } from '@/components/ErrorMessage';
import { View } from 'react-native';
import Toast from 'react-native-root-toast';

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
  
  useEffect(() => {
    let toast: any;
    if (error) {
      toast = Toast.show(error, {
        duration: Toast.durations.SHORT,
        position: Toast.positions.BOTTOM,
        shadow: false,
        animation: true,
        hideOnPress: true,
        backgroundColor: 'rgba(30, 41, 59, 0.98)',
        textColor: '#fff',
        containerStyle: {
          borderRadius: 16,
          marginBottom: 48,
          paddingHorizontal: 24,
          paddingVertical: 16,
          minWidth: 180,
          maxWidth: '90%',
          alignSelf: 'center',
        },
        textStyle: {
          color: '#fff',
          fontSize: 16,
          fontWeight: '600',
          textAlign: 'center',
        },
        onHidden: () => setError(null),
      });
    }
    return () => {
      if (toast) Toast.hide(toast);
    };
  }, [error]);

  return (
    <ErrorContext.Provider value={{ error, setError }}>
      {children}
    </ErrorContext.Provider>
  );
};
