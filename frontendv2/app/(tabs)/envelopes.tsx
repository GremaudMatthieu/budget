import React, { useState, useCallback, useEffect } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  ActivityIndicator,
  Dimensions,
  TextInput,
  Platform
} from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useEnvelopes } from '@/contexts/EnvelopeContext';
import { useTranslation } from '@/utils/useTranslation';
import EnvelopeCard from '@/components/EnvelopeCard';
import CreateEnvelopeModal from '@/components/modals/CreateEnvelopeModal';
import DeleteConfirmationModal from '@/components/modals/DeleteConfirmationModal';
import DescriptionModal from '@/components/modals/DescriptionModal';
import formatAmount from '@/utils/formatAmount';
import validateAmount from '@/utils/validateAmount';
import { useErrorContext } from '@/contexts/ErrorContext';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';
import { useFocusEffect } from 'expo-router';
import { normalizeAmountInput } from '@/utils/normalizeAmountInput';
import { validateEnvelopeAmountField } from '@/utils/validateEnvelopeAmount';
import { formatCurrency } from '@/utils/currencyUtils';

// Utility functions for envelope totals and mixed currency detection
const getUniqueCurrencies = (envelopes: any[]): string[] => {
  const currencies = envelopes.map(envelope => envelope.currency);
  return [...new Set(currencies)];
};

const hasMixedCurrencies = (envelopes: any[]): boolean => {
  return getUniqueCurrencies(envelopes).length > 1;
};

const calculateTotalCurrentBudget = (envelopes: any[]): number => {
  return envelopes.reduce((total, envelope) => total + Number(envelope.currentAmount), 0);
};

const calculateTotalTargetBudget = (envelopes: any[]): number => {
  return envelopes.reduce((total, envelope) => total + Number(envelope.targetedAmount), 0);
};

const calculateOverallProgress = (envelopes: any[]): number => {
  const totalCurrent = calculateTotalCurrentBudget(envelopes);
  const totalTarget = calculateTotalTargetBudget(envelopes);
  return totalTarget > 0 ? (totalCurrent / totalTarget) * 100 : 0;
};

