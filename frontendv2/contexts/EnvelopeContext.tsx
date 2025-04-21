import React, { createContext, useContext, useState, useEffect, useCallback, ReactNode, useRef } from 'react';
import { v4 as uuidv4 } from 'uuid';
import { useSocket } from './SocketContext';
import { useAuth } from './AuthContext';
import { envelopeService, Envelope, EnvelopesData, EnvelopeDetails } from '../services/envelopeService';

interface EnvelopeContextType {
  envelopesData: EnvelopesData | null;
  currentEnvelopeDetails: EnvelopeDetails | null;
  loading: boolean;
  error: string | null;
  refreshEnvelopes: (force?: boolean) => Promise<void>;
  fetchEnvelopeDetails: (envelopeId: string) => Promise<EnvelopeDetails | null>;
  createEnvelope: (name: string, targetBudget: string, currency: string) => Promise<void>;
  deleteEnvelope: (envelopeId: string, setError: (error: string | null) => void) => Promise<void>;
  creditEnvelope: (envelopeId: string, amount: string, description: string, setError: (error: string | null) => void) => Promise<void>;
  debitEnvelope: (envelopeId: string, amount: string, description: string, setError: (error: string | null) => void) => Promise<void>;
  updateEnvelopeName: (envelopeId: string, name: string, setError: (error: string | null) => void) => Promise<void>;
  updateTargetBudget: (envelopeId: string, targetedAmount: string, currentAmount: string, setError: (error: string | null) => void) => Promise<void>;
  listenToEnvelopeUpdates: (envelopeId: string, onUpdate: () => void, onDelete?: () => void) => () => void;
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

  const { socket, connected } = useSocket();
  const { user } = useAuth();
  const userRef = useRef(user);
  const socketRef = useRef(socket);
  const connectedRef = useRef(connected);
  const activeListenersRef = useRef<Record<string, { onUpdate: () => void, onDelete?: () => void }>>({});
  const lastUpdateTimeRef = useRef<Record<string, number>>({});

  // Update refs when values change
  useEffect(() => {
    userRef.current = user;
    socketRef.current = socket;
    connectedRef.current = connected;
  }, [user, socket, connected]);

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

  const refreshEnvelopes = useCallback(async (force = false) => {
    if (!force && (loading || envelopesData)) return;

    setLoading(true);
    setError(null);

    try {
      console.log('Refreshing envelopes...');
      const updatedEnvelopes = await envelopeService.listEnvelopes();
      console.log('Envelopes refreshed:', updatedEnvelopes);
      setEnvelopesData(updatedEnvelopes);
    } catch (err) {
      console.error("Refresh error:", err);
      setError("Failed to refresh envelopes");
    } finally {
      setLoading(false);
    }
  }, [loading, envelopesData]);

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

  useEffect(() => {
    if (user && connected) {
      refreshEnvelopes();
    }
  }, [refreshEnvelopes, user, connected]);

  // Clear pending requests when socket disconnects
  useEffect(() => {
    if (!connected && Object.keys(pendingRequests).length > 0) {
      console.log('Socket disconnected, clearing pending requests');
      setPendingRequests({});
    }
  }, [connected, pendingRequests]);

