import { apiClient } from './apiClient';
import uuid from 'react-native-uuid';

export interface Envelope {
  uuid: string;
  updatedAt: string;
  currentAmount: string;
  targetedAmount: string;
  currency: string;
  name: string;
  userUuid: string;
  createdAt: string;
  deleted: boolean;
  pending: boolean;
}

export interface EnvelopesData {
  envelopes: Envelope[];
  totalItems: number;
}

export interface EnvelopeDetails {
  envelope: {
    uuid: string;
    currentAmount: string;
    targetedAmount: string;
    name: string;
    currency: string;
    deleted: boolean;
    pending: boolean;
  };
  ledger: Array<{
    created_at: string;
    monetary_amount: string;
    entry_type: 'credit' | 'debit';
    description: string;
  }>;
}

export const envelopeService = {
  // Commands
  createEnvelope: (envelopeData: Partial<Envelope>, requestId: string) => 
    apiClient.post('/envelopes/add', envelopeData, { headers: { 'Request-Id': requestId } }),
    
  creditEnvelope: (envelopeId: string, amount: string, description: string, requestId: string) =>
    apiClient.post(`/envelopes/${envelopeId}/credit`, 
      { creditMoney: amount, description }, 
      { headers: { 'Request-Id': requestId } }
    ),
    
  debitEnvelope: (envelopeId: string, amount: string, description: string, requestId: string) =>
    apiClient.post(`/envelopes/${envelopeId}/debit`, 
      { debitMoney: amount, description }, 
      { headers: { 'Request-Id': requestId } }
    ),
    
  deleteEnvelope: (envelopeId: string, requestId: string) =>
    apiClient.delete(`/envelopes/${envelopeId}`, 
      { headers: { 'Request-Id': requestId } }
    ),
    
  nameEnvelope: (envelopeId: string, name: string, requestId: string) =>
    apiClient.post(`/envelopes/${envelopeId}/name`, 
      { name }, 
      { headers: { 'Request-Id': requestId } }
    ),
    
  updateTargetBudget: (envelopeId: string, targetedAmount: string, currentAmount: string, requestId: string) =>
    apiClient.post(`/envelopes/${envelopeId}/change-targeted-amount`, 
      { targetedAmount, currentAmount }, 
      { headers: { 'Request-Id': requestId } }
    ),

  // Queries
  listEnvelopes: (limit?: number) => {
    const url = limit ? `/envelopes?limit=${limit}` : '/envelopes';
    return apiClient.get<EnvelopesData>(url);
  },
  
  getEnvelopeDetails: (uuid: string) => apiClient.get<EnvelopeDetails>(`/envelopes/${uuid}`),
}