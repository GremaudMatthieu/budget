import React, { createContext, useContext, useState, useEffect, useCallback, ReactNode, useRef } from 'react';
import uuid from 'react-native-uuid';
import { useAuth } from './AuthContext';
import { envelopeService, Envelope, EnvelopesData, EnvelopeDetails } from '../services/envelopeService';

interface EnvelopeContextType {
  envelopesData: EnvelopesData | null;
  currentEnvelopeDetails: EnvelopeDetails | null;
  loading: boolean;
  error: string | null;
  refreshEnvelopes: (force?: boolean, limit?: number) => Promise<void>;
  fetchEnvelopeDetails: (envelopeId: string) => Promise<EnvelopeDetails | null>;
  createEnvelope: (name: string, targetBudget: string, currency: string) => Promise<void>;
  deleteEnvelope: (envelopeId: string, setError: (error: string | null) => void) => Promise<void>;
  creditEnvelope: (envelopeId: string, amount: string, description: string, setError: (error: string | null) => void) => Promise<void>;
  debitEnvelope: (envelopeId: string, amount: string, description: string, setError: (error: string | null) => void) => Promise<void>;
  updateEnvelopeName: (envelopeId: string, name: string, setError: (error: string | null) => void) => Promise<void>;
  updateTargetBudget: (envelopeId: string, targetedAmount: string, currentAmount: string, setError: (error: string | null) => void) => Promise<void>;
}

const EnvelopeContext = createContext<EnvelopeContextType | undefined>(undefined);

export const useEnvelopes = (): EnvelopeContextType => {
  const context = useContext(EnvelopeContext);
  if (!context) {
    throw new Error('useEnvelopes must be used within an EnvelopeProvider');
  }
  return context;
};

