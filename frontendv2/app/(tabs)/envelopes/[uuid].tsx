import React, { useState, useEffect, useCallback, useRef } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  TextInput,
  RefreshControl
} from 'react-native';
import { useLocalSearchParams, Stack, useRouter, useFocusEffect } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { StatusBar } from 'expo-status-bar';
import { useEnvelopes } from '@/contexts/EnvelopeContext';
import { formatCurrency, getCurrencySymbol } from '@/utils/currencyUtils';
import EnvelopePieChart from '@/components/EnvelopePieChart';
import formatAmount from '@/utils/formatAmount';
import validateAmount from '@/utils/validateAmount';
import DescriptionModal from '@/components/modals/DescriptionModal';
import DeleteConfirmationModal from '@/components/modals/DeleteConfirmationModal';
import { useErrorContext } from '@/contexts/ErrorContext';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';
import SwipeBackWrapper from '@/components/SwipeBackWrapper';
import { useTranslation } from '@/utils/useTranslation';
import { useLanguage } from '@/contexts/LanguageContext';

export default function EnvelopeDetailScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const { setError } = useErrorContext();
  const { t } = useTranslation();
  const { language } = useLanguage();
  
  const {
    fetchEnvelopeDetails,
    deleteEnvelope,
    creditEnvelope,
    debitEnvelope,
    updateEnvelopeName,
    updateTargetBudget,
    currentEnvelopeDetails,
    listenToEnvelopeUpdates
  } = useEnvelopes();

  // Access uuid directly from params
  const uuid = params.uuid as string;

  // State
  const [details, setDetails] = useState(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [editingName, setEditingName] = useState(null);
  const [editingTarget, setEditingTarget] = useState(null);
  const [amount, setAmount] = useState('');
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [descriptionModalOpen, setDescriptionModalOpen] = useState(false);
  const [currentAction, setCurrentAction] = useState(null);

  // Ref to track component mount state
  const isMounted = useRef(true);

  // Load envelope details
  const loadEnvelopeDetails = useCallback(async () => {
    if (!uuid || !isMounted.current) return;

    try {
      if (!refreshing) {
        setLoading(true);
      }

      const response = await fetchEnvelopeDetails(uuid);
      
      if (isMounted.current && response) {
        setDetails(response);
      }
    } catch (err) {
      if (isMounted.current) {
        setError(t('errors.loadEnvelopeDetails'));
      }
    } finally {
      if (isMounted.current) {
        setLoading(false);
      }
    }
  }, [uuid, fetchEnvelopeDetails, setError, refreshing, t]);

  // Use useFocusEffect instead of useEffect for initial loading
  useFocusEffect(
    useCallback(() => {
      console.log('Screen focused, reloading data');
      isMounted.current = true;
      loadEnvelopeDetails();

      return () => {
        console.log('Screen blurred');
        isMounted.current = false;
      };
    }, [loadEnvelopeDetails])
  );

  // Watch for updates to currentEnvelopeDetails from context
  useEffect(() => {
    if (!currentEnvelopeDetails || !uuid) return;

    // Only update if the details are for this envelope
    if (currentEnvelopeDetails.envelope.uuid === uuid) {
      setDetails(currentEnvelopeDetails);
      setLoading(false);
    }
  }, [currentEnvelopeDetails, uuid]);

  // Setup envelope updates listener
  useEffect(() => {
    if (!uuid) return;

    // Setup listener for envelope updates
    const cleanup = listenToEnvelopeUpdates(
      uuid,
      // Update callback
      () => {
        if (isMounted.current) {
          loadEnvelopeDetails();
        }
      },
      // Delete callback
      () => {
        if (isMounted.current) {
          setError(t('envelopes.envelopeDeleted'));
          router.back();
        }
      }
    );

    return cleanup;
  }, [uuid, listenToEnvelopeUpdates, loadEnvelopeDetails, router, setError, t]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await loadEnvelopeDetails();
    setRefreshing(false);
  }, [loadEnvelopeDetails]);

  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString(language === 'fr' ? 'fr-FR' : 'en-US', { month: '2-digit', day: '2-digit', year: 'numeric' });
  }

  function handleNameChange(newName) {
    setEditingName({ name: newName });
  }

  async function handleUpdateName() {
    if (!details || !editingName) return;

    const newName = editingName.name.trim();

    if (newName === details.envelope.name) {
      setEditingName(null);
      return;
    }

    if (newName.length > 25) {
      setError(t('envelopes.nameExceedsLimit'));
      return;
    }

    if (newName === '') {
      setError(t('envelopes.nameCannotBeEmpty'));
      return;
    }

    try {
      // Update pending state in UI to show loading indicator
      setDetails(prev => ({
        ...prev,
        envelope: {
          ...prev.envelope,
          pending: true
        }
      }));

      await updateEnvelopeName(details.envelope.uuid, newName, setError);
      // Wait a moment to ensure the WebSocket event has time to arrive
      setTimeout(() => {
        if (isMounted.current) {
          loadEnvelopeDetails();
        }
      }, 800);
    } catch (err) {
      console.error('Failed to update name:', err);
      // Reset pending state on error
      setDetails(prev => ({
        ...prev,
        envelope: {
          ...prev.envelope,
          pending: false
        }
      }));
    } finally {
      setEditingName(null);
    }
  }

  function handleTargetChange(newTarget) {
    setEditingTarget({ amount: newTarget });
  }

  async function handleUpdateTarget() {
    if (!details || !editingTarget) return;

    const newTarget = formatAmount(editingTarget.amount);
    const currentAmount = details.envelope.currentAmount;

    if (Number(newTarget) === Number(details.envelope.targetedAmount)) {
      setEditingTarget(null);
      return;
    }

    if (Number(newTarget) < Number(currentAmount)) {
      setError(t('envelopes.targetLessThanCurrent'));
      return;
    }

    try {
      // Update pending state in UI to show loading indicator
      setDetails(prev => ({
        ...prev,
        envelope: {
          ...prev.envelope,
          pending: true
        }
      }));

      await updateTargetBudget(details.envelope.uuid, newTarget, currentAmount, setError);
      // Wait a moment to ensure the WebSocket event has time to arrive
      setTimeout(() => {
        if (isMounted.current) {
          loadEnvelopeDetails();
        }
      }, 800);
    } catch (err) {
      console.error('Failed to update target budget:', err);
      // Reset pending state on error
      setDetails(prev => ({
        ...prev,
        envelope: {
          ...prev.envelope,
          pending: false
        }
      }));
    } finally {
      setEditingTarget(null);
    }
  }

  function handleCredit() {
    if (!amount || !validateAmount(amount)) {
      setError(t('errors.invalidAmount'));
      return;
    }

    const processedAmount = formatAmount(amount);

    if (Number(processedAmount) <= 0) {
      setError(t('errors.amountMustBePositive'));
      return;
    }

    setCurrentAction({
      type: 'credit',
      amount: processedAmount,
    });

    setDescriptionModalOpen(true);
  }

  function handleDebit() {
    if (!details) return;

    if (!amount || !validateAmount(amount)) {
      setError(t('errors.invalidAmount'));
      return;
    }

    const processedAmount = formatAmount(amount);

    if (Number(processedAmount) <= 0) {
      setError(t('errors.amountMustBePositive'));
      return;
    }

    if (Number(processedAmount) > Number(details.envelope.currentAmount)) {
      setError(t('errors.cannotDebitMoreThanBalance'));
      return;
    }

    setCurrentAction({
      type: 'debit',
      amount: processedAmount,
    });

    setDescriptionModalOpen(true);
  }

  async function handleDeleteEnvelope() {
    if (!details) return;

    try {
      // Update pending state in UI
      setDetails(prev => ({
        ...prev,
        envelope: {
          ...prev.envelope,
          pending: true
        }
      }));

      await deleteEnvelope(details.envelope.uuid, setError);
      // WebSocket event will handle navigation back
    } catch (err) {
      console.error('Failed to delete envelope:', err);
      // Reset pending state on error
      setDetails(prev => ({
        ...prev,
        envelope: {
          ...prev.envelope,
          pending: false
        }
      }));
    } finally {
      setDeleteModalOpen(false);
    }
  }

  async function handleDescriptionSubmit(description) {
    if (!details || !currentAction) return;

    try {
      // Update pending state in UI
      setDetails(prev => ({
        ...prev,
        envelope: {
          ...prev.envelope,
          pending: true
        }
      }));

      if (currentAction.type === 'credit') {
        await creditEnvelope(
          details.envelope.uuid,
          currentAction.amount,
          description,
          setError
        );
      } else {
        await debitEnvelope(
          details.envelope.uuid,
          currentAction.amount,
          description,
          setError
        );
      }

      setAmount('');
      // Wait a moment to ensure the WebSocket event has time to arrive
      setTimeout(() => {
        if (isMounted.current) {
          loadEnvelopeDetails();
        }
      }, 800);
    } catch (err) {
      console.error('Failed to process transaction:', err);
      // Reset pending state on error
      setDetails(prev => ({
        ...prev,
        envelope: {
          ...prev.envelope,
          pending: false
        }
      }));
    } finally {
      setDescriptionModalOpen(false);
      setCurrentAction(null);
    }
  }

  function handleQuickAction(actionType, actionAmount) {
    setAmount(actionAmount);
    setCurrentAction({ type: actionType, amount: actionAmount });
    setDescriptionModalOpen(true);
  }

  if (loading && !details) {
    return (
      <View className="flex-1 justify-center items-center bg-white">
        <ActivityIndicator size="large" color="#6366f1" />
      </View>
    );
  }

  if (!details) {
    return (
      <View className="flex-1 justify-center items-center bg-white">
        <Text className="text-gray-600 text-lg">{t('envelopes.notFound')}</Text>
        <TouchableOpacity
          className="mt-4 px-4 py-2 bg-indigo-600 rounded-md"
          onPress={() => router.back()}
        >
          <Text className="text-white">{t('common.goBack')}</Text>
        </TouchableOpacity>
      </View>
    );
  }

  const progress = Number(details.envelope.currentAmount) / Number(details.envelope.targetedAmount);
  const headerTitle = details && (
    <View className="flex-row items-center">
      {editingName ? (
        <View className="flex-row items-center flex-1">
          <TextInput
            value={editingName.name}
            onChangeText={handleNameChange}
            className="flex-1 bg-white/20 px-3 py-2 rounded-lg text-white text-lg font-semibold"
            maxLength={25}
            autoFocus
            placeholderTextColor="#e0e7ff"
          />
          <TouchableOpacity
            onPress={handleUpdateName}
            className="ml-2 p-2 bg-white/20 rounded-full"
          >
            <Ionicons name="checkmark" size={20} color="#ffffff" />
          </TouchableOpacity>
          <TouchableOpacity
            onPress={() => setEditingName(null)}
            className="ml-2 p-2 bg-white/20 rounded-full"
          >
            <Ionicons name="close" size={20} color="#ffffff" />
          </TouchableOpacity>
        </View>
      ) : (
        <>
          <TouchableOpacity
            onPress={() => details && setEditingName({ name: details.envelope.name })}
            className="flex-row items-center flex-1"
            disabled={details?.envelope.pending}
          >
            <Text className="text-3xl font-bold text-white">{details?.envelope.name}</Text>
            <Ionicons name="create-outline" size={20} color="white" className="ml-2" />
          </TouchableOpacity>
          <TouchableOpacity
            onPress={() => setDeleteModalOpen(true)}
            className="bg-white/20 p-2 rounded-full"
          >
            <Ionicons name="trash-outline" size={20} color="white" />
          </TouchableOpacity>
        </>
      )}
    </View>
  );

  const headerContent = details && (
    <View className="mt-4">
      <View className="flex-row justify-between items-center">
        <View>
          <Text className="text-primary-100">{t('envelopes.currentBalance')}</Text>
          <Text className="text-3xl font-bold text-white">
            {formatCurrency(details.envelope.currentAmount, details.envelope.currency)}
          </Text>

          <View className="flex-row items-center mt-2">
            <Text className="text-primary-100">
              {t('envelopes.of')}{' '}
              {editingTarget ? (
                <View className="inline-flex flex-row items-center bg-white/20 rounded-lg px-2 py-1">
                  <Text className="text-white">
                    {getCurrencySymbol(details.envelope.currency)}
                  </Text>
                  <TextInput
                    value={editingTarget.amount}
                    onChangeText={handleTargetChange}
                    className="text-white w-20"
                    keyboardType="numeric"
                    maxLength={10}
                    autoFocus
                  />
                  <TouchableOpacity
                    onPress={handleUpdateTarget}
                    className="ml-1 p-1 bg-white/20 rounded-full"
                  >
                    <Ionicons name="checkmark" size={14} color="#ffffff" />
                  </TouchableOpacity>
                  <TouchableOpacity
                    onPress={() => setEditingTarget(null)}
                    className="ml-1 p-1 bg-white/20 rounded-full"
                  >
                    <Ionicons name="close" size={14} color="#ffffff" />
                  </TouchableOpacity>
                </View>
              ) : (
                <TouchableOpacity
                  onPress={() => details && setEditingTarget({ amount: details.envelope.targetedAmount })}
                  className="inline-flex flex-row items-center"
                  disabled={details?.envelope.pending}
                >
                  <Text className="text-white font-medium">
                    {formatCurrency(details.envelope.targetedAmount, details.envelope.currency)}
                  </Text>
                  <Ionicons name="create-outline" size={14} color="white" className="ml-1" />
                </TouchableOpacity>
              )}
              {' '}{t('envelopes.goal')}
            </Text>

            {progress >= 1 ? (
              <View className="bg-success-50 px-2 py-1 rounded-full ml-2">
                <Text className="text-success-700 text-xs font-medium">{t('envelopes.completed')}</Text>
              </View>
            ) : (
              <View className="bg-white/20 px-2 py-1 rounded-full ml-2">
                <Text className="text-white text-xs font-medium">{Math.round(progress * 100)}% {t('envelopes.filled')}</Text>
              </View>
            )}
          </View>
        </View>

        <View className="w-24 h-24">
          <EnvelopePieChart
            currentAmount={Number(details.envelope.currentAmount)}
            targetedAmount={Number(details.envelope.targetedAmount)}
          />
        </View>
      </View>
    </View>
  );



  return (
    <SwipeBackWrapper>
    <View className="flex-1 bg-background-subtle">
      <StatusBar style="light" />

      <AnimatedHeaderLayout
        title={headerTitle}
        headerHeight={200}
        showBackButton
        headerContent={headerContent}
        collapsePercentage={40}
      >
        <ScrollView
          className="flex-1"
          contentContainerStyle={{ padding: 16, paddingBottom: 80 }}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
          }
        >
          {/* Quick Actions Card */}
          <View className="card bg-white rounded-2xl shadow-md overflow-hidden mb-6">
            <View className="p-6">
              <Text className="text-lg font-semibold text-text-primary mb-4">{t('envelopes.quickActions')}</Text>

              <View className="flex-row space-x-3 mb-4">
                <TouchableOpacity
                  onPress={() => handleQuickAction('credit', '10')}
                  className="flex-1 bg-success-100 p-4 rounded-xl flex-row items-center justify-center"
                >
                  <Ionicons name="arrow-down-circle-outline" size={20} color="#16a34a" />
                  <Text className="text-success-700 font-medium ml-2">{t('envelopes.add')} $10</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  onPress={() => handleQuickAction('debit', '10')}
                  className="flex-1 bg-danger-100 p-4 rounded-xl flex-row items-center justify-center"
                  disabled={Number(details.envelope.currentAmount) < 10}
                >
                  <Ionicons name="arrow-up-circle-outline" size={20} color="#dc2626" />
                  <Text className="text-danger-700 font-medium ml-2">{t('envelopes.spend')} $10</Text>
                </TouchableOpacity>
              </View>

              <View className="flex-row space-x-3">
                <TouchableOpacity
                  onPress={() => handleQuickAction('credit', '25')}
                  className="flex-1 bg-success-100 p-4 rounded-xl flex-row items-center justify-center"
                >
                  <Ionicons name="arrow-down-circle-outline" size={20} color="#16a34a" />
                  <Text className="text-success-700 font-medium ml-2">{t('envelopes.add')} $25</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  onPress={() => handleQuickAction('debit', '25')}
                  className="flex-1 bg-danger-100 p-4 rounded-xl flex-row items-center justify-center"
                  disabled={Number(details.envelope.currentAmount) < 25}
                >
                  <Ionicons name="arrow-up-circle-outline" size={20} color="#dc2626" />
                  <Text className="text-danger-700 font-medium ml-2">{t('envelopes.spend')} $25</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>

          {/* Custom Amount Card */}
          <View className="card bg-white rounded-2xl shadow-md overflow-hidden mb-6">
            <View className="p-6">
              <Text className="text-lg font-semibold text-text-primary mb-4">{t('envelopes.customAmount')}</Text>

              <TextInput
                value={amount}
                onChangeText={setAmount}
                placeholder={t('envelopes.enterAmount')}
                keyboardType="decimal-pad"
                className="border border-surface-border rounded-lg p-3 mb-3 bg-white"
              />

              <View className="flex-row space-x-3">
                <TouchableOpacity
                  onPress={handleCredit}
                  className="flex-1 bg-success-500 p-3 rounded-xl"
                  disabled={
                    details.envelope.pending ||
                    !amount ||
                    Number(amount) <= 0
                  }
                >
                  <Text className="text-white text-center font-semibold">{t('envelopes.credit')}</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  onPress={handleDebit}
                  className="flex-1 bg-danger-500 p-3 rounded-xl"
                  disabled={
                    details.envelope.pending ||
                    !amount ||
                    Number(amount) <= 0 ||
                    Number(amount) > Number(details.envelope.currentAmount)
                  }
                >
                  <Text className="text-white text-center font-semibold">{t('envelopes.debit')}</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>

          {/* Envelope Details Card */}
          <View className="card bg-white rounded-2xl shadow-md overflow-hidden mb-6">
            <View className="p-6">
              <Text className="text-lg font-semibold text-text-primary mb-4">{t('envelopes.details')}</Text>

              <View className="space-y-4">
                <View className="flex-row justify-between">
                  <Text className="text-text-secondary">{t('envelopes.created')}</Text>
                  <Text className="text-text-primary font-medium">
                    {formatDate(details.envelope.createdAt)}
                  </Text>
                </View>

                <View className="flex-row justify-between">
                  <Text className="text-text-secondary">{t('envelopes.lastUpdated')}</Text>
                  <Text className="text-text-primary font-medium">
                    {formatDate(details.envelope.updatedAt)}
                  </Text>
                </View>

                <View className="flex-row justify-between">
                  <Text className="text-text-secondary">{t('envelopes.currency')}</Text>
                  <Text className="text-text-primary font-medium">{details.envelope.currency}</Text>
                </View>

                <View className="flex-row justify-between">
                  <Text className="text-text-secondary">{t('envelopes.status')}</Text>
                  {progress >= 1 ? (
                    <Text className="text-success-600 font-medium">{t('envelopes.completed')}</Text>
                  ) : progress > 0.5 ? (
                    <Text className="text-primary-600 font-medium">{t('envelopes.goodProgress')}</Text>
                  ) : progress > 0 ? (
                    <Text className="text-warning-600 font-medium">{t('envelopes.gettingStarted')}</Text>
                  ) : (
                    <Text className="text-secondary-600 font-medium">{t('envelopes.notStarted')}</Text>
                  )}
                </View>
              </View>
            </View>
          </View>

          {/* Transaction History Card */}
          <View className="card bg-white rounded-2xl shadow-md overflow-hidden mb-6">
            <View className="p-6">
              <Text className="text-lg font-semibold text-text-primary mb-4">{t('envelopes.transactionHistory')}</Text>

              {details.ledger.length === 0 ? (
                <View className="py-4 items-center">
                  <View className="w-16 h-16 rounded-full bg-secondary-100 items-center justify-center mb-2">
                    <Ionicons name="document-text-outline" size={24} color="#64748b" />
                  </View>
                  <Text className="text-text-secondary text-center">{t('envelopes.noTransactions')}</Text>
                </View>
              ) : (
                <View className="space-y-3">
                  {details.ledger.map((transaction, index) => (
                    <View
                      key={index}
                      className="p-3 bg-background-subtle rounded-lg flex-row justify-between items-center"
                    >
                      <View className="flex-row items-center">
                        <View className={`w-10 h-10 rounded-full items-center justify-center mr-3 ${transaction.entry_type === "credit"
                          ? "bg-success-100"
                          : "bg-danger-100"
                          }`}>
                          <Ionicons
                            name={transaction.entry_type === "credit" ? "arrow-down" : "arrow-up"}
                            size={18}
                            color={transaction.entry_type === "credit" ? "#16a34a" : "#dc2626"}
                          />
                        </View>

                        <View>
                          <Text className="font-medium text-text-primary">
                            {transaction.description || (transaction.entry_type === "credit" ? t('envelopes.addedFunds') : t('envelopes.spentFunds'))}
                          </Text>
                          <Text className="text-sm text-text-secondary">
                            {formatDate(transaction.created_at)}
                          </Text>
                        </View>
                      </View>

                      <Text
                        className={transaction.entry_type === "credit"
                          ? "text-success-600 font-semibold"
                          : "text-danger-600 font-semibold"
                        }
                      >
                        {transaction.entry_type === "credit"
                          ? `+${formatCurrency(transaction.monetary_amount, details.envelope.currency)}`
                          : `-${formatCurrency(transaction.monetary_amount, details.envelope.currency)}`
                        }
                      </Text>
                    </View>
                  ))}
                </View>
              )}
            </View>
          </View>

          {/* Delete Envelope Button */}
          <TouchableOpacity
            onPress={() => setDeleteModalOpen(true)}
            className="bg-danger-100 rounded-xl p-4 items-center mb-10"
            disabled={details.envelope.pending}
          >
            <Text className="text-danger-700 font-semibold">{t('envelopes.deleteEnvelope')}</Text>
          </TouchableOpacity>
        </ScrollView>

        <DeleteConfirmationModal
          visible={deleteModalOpen}
          onClose={() => setDeleteModalOpen(false)}
          onConfirm={handleDeleteEnvelope}
          name={details.envelope.name}
        />

        <DescriptionModal
          visible={descriptionModalOpen}
          onClose={() => setDescriptionModalOpen(false)}
          onSubmit={handleDescriptionSubmit}
          actionType={currentAction?.type || 'credit'}
        />
      </AnimatedHeaderLayout>
    </View>
    </SwipeBackWrapper>
  );
}