// Content component for envelopes, which will be wrapped with the animated header
function EnvelopesContent({ onCreateEnvelope }: { onCreateEnvelope: () => void }) {
  const { t } = useTranslation();
  const router = useRouter();
  const { envelopesData, loading, createEnvelope, deleteEnvelope, creditEnvelope, debitEnvelope, updateEnvelopeName, refreshEnvelopes } = useEnvelopes();
  const { setError } = useErrorContext();
  const [isCreating, setIsCreating] = useState(false);
  const screenWidth = Dimensions.get('window').width;
  const isTablet = screenWidth > 768;

  // State for envelope actions
  const [amounts, setAmounts] = useState<Record<string, string>>({});
  const [editingName, setEditingName] = useState<{ id: string, name: string } | null>(null);
  const [nameTouched, setNameTouched] = useState<{ [id: string]: boolean }>({});
  const [nameErrors, setNameErrors] = useState<{ [id: string]: string | null }>({});
  const [amountTouched, setAmountTouched] = useState<{ [id: string]: boolean }>({});
  const [amountErrors, setAmountErrors] = useState<{ [id: string]: string | null }>({});
  const [envelopeToDelete, setEnvelopeToDelete] = useState<{ id: string, name: string } | null>(null);
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [descriptionModalOpen, setDescriptionModalOpen] = useState(false);
  const [currentAction, setCurrentAction] = useState<{ type: 'credit' | 'debit', id: string, amount: string } | null>(null);
  const [amountFocused, setAmountFocused] = useState<{ [id: string]: boolean }>({});

  const handleCreateEnvelope = useCallback(async (name: string, targetAmount: string, currency: string) => {
    if (name && targetAmount) {
      const formattedTarget = formatAmount(targetAmount);
      await createEnvelope(name, formattedTarget, currency);
      await refreshEnvelopes();
      setIsCreating(false);
    }
  }, [createEnvelope, refreshEnvelopes]);

  const navigateToEnvelopeDetail = (uuid: string) => {
    // Navigate to the envelope detail screen using the dynamic route
    router.push(`/envelopes/${uuid}`);
  };

  const handleAmountChange = (id: string, value: string) => {
    // Allow ',' as decimal
    const normalized = value.replace(/,/g, '.');
    // Remove anything that's not a digit or decimal point
    const filtered = normalized.replace(/[^0-9.]/g, '');
    // Ensure only one decimal point
    const parts = filtered.split('.');
    let formatted = parts[0];
    if (parts.length > 1) {
      formatted += '.' + parts[1];
    }
    setAmounts(prev => ({
      ...prev,
      [id]: formatted
    }));
    setAmountTouched(prev => ({ ...prev, [id]: true }));
  };

  // Use shared validation
  const validateAmountField = (value: string) => validateEnvelopeAmountField(value, t);

  // Validation for name
  const validateNameField = (value: string) => {
    if (!value.trim()) return t('envelopes.nameCannotBeEmpty', { defaultValue: 'Envelope name cannot be empty' });
    if (value.trim().length < 1) return t('errors.nameTooShort', { defaultValue: 'Name must be at least 1 character' });
    if (value.trim().length > 50) return t('errors.nameTooLong', { defaultValue: 'Name must be at most 50 characters' });
    return null;
  };

  const handleNameChange = (value: string) => {
    if (editingName) {
      setEditingName({
        ...editingName,
        name: value
      });
      setNameTouched(prev => ({ ...prev, [editingName.id]: true }));
    }
  };

  // Re-validate on input change
  useEffect(() => {
    if (editingName) {
      setNameErrors(prev => ({ ...prev, [editingName.id]: validateNameField(editingName.name) }));
    }
  }, [editingName?.name]);
  useEffect(() => {
    Object.entries(amounts).forEach(([id, value]) => {
      setAmountErrors(prev => ({ ...prev, [id]: validateAmountField(value) }));
    });
  }, [amounts]);

  const startEditingName = (id: string, name: string) => {
    setEditingName({ id, name });
  };

  const cancelEditingName = () => {
    setEditingName(null);
  };

  const handleUpdateName = async () => {
    if (!editingName) return;
    const error = validateNameField(editingName.name);
    setNameErrors(prev => ({ ...prev, [editingName.id]: error }));
    setNameTouched(prev => ({ ...prev, [editingName.id]: true }));
    if (error) {
      if (error === t('envelopes.nameCannotBeEmpty', { defaultValue: 'Envelope name cannot be empty' })) {
        setError(error);
      }
      return;
    }
    try {
      await updateEnvelopeName(editingName.id, editingName.name, setError);
      setEditingName(null);
      await refreshEnvelopes(true);
    } catch (err) {
      console.error('Failed to update name:', err);
    }
  };

  const handleCreditEnvelope = (id: string, currentAmount: string, targetedAmount: string) => {
    const amount = amounts[id];
    if (!amount) return;
    const error = validateAmountField(amount);
    setAmountErrors(prev => ({ ...prev, [id]: error }));
    setAmountTouched(prev => ({ ...prev, [id]: true }));
    if (error) return;
    const current = parseFloat(currentAmount);
    const target = parseFloat(targetedAmount);
    const entered = parseFloat(amount);
    if (entered + current > target) {
      console.log('setError:', t('envelopes.cannotCreditMoreThanTarget'));
      setError(t('envelopes.cannotCreditMoreThanTarget'));
      return;
    }
    const formattedAmount = Number(formatAmount(amount)).toFixed(2);
    setCurrentAction({ type: 'credit', id, amount: formattedAmount });
    setDescriptionModalOpen(true);
  };

  const handleDebitEnvelope = (id: string, currentAmount: string) => {
    const amount = amounts[id];
    if (!amount) return;
    const error = validateAmountField(amount);
    setAmountErrors(prev => ({ ...prev, [id]: error }));
    setAmountTouched(prev => ({ ...prev, [id]: true }));
    if (error) return;
    const current = parseFloat(currentAmount);
    const entered = parseFloat(amount);
    if (entered > current) {
      console.log('setError:', t('envelopes.cannotDebitMoreThanBalance'));
      setError(t('envelopes.cannotDebitMoreThanBalance'));
      return;
    }
    const formattedAmount = Number(formatAmount(amount)).toFixed(2);
    setCurrentAction({ type: 'debit', id, amount: formattedAmount });
    setDescriptionModalOpen(true);
  };

  const handleDescriptionSubmit = async (description: string) => {
    if (!currentAction) return;
    try {
      if (currentAction.type === 'credit') {
        await creditEnvelope(
          currentAction.id,
          currentAction.amount,
          description,
          setError
        );
      } else {
        await debitEnvelope(
          currentAction.id,
          currentAction.amount,
          description,
          setError
        );
      }
      setAmounts(prev => ({
        ...prev,
        [currentAction.id]: ''
      }));
      await refreshEnvelopes(true);
    } catch (err) {
      console.error('Failed to process transaction:', err);
    } finally {
      setDescriptionModalOpen(false);
      setCurrentAction(null);
    }
  };

  const confirmDeleteEnvelope = (id: string, name: string) => {
    setEnvelopeToDelete({ id, name });
    setDeleteModalOpen(true);
  };

  const handleDeleteEnvelope = async () => {
    if (!envelopeToDelete) return;
    try {
      // Update the envelope to show pending state in UI
      const updatedEnvelopes = envelopesData?.envelopes?.map(env => 
        env.uuid === envelopeToDelete.id 
          ? { ...env, pending: true, deleted: true }
          : env
      ) || [];
      if (envelopesData && updatedEnvelopes) {
        const updatedData = {
          ...envelopesData,
          envelopes: updatedEnvelopes
        };
        // (no-op, just for UI feedback)
      }
      await deleteEnvelope(envelopeToDelete.id, setError);
      await refreshEnvelopes();
    } catch (err) {
      console.error('Failed to delete envelope:', err);
      if (envelopesData && envelopesData.envelopes) {
        const resetEnvelopes = envelopesData.envelopes.map(env => 
          env.uuid === envelopeToDelete?.id 
            ? { ...env, pending: false, deleted: false }
            : env
        );
        // (no-op, just for UI feedback)
      }
    } finally {
      setDeleteModalOpen(false);
      setEnvelopeToDelete(null);
    }
  };

  // Fallback UI for empty envelope list
  if (!loading && (!envelopesData || !envelopesData.envelopes || (envelopesData?.envelopes?.length ?? 0) === 0)) {
    return (
      <View className="flex-1 items-center justify-center px-6">
        <Text className="text-lg text-text-secondary mb-4 text-center">{t('envelopes.noEnvelopes')}</Text>
        <TouchableOpacity
          className="bg-primary-600 px-6 py-3 rounded-xl"
          onPress={onCreateEnvelope}
        >
          <Text className="text-white font-semibold text-base">{t('envelopes.createEnvelope')}</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View className="flex-1">
      <View className="flex-1">

        <View className="mt-4">
          <Text className="text-xl font-semibold text-secondary-800 mb-4">{t('envelopes.budgetCategories')}</Text>
          {/* Completed Envelopes */}
          {(envelopesData?.envelopes ?? []).filter(envelope => Number(envelope.currentAmount) / Number(envelope.targetedAmount) >= 1).length > 0 && (
              <View className="mb-6">
                <Text className="text-lg font-medium text-secondary-600 mb-2">{t('envelopes.completed')}</Text>
                <View className={isTablet ? "flex-row flex-wrap justify-between" : "space-y-4"}>
                  {(envelopesData?.envelopes ?? []).filter(envelope => Number(envelope.currentAmount) / Number(envelope.targetedAmount) >= 1).map((envelope) => (
                      <View
                        key={envelope.uuid}
                        className={isTablet ? "w-[49%] mb-4" : "w-full"}
                      >
                        <View className="card">
                          <View className="absolute top-0 right-0 left-0 h-1 bg-success-500 rounded-t-xl" />
                          <View className="card-content">
                            {/* Envelope header with name and edit/delete options */}
                            <View className="flex-row items-center justify-between mb-3">
                              <View className="flex-row items-center flex-1">
                                <View className="w-10 h-10 rounded-full bg-success-100 items-center justify-center mr-3">
                                  <Ionicons name="checkmark-circle" size={20} color="#16a34a" />
                                </View>

                                {editingName && editingName.id === envelope.uuid ? (
                                  <View className="flex-row items-center flex-1">
                                    <TextInput
                                      value={editingName.name}
                                      onChangeText={handleNameChange}
                                      className="w-40 p-2 border border-surface-border rounded-lg bg-white"
                                      maxLength={25}
                                      autoFocus
                                      onBlur={() => setNameTouched(prev => ({ ...prev, [envelope.uuid]: true }))}
                                      onFocus={() => setNameTouched(prev => ({ ...prev, [envelope.uuid]: true }))}
                                    />
                                    <TouchableOpacity
                                      onPress={handleUpdateName}
                                      className="ml-2 p-2 bg-success-100 rounded-full"
                                    >
                                      <Ionicons name="checkmark" size={18} color="#16a34a" />
                                    </TouchableOpacity>
                                    <TouchableOpacity
                                      onPress={cancelEditingName}
                                      className="ml-1 p-2 bg-danger-100 rounded-full"
                                    >
                                      <Ionicons name="close" size={18} color="#dc2626" />
                                    </TouchableOpacity>
                                  </View>
                                ) : (
                                  <View className="flex-row flex-1 items-center">
                                    <View className="flex-1">
                                      <Text className="text-lg font-semibold text-text-primary">{envelope.name}</Text>
                                    </View>

                                    <TouchableOpacity
                                      onPress={() => navigateToEnvelopeDetail(envelope.uuid)}
                                      className="p-2 mr-1 bg-primary-50 rounded-full"
                                      style={{ opacity: 0.9 }}
                                    >
                                      <Ionicons name="document-text-outline" size={18} color="#0284c7" />
                                    </TouchableOpacity>

                                    <TouchableOpacity
                                      onPress={() => startEditingName(envelope.uuid, envelope.name)}
                                      className="p-2 mr-1"
                                    >
                                      <Ionicons name="create-outline" size={18} color="#64748b" />
                                    </TouchableOpacity>

                                    <TouchableOpacity
                                      onPress={() => confirmDeleteEnvelope(envelope.uuid, envelope.name)}
                                      className="p-2"
                                    >
                                      <Ionicons name="trash-outline" size={18} color="#dc2626" />
                                    </TouchableOpacity>
                                  </View>
                                )}
                              </View>
                            </View>

                            <EnvelopeCard envelope={envelope} />

                            {/* Credit/Debit Controls */}
                            <View className="mt-3">
                              <View className="flex-1">
                                {amountTouched[envelope.uuid] && amountFocused[envelope.uuid] && amountErrors[envelope.uuid] && (
                                  <Text className="text-red-500 text-xs mb-1 ml-1">{amountErrors[envelope.uuid]}</Text>
                                )}
                                <View className="flex-row items-center space-x-2">
                                  <TextInput
                                    value={amounts[envelope.uuid] || ''}
                                    onChangeText={text => handleAmountChange(envelope.uuid, normalizeAmountInput(text))}
                                    placeholder={t('envelopes.enterAmount')}
                                    keyboardType="decimal-pad"
                                    className="flex-1 min-w-0 p-2 border border-surface-border rounded-lg bg-white"
                                    maxLength={13}
                                    onBlur={() => {
                                      setAmountTouched(prev => ({ ...prev, [envelope.uuid]: true }));
                                      setAmountFocused(prev => ({ ...prev, [envelope.uuid]: false }));
                                    }}
                                    onFocus={() => {
                                      setAmountTouched(prev => ({ ...prev, [envelope.uuid]: true }));
                                      setAmountFocused(prev => ({ ...prev, [envelope.uuid]: true }));
                                    }}
                                  />
                                  <TouchableOpacity
                                    onPress={() => handleCreditEnvelope(
                                      envelope.uuid,
                                      envelope.currentAmount,
                                      envelope.targetedAmount
                                    )}
                                    className="px-3 py-2 bg-success-100 rounded-lg shrink-0"
                                    disabled={!amounts[envelope.uuid] || envelope.pending || !!amountErrors[envelope.uuid]}
                                  >
                                    <Text className="text-success-700 font-medium text-sm">{t('envelopes.addFunds')}</Text>
                                  </TouchableOpacity>
                                  <TouchableOpacity
                                    onPress={() => handleDebitEnvelope(
                                      envelope.uuid,
                                      envelope.currentAmount
                                    )}
                                    className="px-3 py-2 bg-danger-100 rounded-lg shrink-0"
                                    disabled={!amounts[envelope.uuid] || envelope.pending || !!amountErrors[envelope.uuid]}
                                  >
                                    <Text className="text-danger-700 font-medium text-sm">{t('envelopes.withdraw')}</Text>
                                  </TouchableOpacity>
                                </View>
                              </View>
                            </View>

                            {envelope.pending && (
                              <View className="mt-2 items-center">
                                <ActivityIndicator size="small" color="#0c6cf2" />
                                <Text className="text-secondary-500 text-xs mt-1">{t('common.loading')}</Text>
                              </View>
                            )}
                          </View>
                        </View>
                      </View>
                    ))}
                </View>
              </View>
            )}

          {/* In Progress Envelopes */}
          {(envelopesData?.envelopes ?? []).filter(envelope => {
            const progress = Number(envelope.currentAmount) / Number(envelope.targetedAmount);
            return progress > 0 && progress < 1;
          }).length > 0 && (
              <View className="mb-6">
                <Text className="text-lg font-medium text-secondary-600 mb-2">{t('envelopes.inProgress')}</Text>
                <View className={isTablet ? "flex-row flex-wrap justify-between" : "space-y-4"}>
                  {(envelopesData?.envelopes ?? []).filter(envelope => {
                    const progress = Number(envelope.currentAmount) / Number(envelope.targetedAmount);
                    return progress > 0 && progress < 1;
                  }).map((envelope) => (
                      <View
                        key={envelope.uuid}
                        className={isTablet ? "w-[49%] mb-4" : "w-full"}
                      >
                        {/* ...envelope card content... */}
                        <View className="card">
                          <View className="absolute top-0 right-0 left-0 h-1 bg-primary-500 rounded-t-xl" />
                          <View className="card-content">
                            {/* Envelope header with name and edit/delete options */}
                            <View className="flex-row items-center justify-between mb-3">
                              <View className="flex-row items-center flex-1">
                                <View className="w-10 h-10 rounded-full bg-primary-100 items-center justify-center mr-3">
                                  <Ionicons name="trending-up" size={20} color="#0284c7" />
                                </View>

                                {editingName && editingName.id === envelope.uuid ? (
                                  <View className="flex-row items-center flex-1">
                                    <TextInput
                                      value={editingName.name}
                                      onChangeText={handleNameChange}
                                      className="w-40 p-2 border border-surface-border rounded-lg bg-white"
                                      maxLength={25}
                                      autoFocus
                                      onBlur={() => setNameTouched(prev => ({ ...prev, [envelope.uuid]: true }))}
                                      onFocus={() => setNameTouched(prev => ({ ...prev, [envelope.uuid]: true }))}
                                    />
                                    <TouchableOpacity
                                      onPress={handleUpdateName}
                                      className="ml-2 p-2 bg-success-100 rounded-full"
                                    >
                                      <Ionicons name="checkmark" size={18} color="#16a34a" />
                                    </TouchableOpacity>
                                    <TouchableOpacity
                                      onPress={cancelEditingName}
                                      className="ml-1 p-2 bg-danger-100 rounded-full"
                                    >
                                      <Ionicons name="close" size={18} color="#dc2626" />
                                    </TouchableOpacity>
                                  </View>
                                ) : (
                                  <View className="flex-row flex-1 items-center">
                                    <View className="flex-1">
                                      <Text className="text-lg font-semibold text-text-primary">{envelope.name}</Text>
                                    </View>

                                    <TouchableOpacity
                                      onPress={() => navigateToEnvelopeDetail(envelope.uuid)}
                                      className="p-2 mr-1 bg-primary-50 rounded-full"
                                      style={{ opacity: 0.9 }}
                                    >
                                      <Ionicons name="document-text-outline" size={18} color="#0284c7" />
                                    </TouchableOpacity>

                                    <TouchableOpacity
                                      onPress={() => startEditingName(envelope.uuid, envelope.name)}
                                      className="p-2 mr-1"
                                    >
                                      <Ionicons name="create-outline" size={18} color="#64748b" />
                                    </TouchableOpacity>

                                    <TouchableOpacity
                                      onPress={() => confirmDeleteEnvelope(envelope.uuid, envelope.name)}
                                      className="p-2"
                                    >
                                      <Ionicons name="trash-outline" size={18} color="#dc2626" />
                                    </TouchableOpacity>
                                  </View>
                                )}
                              </View>
                            </View>

                            <EnvelopeCard envelope={envelope} />

                            {/* Credit/Debit Controls */}
                            <View className="mt-3">
                              <View className="flex-1">
                                {amountTouched[envelope.uuid] && amountFocused[envelope.uuid] && amountErrors[envelope.uuid] && (
                                  <Text className="text-red-500 text-xs mb-1 ml-1">{amountErrors[envelope.uuid]}</Text>
                                )}
                                <View className="flex-row items-center space-x-2">
                                  <TextInput
                                    value={amounts[envelope.uuid] || ''}
                                    onChangeText={text => handleAmountChange(envelope.uuid, normalizeAmountInput(text))}
                                    placeholder={t('envelopes.enterAmount')}
                                    keyboardType="decimal-pad"
                                    className="flex-1 min-w-0 p-2 border border-surface-border rounded-lg bg-white"
                                    maxLength={13}
                                    onBlur={() => {
                                      setAmountTouched(prev => ({ ...prev, [envelope.uuid]: true }));
                                      setAmountFocused(prev => ({ ...prev, [envelope.uuid]: false }));
                                    }}
                                    onFocus={() => {
                                      setAmountTouched(prev => ({ ...prev, [envelope.uuid]: true }));
                                      setAmountFocused(prev => ({ ...prev, [envelope.uuid]: true }));
                                    }}
                                  />
                                  <TouchableOpacity
                                    onPress={() => handleCreditEnvelope(
                                      envelope.uuid,
                                      envelope.currentAmount,
                                      envelope.targetedAmount
                                    )}
                                    className="px-3 py-2 bg-success-100 rounded-lg shrink-0"
                                    disabled={!amounts[envelope.uuid] || envelope.pending || !!amountErrors[envelope.uuid]}
                                  >
                                    <Text className="text-success-700 font-medium text-sm">{t('envelopes.addFunds')}</Text>
                                  </TouchableOpacity>
                                  <TouchableOpacity
                                    onPress={() => handleDebitEnvelope(
                                      envelope.uuid,
                                      envelope.currentAmount
                                    )}
                                    className="px-3 py-2 bg-danger-100 rounded-lg shrink-0"
                                    disabled={!amounts[envelope.uuid] || envelope.pending || !!amountErrors[envelope.uuid]}
                                  >
                                    <Text className="text-danger-700 font-medium text-sm">{t('envelopes.withdraw')}</Text>
                                  </TouchableOpacity>
                                </View>
                              </View>
                            </View>

                            {envelope.pending && (
                              <View className="mt-2 items-center">
                                <ActivityIndicator size="small" color="#0c6cf2" />
                                <Text className="text-secondary-500 text-xs mt-1">{t('common.loading')}</Text>
                              </View>
                            )}
                          </View>
                        </View>
                      </View>
                    ))}
                </View>
              </View>
            )}

          {/* Not Started Envelopes */}
          {(envelopesData?.envelopes ?? []).filter(envelope => Number(envelope.currentAmount) === 0).length > 0 && (
              <View className="mb-6">
                <Text className="text-lg font-medium text-secondary-600 mb-2">{t('envelopes.notStarted')}</Text>
                <View className={isTablet ? "flex-row flex-wrap justify-between" : "space-y-4"}>
                  {(envelopesData?.envelopes ?? []).filter(envelope => Number(envelope.currentAmount) === 0).map((envelope) => (
                      <View
                        key={envelope.uuid}
                        className={isTablet ? "w-[49%] mb-4" : "w-full"}
                      >
                        <View className="card">
                          <View className="absolute top-0 right-0 left-0 h-1 bg-secondary-500 rounded-t-xl" />
                          <View className="card-content">
                            {/* Envelope header with name and edit/delete options */}
                            <View className="flex-row items-center justify-between mb-3">
                              <View className="flex-row items-center flex-1">
                                <View className="w-10 h-10 rounded-full bg-secondary-100 items-center justify-center mr-3">
                                  <Ionicons name="hourglass-outline" size={20} color="#64748b" />
                                </View>

                                {editingName && editingName.id === envelope.uuid ? (
                                  <View className="flex-row items-center flex-1">
                                    <TextInput
                                      value={editingName.name}
                                      onChangeText={handleNameChange}
                                      className="w-40 p-2 border border-surface-border rounded-lg bg-white"
                                      maxLength={25}
                                      autoFocus
                                      onBlur={() => setNameTouched(prev => ({ ...prev, [envelope.uuid]: true }))}
                                      onFocus={() => setNameTouched(prev => ({ ...prev, [envelope.uuid]: true }))}
                                    />
                                    <TouchableOpacity
                                      onPress={handleUpdateName}
                                      className="ml-2 p-2 bg-success-100 rounded-full"
                                    >
                                      <Ionicons name="checkmark" size={18} color="#16a34a" />
                                    </TouchableOpacity>
                                    <TouchableOpacity
                                      onPress={cancelEditingName}
                                      className="ml-1 p-2 bg-danger-100 rounded-full"
                                    >
                                      <Ionicons name="close" size={18} color="#dc2626" />
                                    </TouchableOpacity>
                                  </View>
                                ) : (
                                  <View className="flex-row flex-1 items-center">
                                    <View className="flex-1">
                                      <Text className="text-lg font-semibold text-text-primary">{envelope.name}</Text>
                                    </View>

                                    <TouchableOpacity
                                      onPress={() => navigateToEnvelopeDetail(envelope.uuid)}
                                      className="p-2 mr-1 bg-primary-50 rounded-full"
                                      style={{ opacity: 0.9 }}
                                    >
                                      <Ionicons name="document-text-outline" size={18} color="#0284c7" />
                                    </TouchableOpacity>

                                    <TouchableOpacity
                                      onPress={() => startEditingName(envelope.uuid, envelope.name)}
                                      className="p-2 mr-1"
                                    >
                                      <Ionicons name="create-outline" size={18} color="#64748b" />
                                    </TouchableOpacity>

                                    <TouchableOpacity
                                      onPress={() => confirmDeleteEnvelope(envelope.uuid, envelope.name)}
                                      className="p-2"
                                    >
                                      <Ionicons name="trash-outline" size={18} color="#dc2626" />
                                    </TouchableOpacity>
                                  </View>
                                )}
                              </View>
                            </View>
                            <EnvelopeCard envelope={envelope} />

                            {/* Credit/Debit Controls */}
                            <View className="mt-3">
                              <View className="flex-1">
                                {amountTouched[envelope.uuid] && amountFocused[envelope.uuid] && amountErrors[envelope.uuid] && (
                                  <Text className="text-red-500 text-xs mb-1 ml-1">{amountErrors[envelope.uuid]}</Text>
                                )}
                                <View className="flex-row items-center space-x-2">
                                  <TextInput
                                    value={amounts[envelope.uuid] || ''}
                                    onChangeText={text => handleAmountChange(envelope.uuid, normalizeAmountInput(text))}
                                    placeholder={t('envelopes.enterAmount')}
                                    keyboardType="decimal-pad"
                                    className="flex-1 min-w-0 p-2 border border-surface-border rounded-lg bg-white"
                                    maxLength={13}
                                    onBlur={() => {
                                      setAmountTouched(prev => ({ ...prev, [envelope.uuid]: true }));
                                      setAmountFocused(prev => ({ ...prev, [envelope.uuid]: false }));
                                    }}
                                    onFocus={() => {
                                      setAmountTouched(prev => ({ ...prev, [envelope.uuid]: true }));
                                      setAmountFocused(prev => ({ ...prev, [envelope.uuid]: true }));
                                    }}
                                  />
                                  <TouchableOpacity
                                    onPress={() => handleCreditEnvelope(
                                      envelope.uuid,
                                      envelope.currentAmount,
                                      envelope.targetedAmount
                                    )}
                                    className="px-3 py-2 bg-success-100 rounded-lg shrink-0"
                                    disabled={!amounts[envelope.uuid] || envelope.pending || !!amountErrors[envelope.uuid]}
                                  >
                                    <Text className="text-success-700 font-medium text-sm">{t('envelopes.addFunds')}</Text>
                                  </TouchableOpacity>
                                  <TouchableOpacity
                                    onPress={() => handleDebitEnvelope(
                                      envelope.uuid,
                                      envelope.currentAmount
                                    )}
                                    className="px-3 py-2 bg-danger-100 rounded-lg shrink-0"
                                    disabled={!amounts[envelope.uuid] || envelope.pending || !!amountErrors[envelope.uuid]}
                                  >
                                    <Text className="text-danger-700 font-medium text-sm">{t('envelopes.withdraw')}</Text>
                                  </TouchableOpacity>
                                </View>
                              </View>
                            </View>

                            {envelope.pending && (
                              <View className="mt-2 items-center">
                                <ActivityIndicator size="small" color="#0c6cf2" />
                                <Text className="text-secondary-500 text-xs mt-1">{t('common.loading')}</Text>
                              </View>
                            )}
                          </View>
                        </View>
                      </View>
                    ))}
                </View>
              </View>
            )}

          {/* Enhanced Budget Summary Section with Totals and Mixed Currency Warning */}
          {(envelopesData?.envelopes ?? []).length > 0 && (() => {
            const envelopes = envelopesData.envelopes;
            const mixedCurrencies = hasMixedCurrencies(envelopes);
            const uniqueCurrencies = getUniqueCurrencies(envelopes);
            const totalCurrent = calculateTotalCurrentBudget(envelopes);
            const totalTarget = calculateTotalTargetBudget(envelopes);
            const overallProgress = calculateOverallProgress(envelopes);
            const completedCount = envelopes.filter(e => Number(e.currentAmount) / Number(e.targetedAmount) >= 1).length;
            
            // Use the most common currency for display, or first currency if all equal
            const primaryCurrency = uniqueCurrencies.reduce((a, b) => 
              envelopes.filter(e => e.currency === a).length >= envelopes.filter(e => e.currency === b).length ? a : b
            );

            return (
              <View className="bg-secondary-900 rounded-xl p-6 mb-8">
                {/* Mixed Currency Warning */}
                {mixedCurrencies && (
                  <View className="bg-orange-100 border border-orange-300 rounded-lg p-4 mb-4">
                    <View className="flex-row items-center mb-2">
                      <Ionicons name="warning" size={18} color="#ea580c" style={{ marginRight: 8 }} />
                      <Text className="text-orange-800 font-semibold text-sm">{t('envelopes.mixedCurrenciesWarning')}</Text>
                    </View>
                    <Text className="text-orange-700 text-xs mb-2">
                      {t('envelopes.mixedCurrenciesDescription')}
                    </Text>
                    <Text className="text-orange-600 text-xs font-medium">
                      {t('envelopes.currenciesUsed')}: {uniqueCurrencies.join(', ')}
                    </Text>
                  </View>
                )}

                {/* Total Summary */}
                <View className="bg-secondary-800 rounded-lg p-4 mb-4">
                  <Text className="text-xl font-bold text-white mb-2">{t('envelopes.totalSummary')}</Text>
                  
                  <View className="flex-row justify-between items-center mb-2">
                    <Text className="text-secondary-300 text-sm">{t('envelopes.totalCurrentBudget')}</Text>
                    <Text className="text-white font-semibold">
                      {mixedCurrencies ? 
                        `${formatCurrency(totalCurrent, primaryCurrency)} *` : 
                        formatCurrency(totalCurrent, primaryCurrency)
                      }
                    </Text>
                  </View>
                  
                  <View className="flex-row justify-between items-center mb-2">
                    <Text className="text-secondary-300 text-sm">{t('envelopes.totalTargetBudget')}</Text>
                    <Text className="text-white font-semibold">
                      {mixedCurrencies ? 
                        `${formatCurrency(totalTarget, primaryCurrency)} *` : 
                        formatCurrency(totalTarget, primaryCurrency)
                      }
                    </Text>
                  </View>
                  
                  <View className="flex-row justify-between items-center mb-3">
                    <Text className="text-secondary-300 text-sm">{t('envelopes.overallProgress')}</Text>
                    <Text className="text-white font-semibold">{overallProgress.toFixed(1)}%</Text>
                  </View>

                  {mixedCurrencies && (
                    <Text className="text-orange-300 text-xs italic">
                      * {t('envelopes.mixedCurrenciesInaccurate')}
                    </Text>
                  )}
                </View>

                {/* Budget Overview */}
                <View className="bg-secondary-800 rounded-lg p-4 mb-4">
                  <Text className="text-xl font-bold text-white min-w-0 break-all" numberOfLines={2} ellipsizeMode="tail">{t('envelopes.budgetOverview')}</Text>
                  <Text className="text-secondary-300 min-w-0 break-all">
                    {completedCount} {t('envelopes.completedOf')} {envelopes.length} {t('envelopes.title').toLowerCase()}
                  </Text>
                </View>

                {/* Progress Bar */}
                <View className="flex-row items-center min-w-0">
                  <View className="h-1 flex-1 bg-success-500 rounded-full min-w-0" />
                  <View className="h-1 flex-1 bg-primary-500 rounded-full mx-1 min-w-0" />
                  <View className="h-1 flex-1 bg-secondary-500 rounded-full min-w-0" />
                </View>
              </View>
            );
          })()}

        </View>

        {/* Modals */}
        <CreateEnvelopeModal
          visible={isCreating}
          onClose={() => setIsCreating(false)}
          onSubmit={handleCreateEnvelope}
        />

        <DeleteConfirmationModal
          visible={deleteModalOpen}
          onClose={() => setDeleteModalOpen(false)}
          onConfirm={handleDeleteEnvelope}
          name={envelopeToDelete?.name || ''}
        />

        <DescriptionModal
          visible={descriptionModalOpen}
          onClose={() => setDescriptionModalOpen(false)}
          onSubmit={handleDescriptionSubmit}
          actionType={currentAction?.type || 'credit'}
        />
      </View>
      {Platform.OS === 'web' && <div className="h-32" />}
    </View>
  );
}

// Create a header action button component for the add button
function AddEnvelopeButton({ onPress }: { onPress: () => void }) {
  return (
    <TouchableOpacity
      onPress={onPress}
      className="bg-white/20 p-3 rounded-full"
    >
      <Ionicons name="add" size={24} color="white" />
    </TouchableOpacity>
  );
}

// Wrap the screen with the animated header HOC for refresh capability
function EnvelopesScreen() {
  const { t } = useTranslation();
  const { refreshEnvelopes, envelopesData, createEnvelope } = useEnvelopes();
  const [refreshing, setRefreshing] = useState(false);
  const [isCreating, setIsCreating] = useState(false);
  const [creating, setCreating] = useState(false);

  const handleRefresh = useCallback(async () => {
    setRefreshing(true);
    await refreshEnvelopes(true);
    setRefreshing(false);
  }, [refreshEnvelopes]);

  const handleCreateEnvelope = useCallback(async (name: string, targetAmount: string, currency: string) => {
    if (name && targetAmount) {
      setCreating(true);
      await createEnvelope(name, targetAmount, currency);
      setCreating(false);
      setIsCreating(false);
    }
  }, [createEnvelope]);

  useFocusEffect(
    useCallback(() => {
      refreshEnvelopes(true);
    }, [refreshEnvelopes])
  );

  if (Platform.OS === 'web') {
    return (
      <div className="mb-8 mt-2">
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
          <div>
            <h1 className="text-3xl font-bold text-slate-900 mb-1">{t('envelopes.title')}</h1>
            <p className="text-slate-500">{t('envelopes.subtitle')}</p>
          </div>
          {/* Place any important actions/info from the blue header here if needed */}
        </div>
        <EnvelopesContent onCreateEnvelope={() => setIsCreating(true)} />
        {/* Floating Action Button for web (optional) */}
        <div className="fixed bottom-24 right-8 z-50">
          <button
            onClick={() => setIsCreating(true)}
            className="bg-primary-600 w-14 h-14 rounded-full shadow-lg flex items-center justify-center text-white text-2xl hover:bg-primary-700 transition"
            style={{ boxShadow: '0 4px 16px rgba(44, 120, 220, 0.15)' }}
          >
            <Ionicons name="add" size={28} color="white" />
          </button>
        </div>
        <CreateEnvelopeModal
          visible={isCreating}
          onClose={() => { if (!creating) setIsCreating(false); }}
          onSubmit={handleCreateEnvelope}
        />
      </div>
    );
  }

  return (
    <View className="flex-1">
      <AnimatedHeaderLayout
        title={t('envelopes.title')}
        subtitle={t('envelopes.subtitle')}
        headerHeight={130}
      >
        <EnvelopesContent onCreateEnvelope={() => setIsCreating(true)} />
      </AnimatedHeaderLayout>

      {/* Floating Action Button */}
      <View className="absolute bottom-6 right-6 z-50">
        <TouchableOpacity
          onPress={() => setIsCreating(true)}
          className="bg-primary-600 w-14 h-14 rounded-full shadow-lg items-center justify-center"
          style={{ elevation: 8 }}
        >
          <Ionicons name="add" size={28} color="white" />
        </TouchableOpacity>
      </View>

      {/* Create Envelope Modal */}
      <CreateEnvelopeModal
        visible={isCreating}
        onClose={() => { if (!creating) setIsCreating(false); }}
        onSubmit={handleCreateEnvelope}
      />
    </View>
  );
}

export default EnvelopesScreen;