  // Centralized function to listen for updates to a specific envelope
  const listenToEnvelopeUpdates = useCallback((
    envelopeId: string,
    onUpdate: () => void,
    onDelete?: () => void
  ) => {
    if (!socket) {
      console.log('Socket not available for envelope update listener');
      return () => { };
    }

    console.log(`Setting up WebSocket event listeners for envelope: ${envelopeId}`);

    // Store callbacks in the ref to access them in event handlers
    activeListenersRef.current[envelopeId] = { onUpdate, onDelete };

    // Handler for envelope events
    const handleEnvelopeEvent = (event: any) => {
      // Check if this event is for the target envelope
      if (event.aggregateId === envelopeId || event.budgetEnvelopeId === envelopeId) {
        console.log(`Envelope event received for ${envelopeId}:`, event.type);

        // If it's a delete event and we have a delete callback, call it immediately
        if (event.type === 'BudgetEnvelopeDeleted' || event.__type === 'BudgetEnvelopeDeleted') {
          if (activeListenersRef.current[envelopeId]?.onDelete) {
            activeListenersRef.current[envelopeId].onDelete?.();
          }
          return;
        }

        // For all other events, we need to:
        // 1. Signal the UI immediately that an update is available (improve responsiveness)
        // 2. Then fetch the latest data to ensure consistency

        // First, notify the UI layer that an update is happening
        // This lets the UI show a spinner or some other indication
        if (activeListenersRef.current[envelopeId]) {
          console.log(`Triggering immediate UI notification for envelope ${envelopeId}`);
          activeListenersRef.current[envelopeId].onUpdate();
        }

        // Then fetch the latest data with a small delay to avoid race conditions
        // with multiple rapid socket events
        const now = Date.now();
        const lastUpdate = lastUpdateTimeRef.current[envelopeId] || 0;

        // If we've had a recent update, use a slightly longer delay for the data fetch
        const updateDelay = now - lastUpdate < 300 ? 500 : 200;
        lastUpdateTimeRef.current[envelopeId] = now;

        // Use setTimeout to avoid blocking the UI during the fetch
        setTimeout(() => {
          fetchEnvelopeDetails(envelopeId)
            .then(details => {
              if (details) {
                console.log(`Updated envelope details fetched for ${envelopeId}`);
                setCurrentEnvelopeDetails(details);

                // No need to call onUpdate again as we already notified the UI
                // The UI should be refreshing the data from context or props
              } else {
                console.warn(`No details returned for envelope ${envelopeId}`);
              }
            })
            .catch(err => {
              console.error(`Failed to fetch updated details for envelope ${envelopeId}:`, err);
            });
        }, updateDelay);
      }
    };

    // All envelope-related events
    const eventTypes = [
      'BudgetEnvelopeCredited',
      'BudgetEnvelopeDebited',
      'BudgetEnvelopeRenamed',
      'BudgetEnvelopeTargetedAmountChanged',
      'BudgetEnvelopeDeleted'
    ];

    // Register all event handlers
    for (const eventType of eventTypes) {
      socket.on(eventType, handleEnvelopeEvent);
    }

    // Return cleanup function
    return () => {
      console.log(`Cleaning up WebSocket event listeners for envelope: ${envelopeId}`);
      // Remove all event handlers
      for (const eventType of eventTypes) {
        socket.off(eventType, handleEnvelopeEvent);
      }

      // Remove from active listeners
      delete activeListenersRef.current[envelopeId];
      delete lastUpdateTimeRef.current[envelopeId];
    };
  }, [socket, fetchEnvelopeDetails]);

