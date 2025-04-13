import { useState, useEffect } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, ActivityIndicator, RefreshControl } from 'react-native';
import { useRouter } from 'expo-router';
import { apiClient } from '../services/apiClient';
import { useSocket } from '../contexts/SocketContext';
import { StatusBar } from 'expo-status-bar';

type Envelope = {
  uuid: string;
  name: string;
  amount: number;
  currency: string;
  budgetPlanUuid: string;
  createdAt: string;
  updatedAt: string;
};

export default function EnvelopesScreen() {
  const [envelopes, setEnvelopes] = useState<Envelope[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const router = useRouter();
  const { socket, connected } = useSocket();
  
  // Fetch envelopes from API
  const fetchEnvelopes = async () => {
    try {
      setError(null);
      const data = await apiClient.get<Envelope[]>('/envelopes');
      setEnvelopes(data);
    } catch (err) {
      console.error('Error fetching envelopes:', err);
      setError('Failed to load envelopes. Please try again later.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };
  
  // Load envelopes on component mount
  useEffect(() => {
    fetchEnvelopes();
  }, []);
  
  // Listen for WebSocket updates
  useEffect(() => {
    if (!socket) return;
    
    // Listen for envelope created/updated/deleted events
    socket.on('envelope:created', (newEnvelope: Envelope) => {
      setEnvelopes(prev => [...prev, newEnvelope]);
    });
    
    socket.on('envelope:updated', (updatedEnvelope: Envelope) => {
      setEnvelopes(prev => 
        prev.map(env => env.uuid === updatedEnvelope.uuid ? updatedEnvelope : env)
      );
    });
    
    socket.on('envelope:deleted', (deletedUuid: string) => {
      setEnvelopes(prev => prev.filter(env => env.uuid !== deletedUuid));
    });
    
    return () => {
      socket.off('envelope:created');
      socket.off('envelope:updated');
      socket.off('envelope:deleted');
    };
  }, [socket]);
  
  // Handle pull-to-refresh
  const onRefresh = async () => {
    setRefreshing(true);
    await fetchEnvelopes();
  };
  
  // Format currency
  const formatAmount = (amount: number, currency: string) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency || 'USD',
    }).format(amount);
  };
  
  // Render an individual envelope item
  const renderEnvelope = ({ item }: { item: Envelope }) => (
    <TouchableOpacity
      style={styles.envelopeCard}
      onPress={() => router.push(`/envelope/${item.uuid}`)}
    >
      <Text style={styles.envelopeName}>{item.name}</Text>
      <Text style={styles.envelopeAmount}>{formatAmount(item.amount, item.currency)}</Text>
    </TouchableOpacity>
  );

  if (loading && !refreshing) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#4a6fa5" />
        <Text style={styles.loadingText}>Loading envelopes...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar style="auto" />
      
      <View style={styles.header}>
        <Text style={styles.title}>Budget Envelopes</Text>
        {connected ? (
          <View style={styles.connectedBadge}>
            <Text style={styles.connectedText}>Live</Text>
          </View>
        ) : null}
      </View>

      {error ? (
        <View style={styles.errorContainer}>
          <Text style={styles.errorText}>{error}</Text>
          <TouchableOpacity style={styles.retryButton} onPress={fetchEnvelopes}>
            <Text style={styles.retryButtonText}>Retry</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <>
          <FlatList
            data={envelopes}
            renderItem={renderEnvelope}
            keyExtractor={(item) => item.uuid}
            contentContainerStyle={styles.listContainer}
            refreshControl={
              <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
            }
            ListEmptyComponent={
              <View style={styles.emptyContainer}>
                <Text style={styles.emptyText}>No envelopes found</Text>
                <Text style={styles.emptySubText}>
                  Tap the + button to create your first envelope
                </Text>
              </View>
            }
          />

          <TouchableOpacity
            style={styles.fab}
            onPress={() => router.push('/create-envelope')}
          >
            <Text style={styles.fabText}>+</Text>
          </TouchableOpacity>
        </>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f7fa',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 10,
    fontSize: 16,
    color: '#4a6fa5',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 20,
    paddingTop: 60,
    backgroundColor: 'white',
    borderBottomWidth: 1,
    borderBottomColor: '#e0e0e0',
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#4a6fa5',
  },
  connectedBadge: {
    backgroundColor: '#4caf50',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 12,
  },
  connectedText: {
    color: 'white',
    fontSize: 12,
    fontWeight: 'bold',
  },
  listContainer: {
    padding: 16,
    paddingBottom: 80,
  },
  envelopeCard: {
    backgroundColor: 'white',
    padding: 20,
    borderRadius: 10,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
    elevation: 2,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  envelopeName: {
    fontSize: 18,
    fontWeight: '500',
    color: '#333',
  },
  envelopeAmount: {
    fontSize: 18,
    fontWeight: '600',
    color: '#4a6fa5',
  },
  emptyContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 20,
    paddingTop: 100,
  },
  emptyText: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#9e9e9e',
    marginBottom: 10,
  },
  emptySubText: {
    fontSize: 16,
    color: '#9e9e9e',
    textAlign: 'center',
  },
  errorContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 20,
  },
  errorText: {
    fontSize: 18,
    color: '#e53935',
    marginBottom: 20,
    textAlign: 'center',
  },
  retryButton: {
    backgroundColor: '#4a6fa5',
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 8,
  },
  retryButtonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: 'bold',
  },
  fab: {
    position: 'absolute',
    bottom: 30,
    right: 30,
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: '#4a6fa5',
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 5,
    elevation: 5,
  },
  fabText: {
    fontSize: 30,
    fontWeight: 'bold',
    color: 'white',
  },
});