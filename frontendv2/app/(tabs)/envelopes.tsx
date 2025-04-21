import React, { useState, useCallback } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  ActivityIndicator,
  Dimensions,
  TextInput
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

// Content component for envelopes, which will be wrapped with the animated header
function EnvelopesContent() {
  const { t } = useTranslation();
  const router = useRouter();
  const { envelopesData, loading, createEnvelope, deleteEnvelope, creditEnvelope, debitEnvelope, updateEnvelopeName } = useEnvelopes();
  const { setError } = useErrorContext();
  const [isCreating, setIsCreating] = useState(false);
  const screenWidth = Dimensions.get('window').width;
  const isTablet = screenWidth > 768;

  // State for envelope actions
  const [amounts, setAmounts] = useState<Record<string, string>>({});
  const [editingName, setEditingName] = useState<{ id: string, name: string } | null>(null);
  const [envelopeToDelete, setEnvelopeToDelete] = useState<{ id: string, name: string } | null>(null);
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [descriptionModalOpen, setDescriptionModalOpen] = useState(false);
  const [currentAction, setCurrentAction] = useState<{ type: 'credit' | 'debit', id: string, amount: string } | null>(null);

  const handleCreateEnvelope = useCallback(async (name: string, targetAmount: string, currency: string) => {
    if (name && targetAmount) {
      const formattedTarget = formatAmount(targetAmount);
      await createEnvelope(name, formattedTarget, currency);
      setIsCreating(false);
    }
  }, [createEnvelope]);

  const navigateToEnvelopeDetail = (uuid: string) => {
    // Navigate to the envelope detail screen using the dynamic route
    router.push(`/envelopes/${uuid}`);
  };

  const handleAmountChange = (id: string, value: string) => {
    // Remove anything that's not a digit or decimal point
    const filtered = value.replace(/[^0-9.]/g, '');

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
  };

  const handleNameChange = (value: string) => {
    if (editingName) {
      setEditingName({
        ...editingName,
        name: value
      });
    }
  };

  const startEditingName = (id: string, name: string) => {
    setEditingName({ id, name });
  };

  const cancelEditingName = () => {
    setEditingName(null);
  };

  const handleUpdateName = async () => {
    if (!editingName) return;

    try {
      await updateEnvelopeName(editingName.id, editingName.name, setError);
      setEditingName(null);
    } catch (err) {
      console.error('Failed to update name:', err);
    }
  };

  const handleCreditEnvelope = (id: string, currentAmount: string, targetedAmount: string) => {
    const amount = amounts[id];
    if (!amount) return;

    const formattedAmount = formatAmount(amount);

    if (validateAmount(formattedAmount, currentAmount, targetedAmount, true)) {
      setCurrentAction({ type: 'credit', id, amount: formattedAmount });
      setDescriptionModalOpen(true);
    } else {
      const maxCredit = (
        Number(targetedAmount) - Number(currentAmount)
      ).toFixed(2);
      setError(`Cannot credit ${formattedAmount}. Maximum is ${maxCredit}`);
    }
  };

  const handleDebitEnvelope = (id: string, currentAmount: string) => {
    const amount = amounts[id];
    if (!amount) return;

    const formattedAmount = formatAmount(amount);

    if (validateAmount(formattedAmount, currentAmount, "0", false)) {
      setCurrentAction({ type: 'debit', id, amount: formattedAmount });
      setDescriptionModalOpen(true);
    } else {
      setError(`Cannot debit ${formattedAmount}. Maximum is ${currentAmount}`);
    }
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
      // Clear the amount input after successful transaction
      setAmounts(prev => ({
        ...prev,
        [currentAction.id]: ''
      }));
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
      const updatedEnvelopes = envelopesData?.envelopes.map(env => 
        env.uuid === envelopeToDelete.id 
          ? { ...env, pending: true, deleted: true }
          : env
      );
      
      if (envelopesData && updatedEnvelopes) {
        // Update UI state to show loading
        const updatedData = {
          ...envelopesData,
          envelopes: updatedEnvelopes
        };
        
        // Update envelopes data with pending state
        envelopesData.envelopes = updatedEnvelopes;
      }
      
      await deleteEnvelope(envelopeToDelete.id, setError);
      // WebSocket event will handle the actual deletion from the list
    } catch (err) {
      console.error('Failed to delete envelope:', err);
      
      // Reset pending state if there was an error
      if (envelopesData) {
        const resetEnvelopes = envelopesData.envelopes.map(env => 
          env.uuid === envelopeToDelete?.id 
            ? { ...env, pending: false, deleted: false }
            : env
        );
        
        // Update UI with reset state
        envelopesData.envelopes = resetEnvelopes;
      }
    } finally {
      setDeleteModalOpen(false);
      setEnvelopeToDelete(null);
    }
  };

  // If no envelopes yet, display empty state
  if (!loading && (!envelopesData?.envelopes || envelopesData.envelopes.length === 0)) {
    return (
      <View className="card mt-4">
        <View className="card-content items-center justify-center py-6">
          <View className="w-16 h-16 bg-primary-100 rounded-full items-center justify-center mb-4">
            <Ionicons name="wallet-outline" size={32} color="#0c6cf2" />
          </View>
          <Text className="text-xl font-semibold text-text-primary text-center mb-2">
            {t('envelopes.noTransactions')}
          </Text>
          <Text className="text-text-secondary text-center mb-6">
            {t('envelopes.createEnvelopePrompt')}
          </Text>
          <TouchableOpacity
            onPress={() => setIsCreating(true)}
            className="bg-primary-600 rounded-xl py-3 px-6 items-center shadow-sm"
          >
            <Text className="text-white font-semibold">{t('envelopes.createEnvelope')}</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  return (
    <View className="flex-1">
      <View className="flex-1">

        <View className="mt-4">
          <Text className="text-xl font-semibold text-secondary-800 mb-4">{t('envelopes.budgetCategories')}</Text>
          {/* Completed Envelopes */}
          {envelopesData?.envelopes
            .filter(envelope => Number(envelope.currentAmount) / Number(envelope.targetedAmount) >= 1)
            .length > 0 && (
              <View className="mb-6">
                <Text className="text-lg font-medium text-secondary-600 mb-2">{t('envelopes.completed')}</Text>
                <View className={isTablet ? "flex-row flex-wrap justify-between" : "space-y-4"}>
                  {envelopesData.envelopes
                    .filter(envelope => Number(envelope.currentAmount) / Number(envelope.targetedAmount) >= 1)
                    .map((envelope) => (
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
                                      className="flex-1 p-2 border border-surface-border rounded-lg bg-white"
                                      maxLength={25}
                                      autoFocus
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
                                    <TouchableOpacity
                                      onPress={() => navigateToEnvelopeDetail(envelope.uuid)}
                                      className="flex-1"
                                    >
                                      <Text className="text-lg font-semibold text-text-primary">{envelope.name}</Text>
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
                              <View className="flex-row items-center space-x-2">
                                <TextInput
                                  value={amounts[envelope.uuid] || ''}
                                  onChangeText={(text) => handleAmountChange(envelope.uuid, text)}
                                  placeholder={t('envelopes.enterAmount')}
                                  keyboardType="decimal-pad"
                                  className="flex-1 p-2 border border-surface-border rounded-lg bg-white"
                                />

                                <TouchableOpacity
                                  onPress={() => handleCreditEnvelope(
                                    envelope.uuid,
                                    envelope.currentAmount,
                                    envelope.targetedAmount
                                  )}
                                  className="p-2 bg-success-100 rounded-lg"
                                  disabled={!amounts[envelope.uuid] || envelope.pending}
                                >
                                  <Text className="text-success-700 font-medium">{t('envelopes.addFunds')}</Text>
                                </TouchableOpacity>

                                <TouchableOpacity
                                  onPress={() => handleDebitEnvelope(
                                    envelope.uuid,
                                    envelope.currentAmount
                                  )}
                                  className="p-2 bg-danger-100 rounded-lg"
                                  disabled={!amounts[envelope.uuid] || envelope.pending}
                                >
                                  <Text className="text-danger-700 font-medium">{t('envelopes.withdraw')}</Text>
                                </TouchableOpacity>
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
          {envelopesData?.envelopes
            .filter(envelope => {
              const progress = Number(envelope.currentAmount) / Number(envelope.targetedAmount);
              return progress > 0 && progress < 1;
            })
            .length > 0 && (
              <View className="mb-6">
                <Text className="text-lg font-medium text-secondary-600 mb-2">{t('envelopes.inProgress')}</Text>
                <View className={isTablet ? "flex-row flex-wrap justify-between" : "space-y-4"}>
                  {envelopesData.envelopes
                    .filter(envelope => {
                      const progress = Number(envelope.currentAmount) / Number(envelope.targetedAmount);
                      return progress > 0 && progress < 1;
                    })
                    .map((envelope) => (
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
                                      className="flex-1 p-2 border border-surface-border rounded-lg bg-white"
                                      maxLength={25}
                                      autoFocus
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
                                    <TouchableOpacity
                                      onPress={() => navigateToEnvelopeDetail(envelope.uuid)}
                                      className="flex-1"
                                    >
                                      <Text className="text-lg font-semibold text-text-primary">{envelope.name}</Text>
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
                              <View className="flex-row items-center space-x-2">
                                <TextInput
                                  value={amounts[envelope.uuid] || ''}
                                  onChangeText={(text) => handleAmountChange(envelope.uuid, text)}
                                  placeholder={t('envelopes.enterAmount')}
                                  keyboardType="decimal-pad"
                                  className="flex-1 p-2 border border-surface-border rounded-lg bg-white"
                                />

                                <TouchableOpacity
                                  onPress={() => handleCreditEnvelope(
                                    envelope.uuid,
                                    envelope.currentAmount,
                                    envelope.targetedAmount
                                  )}
                                  className="p-2 bg-success-100 rounded-lg"
                                  disabled={!amounts[envelope.uuid] || envelope.pending}
                                >
                                  <Text className="text-success-700 font-medium">{t('envelopes.addFunds')}</Text>
                                </TouchableOpacity>

                                <TouchableOpacity
                                  onPress={() => handleDebitEnvelope(
                                    envelope.uuid,
                                    envelope.currentAmount
                                  )}
                                  className="p-2 bg-danger-100 rounded-lg"
                                  disabled={!amounts[envelope.uuid] || envelope.pending}
                                >
                                  <Text className="text-danger-700 font-medium">{t('envelopes.withdraw')}</Text>
                                </TouchableOpacity>
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
          {envelopesData?.envelopes
            .filter(envelope => Number(envelope.currentAmount) === 0)
            .length > 0 && (
              <View className="mb-6">
                <Text className="text-lg font-medium text-secondary-600 mb-2">{t('envelopes.notStarted')}</Text>
                <View className={isTablet ? "flex-row flex-wrap justify-between" : "space-y-4"}>
                  {envelopesData.envelopes
                    .filter(envelope => Number(envelope.currentAmount) === 0)
                    .map((envelope) => (
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
                                      className="flex-1 p-2 border border-surface-border rounded-lg bg-white"
                                      maxLength={25}
                                      autoFocus
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
                                    <TouchableOpacity
                                      onPress={() => navigateToEnvelopeDetail(envelope.uuid)}
                                      className="flex-1"
                                    >
                                      <Text className="text-lg font-semibold text-text-primary">{envelope.name}</Text>
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
                              <View className="flex-row items-center space-x-2">
                                <TextInput
                                  value={amounts[envelope.uuid] || ''}
                                  onChangeText={(text) => handleAmountChange(envelope.uuid, text)}
                                  placeholder={t('envelopes.enterAmount')}
                                  keyboardType="decimal-pad"
                                  className="flex-1 p-2 border border-surface-border rounded-lg bg-white"
                                />

                                <TouchableOpacity
                                  onPress={() => handleCreditEnvelope(
                                    envelope.uuid,
                                    envelope.currentAmount,
                                    envelope.targetedAmount
                                  )}
                                  className="p-2 bg-success-100 rounded-lg"
                                  disabled={!amounts[envelope.uuid] || envelope.pending}
                                >
                                  <Text className="text-success-700 font-medium">{t('envelopes.addFunds')}</Text>
                                </TouchableOpacity>

                                <TouchableOpacity
                                  onPress={() => handleDebitEnvelope(
                                    envelope.uuid,
                                    envelope.currentAmount
                                  )}
                                  className="p-2 bg-danger-100 rounded-lg"
                                  disabled={!amounts[envelope.uuid] || envelope.pending}
                                >
                                  <Text className="text-danger-700 font-medium">{t('envelopes.withdraw')}</Text>
                                </TouchableOpacity>
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

          {/* Budget Summary Section */}
          {envelopesData?.envelopes?.length > 0 && (
            <View className="bg-secondary-900 rounded-xl p-6 mb-8">
              <View className="bg-secondary-800 rounded-lg p-4 mb-4">
                <Text className="text-xl font-bold text-white">{t('envelopes.budgetOverview')}</Text>
                <Text className="text-secondary-300">
                  {envelopesData.envelopes.filter(e => Number(e.currentAmount) / Number(e.targetedAmount) >= 1).length} {t('envelopes.completedOf')} {envelopesData.envelopes.length} {t('envelopes.title').toLowerCase()}
                </Text>
              </View>
              <View className="flex-row items-center">
                <View className="h-1 flex-1 bg-success-500 rounded-full" />
                <View className="h-1 flex-1 bg-primary-500 rounded-full mx-1" />
                <View className="h-1 flex-1 bg-secondary-500 rounded-full" />
              </View>

            </View>
          )}

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

  const handleRefresh = useCallback(async () => {
    setRefreshing(true);
    await refreshEnvelopes(true);
    setRefreshing(false);
  }, [refreshEnvelopes]);

  const handleCreateEnvelope = useCallback(async (name: string, targetAmount: string, currency: string) => {
    if (name && targetAmount) {
      const formattedTarget = formatAmount(targetAmount);
      await createEnvelope(name, formattedTarget, currency);
      setIsCreating(false);
    }
  }, [createEnvelope]);

  return (
    <View className="flex-1">
      <AnimatedHeaderLayout
        title={t('envelopes.title')}
        subtitle={t('envelopes.subtitle')}
        headerHeight={130}
      >
        <EnvelopesContent />
      </AnimatedHeaderLayout>

      {/* Floating Action Button */}
      <View 
        style={{
          position: 'absolute',
          bottom: 24,
          right: 24,
          zIndex: 50,
        }}
      >
        <TouchableOpacity
          onPress={() => setIsCreating(true)}
          className="bg-primary-600 w-14 h-14 rounded-full shadow-lg items-center justify-center"
          style={{
            elevation: 8, // Pour Android
            shadowColor: "#000", // Pour iOS
            shadowOffset: {
              width: 0,
              height: 4,
            },
            shadowOpacity: 0.30,
            shadowRadius: 4.65,
          }}
        >
          <Ionicons name="add" size={28} color="white" />
        </TouchableOpacity>
      </View>

      {/* Create Envelope Modal */}
      <CreateEnvelopeModal
        visible={isCreating}
        onClose={() => setIsCreating(false)}
        onSubmit={handleCreateEnvelope}
      />
    </View>
  );
}

export default EnvelopesScreen;