export const EnvelopeProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [envelopesData, setEnvelopesData] = useState<EnvelopesData | null>(null);
  const [currentEnvelopeDetails, setCurrentEnvelopeDetails] = useState<EnvelopeDetails | null>(null);
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [pendingRequests, setPendingRequests] = useState<Record<string, boolean>>({});

  const { user } = useAuth();

  const updateEnvelopeState = useCallback((updatedEnvelope: Envelope) => {
    setEnvelopesData(prev => {
      if (!prev) return prev;

      const existingEnvelopeIndex = prev.envelopes.findIndex(env => env.uuid === updatedEnvelope.uuid);

      if (existingEnvelopeIndex === -1) {
        return {
          ...prev,
          envelopes: [...prev.envelopes, updatedEnvelope],
          totalItems: prev.totalItems + 1
        };
      }

      return {
        ...prev,
        envelopes: prev.envelopes.map(env =>
          env.uuid === updatedEnvelope.uuid ? updatedEnvelope : env
        )
      };
    });
  }, []);

  const refreshEnvelopes = useCallback(async (force?: boolean, limit?: number) => {
    setLoading(true);
    setError(null);
    try {
      const updatedEnvelopes = await envelopeService.listEnvelopes(limit);
      setEnvelopesData(updatedEnvelopes);
    } catch (err) {
      setError("Failed to refresh envelopes");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (user && !envelopesData) {
      refreshEnvelopes();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [user]);

  // Fetch details for a specific envelope
  const fetchEnvelopeDetails = useCallback(async (envelopeId: string): Promise<EnvelopeDetails | null> => {
    try {
      const details = await envelopeService.getEnvelopeDetails(envelopeId);
      setCurrentEnvelopeDetails(details);
      return details;
    } catch (err) {
      console.error('Failed to fetch envelope details:', err);
      return null;
    }
  }, []);

  // CRUD operations - unchanged...
  const createEnvelope = async (name: string, targetBudget: string, currency: string) => {
    setLoading(true);
    const requestId = uuid.v4();
    // No optimistic update for new envelopes; always fetch from backend after creation

    try {
      // Make sure we explicitly pass all required fields
      await envelopeService.createEnvelope({
        uuid: requestId,
        name,
        targetedAmount: targetBudget,
        currency,
        currentAmount: "0"
      }, requestId);
      console.log(`Envelope creation API call complete for request ID: ${requestId}`);
      // Always refresh after creation to get the latest data
      await refreshEnvelopes();
    } catch (err: any) {
      // Rollback on error
      setPendingRequests(prev => {
        const updated = { ...prev };
        delete updated[requestId];
        return updated;
      });

      setError(err.message || "Failed to create envelope");
      console.error(`Error creating envelope: ${err.message || "Failed to create envelope"}`);
    } finally {
      setLoading(false);
    }
  };

  const deleteEnvelope = async (envelopeId: string, setError: (error: string | null) => void) => {
    const requestId = uuid.v4();

    setPendingRequests(prev => ({
      ...prev,
      [requestId]: true
    }));

    try {
      await envelopeService.deleteEnvelope(envelopeId, requestId);
      // WebSocket event will handle the UI update
      
      // Safety timeout: clear pending state after 5 seconds if no WebSocket event is received
      setTimeout(() => {
        setPendingRequests(prev => {
          if (prev[requestId]) {
            console.log(`Fallback timeout for delete envelope ${envelopeId}: clearing pending state`);
            const updated = { ...prev };
            delete updated[requestId];
            return updated;
          }
          return prev;
        });
      }, 5000);
    } catch (err) {
      console.error("Delete envelope error:", err);
      setError("Failed to delete envelope");

      setPendingRequests(prev => {
        const updated = { ...prev };
        delete updated[requestId];
        return updated;
      });

      throw err;
    }
  };

  const creditEnvelope = async (
    envelopeId: string,
    amount: string,
    description: string,
    setError: (error: string | null) => void
  ) => {
    const requestId = uuid.v4();

    setPendingRequests(prev => ({
      ...prev,
      [requestId]: true
    }));

    try {
      await envelopeService.creditEnvelope(envelopeId, amount, description, requestId);
      // WebSocket event will handle the UI update
      
      // Safety timeout: clear pending state after 5 seconds if no WebSocket event is received
      setTimeout(() => {
        setPendingRequests(prev => {
          if (prev[requestId]) {
            console.log(`Fallback timeout for credit envelope ${envelopeId}: clearing pending state`);
            const updated = { ...prev };
            delete updated[requestId];
            return updated;
          }
          return prev;
        });
      }, 5000);
    } catch (err) {
      console.error("Credit envelope error:", err);
      setError("Failed to credit envelope");

      setPendingRequests(prev => {
        const updated = { ...prev };
        delete updated[requestId];
        return updated;
      });

      throw err;
    }
  };

  const debitEnvelope = async (
    envelopeId: string,
    amount: string,
    description: string,
    setError: (error: string | null) => void
  ) => {
    const requestId = uuid.v4();

    setPendingRequests(prev => ({
      ...prev,
      [requestId]: true
    }));

    try {
      await envelopeService.debitEnvelope(envelopeId, amount, description, requestId);
      // WebSocket event will handle the UI update
      
      // Safety timeout: clear pending state after 5 seconds if no WebSocket event is received
      setTimeout(() => {
        setPendingRequests(prev => {
          if (prev[requestId]) {
            console.log(`Fallback timeout for debit envelope ${envelopeId}: clearing pending state`);
            const updated = { ...prev };
            delete updated[requestId];
            return updated;
          }
          return prev;
        });
      }, 5000);
    } catch (err) {
      console.error("Debit envelope error:", err);
      setError("Failed to debit envelope");

      setPendingRequests(prev => {
        const updated = { ...prev };
        delete updated[requestId];
        return updated;
      });

      throw err;
    }
  };

  const updateEnvelopeName = async (
    envelopeId: string,
    name: string,
    setError: (error: string | null) => void
  ) => {
    const requestId = uuid.v4();

    setPendingRequests(prev => ({
      ...prev,
      [requestId]: true
    }));

    try {
      // Changed from updateEnvelopeName to nameEnvelope to match service implementation
      await envelopeService.nameEnvelope(envelopeId, name, requestId);
      // WebSocket event will handle the UI update
      
      // Safety timeout: clear pending state after 5 seconds if no WebSocket event is received
      setTimeout(() => {
        setPendingRequests(prev => {
          if (prev[requestId]) {
            console.log(`Fallback timeout for rename envelope ${envelopeId}: clearing pending state`);
            const updated = { ...prev };
            delete updated[requestId];
            return updated;
          }
          return prev;
        });
      }, 5000);
    } catch (err) {
      console.error("Update envelope name error:", err);
      setError("Failed to update envelope name");

      setPendingRequests(prev => {
        const updated = { ...prev };
        delete updated[requestId];
        return updated;
      });

      throw err;
    }
  };

  const updateTargetBudget = async (
    envelopeId: string,
    targetedAmount: string,
    currentAmount: string,
    setError: (error: string | null) => void
  ) => {
    const requestId = uuid.v4();

    setPendingRequests(prev => ({
      ...prev,
      [requestId]: true
    }));

    try {
      await envelopeService.updateTargetBudget(envelopeId, targetedAmount, currentAmount, requestId);
      // WebSocket event will handle the UI update
      
      // Safety timeout: clear pending state after 5 seconds if no WebSocket event is received
      setTimeout(() => {
        setPendingRequests(prev => {
          if (prev[requestId]) {
            console.log(`Fallback timeout for update target budget ${envelopeId}: clearing pending state`);
            const updated = { ...prev };
            delete updated[requestId];
            return updated;
          }
          return prev;
        });
      }, 5000);
    } catch (err) {
      console.error("Update target budget error:", err);
      setError("Failed to update target budget");

      setPendingRequests(prev => {
        const updated = { ...prev };
        delete updated[requestId];
        return updated;
      });

      throw err;
    }
  };

  const value = {
    envelopesData,
    currentEnvelopeDetails,
    loading,
    error,
    refreshEnvelopes,
    fetchEnvelopeDetails,
    createEnvelope,
    deleteEnvelope,
    creditEnvelope,
    debitEnvelope,
    updateEnvelopeName,
    updateTargetBudget,
  };

  return (
    <EnvelopeContext.Provider value={value}>
      {children}
    </EnvelopeContext.Provider>
  );
};