  // Handle socket events for real-time updates to the envelope list
  useEffect(() => {
    if (!socket) return;

    console.log('Setting up WebSocket event listeners for envelopes list');

    // Track last refresh time to prevent multiple refreshes in a short period
    let lastRefreshTime = 0;
    const REFRESH_DEBOUNCE_TIME = 500; // ms

    // Debounced refresh function that updates both list and details
    const debouncedRefresh = async (envelopeId?: string) => {
      const currentTime = Date.now();
      if (currentTime - lastRefreshTime > REFRESH_DEBOUNCE_TIME) {
        lastRefreshTime = currentTime;
        
        // Refresh envelopes list
        await refreshEnvelopes(true);
        
        // If we have a specific envelope ID and it matches current details, refresh it
        if (envelopeId && currentEnvelopeDetails?.envelope.uuid === envelopeId) {
          const updatedDetails = await fetchEnvelopeDetails(envelopeId);
          if (updatedDetails) {
            setCurrentEnvelopeDetails(updatedDetails);
          }
        }
      } else {
        console.log('Skipping refresh, too soon since last refresh');
      }
    };

    const eventHandlers: Record<string, (event: any) => void> = {
      'connected': (event) => {
        console.log('WebSocket connected event received in envelope context:', event);
        // When socket reconnects, refresh data and clear pending requests
        debouncedRefresh();
        
        // Clear any pending requests that might be stuck
        if (Object.keys(pendingRequests).length > 0) {
          console.log('Clearing pending envelope requests after reconnection');
          setPendingRequests({});
        }
      },
      'BudgetEnvelopeAdded': (event) => {
        console.log('Envelope added event received:', event);
        if (event.requestId && pendingRequests[event.requestId]) {
          setPendingRequests(prev => {
            const updated = { ...prev };
            delete updated[event.requestId];
            return updated;
          });
          return;
        }
        debouncedRefresh();
      },
      'BudgetEnvelopeCredited': (event) => {
        console.log('Envelope credited event received:', event);
        if (event.requestId && pendingRequests[event.requestId]) {
          setPendingRequests(prev => {
            const updated = { ...prev };
            delete updated[event.requestId];
            return updated;
          });
          return;
        }
        debouncedRefresh(event.aggregateId || event.budgetEnvelopeId);
      },
      'BudgetEnvelopeDebited': (event) => {
        console.log('Envelope debited event received:', event);
        if (event.requestId && pendingRequests[event.requestId]) {
          setPendingRequests(prev => {
            const updated = { ...prev };
            delete updated[event.requestId];
            return updated;
          });
          return;
        }
        debouncedRefresh(event.aggregateId || event.budgetEnvelopeId);
      },
      'BudgetEnvelopeDeleted': (event) => {
        console.log('Envelope deleted event received:', event);
        
        // Just clear any pending request
        if (event.requestId && pendingRequests[event.requestId]) {
          setPendingRequests(prev => {
            const updated = { ...prev };
            delete updated[event.requestId];
            return updated;
          });
        }
        
        // Let the server refresh be handled by normal refresh cycle
        // This is more reliable than optimistic updates
        debouncedRefresh();
        
        // For any components watching this specific envelope (detail view)
        // they'll get their onDelete callback called which handles navigation
        const deletedId = event.aggregateId || event.budgetEnvelopeId;
        if (deletedId && activeListenersRef.current[deletedId]?.onDelete) {
          console.log(`Calling onDelete callback for envelope ${deletedId}`);
          activeListenersRef.current[deletedId].onDelete();
        }
      },
      'BudgetEnvelopeRenamed': (event) => {
        console.log('Envelope renamed event received:', event);
        if (event.requestId && pendingRequests[event.requestId]) {
          setPendingRequests(prev => {
            const updated = { ...prev };
            delete updated[event.requestId];
            return updated;
          });
          return;
        }
        debouncedRefresh(event.aggregateId || event.budgetEnvelopeId);
      },
      'BudgetEnvelopeTargetedAmountChanged': (event) => {
        console.log('Envelope target amount changed event received:', event);
        if (event.requestId && pendingRequests[event.requestId]) {
          setPendingRequests(prev => {
            const updated = { ...prev };
            delete updated[event.requestId];
            return updated;
          });
          return;
        }
        debouncedRefresh(event.aggregateId || event.budgetEnvelopeId);
      }
    };

    // Register event handlers
    for (const [event, handler] of Object.entries(eventHandlers)) {
      socket.on(event, handler);
    }

    return () => {
      // Cleanup all event handlers
      for (const event of Object.keys(eventHandlers)) {
        socket.off(event);
      }
    };
  }, [socket, refreshEnvelopes, currentEnvelopeDetails, pendingRequests]);

  // CRUD operations - unchanged...
  const createEnvelope = async (name: string, targetBudget: string, currency: string) => {
    setLoading(true);
    const requestId = uuidv4();
    setPendingRequests(prev => ({
      ...prev,
      [requestId]: true
    }));

    console.log(`Creating envelope with request ID: ${requestId}`);

    const newEnvelope: Partial<Envelope> = {
      uuid: requestId,
      name,
      targetedAmount: targetBudget,
      currency,
      currentAmount: "0",
      updatedAt: new Date().toISOString(),
      createdAt: new Date().toISOString(),
      deleted: false,
      pending: true,
    };

    // Optimistic update
    setEnvelopesData(prev => {
      if (!prev) {
        return {
          envelopes: [newEnvelope as Envelope],
          totalItems: 1
        };
      }

      return {
        ...prev,
        envelopes: [...prev.envelopes, newEnvelope as Envelope],
        totalItems: prev.totalItems + 1
      };
    });

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
    } catch (err: any) {
      // Rollback on error
      setPendingRequests(prev => {
        const updated = { ...prev };
        delete updated[requestId];
        return updated;
      });

      setError(err.message || "Failed to create envelope");
      console.error(`Error creating envelope: ${err.message || "Failed to create envelope"}`);

      setEnvelopesData(prev => {
        if (!prev) return prev;

        return {
          ...prev,
          envelopes: prev.envelopes.filter(env => env.uuid !== requestId),
          totalItems: prev.totalItems - 1
        };
      });
    } finally {
      setLoading(false);
    }
  };

  const deleteEnvelope = async (envelopeId: string, setError: (error: string | null) => void) => {
    const requestId = uuidv4();

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
    const requestId = uuidv4();

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
    const requestId = uuidv4();

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
    const requestId = uuidv4();

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
    const requestId = uuidv4();

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
    listenToEnvelopeUpdates
  };

  return (
    <EnvelopeContext.Provider value={value}>
      {children}
    </EnvelopeContext.Provider>
  );
};