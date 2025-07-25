import React, { useEffect, useState } from 'react';
import { View, ScrollView, RefreshControl, TouchableOpacity, Text, Platform } from 'react-native';
import { useLocalSearchParams, useRouter, useFocusEffect } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useTranslation } from '@/utils/useTranslation';
import SwipeBackWrapper from '@/components/SwipeBackWrapper';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';
import EnvelopeProgressCard from '@/components/card/EnvelopeProgressCard';
import EnvelopeQuickActionsCard from '@/components/card/EnvelopeQuickActionsCard';
import EnvelopeCustomAmountCard from '@/components/card/EnvelopeCustomAmountCard';
import EnvelopeTransactionHistoryCard from '@/components/card/EnvelopeTransactionHistoryCard';
import DescriptionModal from '@/components/modals/DescriptionModal';
import DeleteConfirmationModal from '@/components/modals/DeleteConfirmationModal';
import { Ionicons } from '@expo/vector-icons';
import { useEnvelopeData } from '@/hooks/useEnvelopeData';
import { useEnvelopes } from '@/contexts/EnvelopeContext';

export default function EnvelopeDetailScreen() {
  const { uuid } = useLocalSearchParams<{ uuid: string }>();
  const { t } = useTranslation();
  const router = useRouter();
  const [hasAttemptedLoad, setHasAttemptedLoad] = useState(false);
  const { clearCurrentEnvelopeDetails } = useEnvelopes();
  const {
    details,
    loading,
    refreshing,
    editingName,
    setEditingName,
    editingTarget,
    setEditingTarget,
    amount,
    setAmount,
    deleteModalOpen,
    setDeleteModalOpen,
    descriptionModalOpen,
    setDescriptionModalOpen,
    description,
    setDescription,
    currentAction,
    setCurrentAction,
    onRefresh,
    loadEnvelopeDetails,
    handleUpdateName,
    handleUpdateTarget,
    handleCredit,
    handleDebit,
    handleQuickCredit,
    handleQuickDebit,
    handleDescriptionSubmit,
    setError,
    editingTargetError,
    deleteEnvelope,
  } = useEnvelopeData(uuid as string);

  // Clear current envelope details when UUID changes to prevent showing cached data
  useEffect(() => {
    if (uuid) {
      clearCurrentEnvelopeDetails();
      setHasAttemptedLoad(false);
    }
  }, [uuid, clearCurrentEnvelopeDetails]);

  // Always fetch latest details when page is focused
  useFocusEffect(
    React.useCallback(() => {
      loadEnvelopeDetails();
    }, [loadEnvelopeDetails])
  );

  // Track when we've attempted to load data
  useEffect(() => {
    if (!loading && hasAttemptedLoad === false) {
      setHasAttemptedLoad(true);
    }
  }, [loading, hasAttemptedLoad]);

  // Redirect to not-found page if envelope doesn't exist after loading
  useEffect(() => {
    // Only redirect if we've finished loading, attempted to load, and there's definitely no envelope
    // Also ensure we have a valid UUID to prevent false positives
    if (!loading && hasAttemptedLoad && !details && uuid && uuid.length > 0) {
      const timer = setTimeout(() => {
        console.log('Redirecting to not-found - no envelope found after load attempt');
        router.push('/not-found');
      }, 200); // Slightly longer delay to prevent race conditions
      return () => clearTimeout(timer);
    }
  }, [loading, hasAttemptedLoad, details, uuid]);

  const handleDeleteEnvelope = async () => {
    if (!details) return;
    try {
      await deleteEnvelope(details.envelope.uuid, setError);
      setDeleteModalOpen(false);
      router.push('/envelopes');
    } catch (err) {
      setError(t('envelopes.failedToDeleteEnvelope'));
    }
  };

  if (loading) {
    return (
      <View className="flex-1 items-center justify-center bg-white">
        <StatusBar style="dark" />
        <Text>{t('common.loading')}</Text>
      </View>
    );
  }

  if (!details) {
    // This shouldn't render because of the redirect effect above, but keep as fallback
    return null;
  }

  const progress = Number(details.envelope.currentAmount) / Number(details.envelope.targetedAmount);

  // Helper to determine if the envelope name is long
  const getHeaderHeight = () => {
    if (!details?.envelope?.name) return 130;
    return details.envelope.name.length > 40 || details.envelope.name.includes('\n') ? 180 : 130;
  };



  if (Platform.OS === 'web') {
    return (
      <div className="mb-8 mt-2 pb-16">
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
          <div>
            <h1 className="text-3xl font-bold text-slate-900 mb-1">{details.envelope.name}</h1>
            {/* Add subtitle or stats here if needed */}
          </div>
          {/* Place any important actions/info from the blue header here if needed */}
        </div>
        <EnvelopeProgressCard
          name={details.envelope.name}
          currentAmount={Number(details.envelope.currentAmount)}
          targetedAmount={Number(details.envelope.targetedAmount)}
          currency={details.envelope.currency}
          progress={progress}
          editingName={editingName}
          setEditingName={setEditingName}
          editingTarget={editingTarget}
          setEditingTarget={setEditingTarget}
          onUpdateName={handleUpdateName}
          onUpdateTarget={handleUpdateTarget}
          pending={!!details.envelope.pending}
          editingTargetError={editingTargetError ?? undefined}
        />
        <EnvelopeQuickActionsCard
          onQuickCredit={handleQuickCredit}
          onQuickDebit={handleQuickDebit}
          disabled={!!details.envelope.pending}
          currency={details.envelope.currency}
        />
        <EnvelopeCustomAmountCard
          amount={amount}
          setAmount={setAmount}
          onCredit={handleCredit}
          onDebit={handleDebit}
          disabled={!!details.envelope.pending}
          currency={details.envelope.currency}
          currentAmount={Number(details.envelope.currentAmount)}
          targetAmount={Number(details.envelope.targetedAmount)}
          setError={setError}
        />
        <EnvelopeTransactionHistoryCard
          ledger={details.ledger}
          currency={details.envelope.currency}
          loading={loading}
        />
        <div className="flex flex-col items-center w-full">
          <TouchableOpacity
            onPress={() => setDeleteModalOpen(true)}
            className="bg-red-100 rounded-xl p-4 items-center mt-6 mb-10"
            disabled={!!details.envelope.pending}
            accessibilityRole="button"
            accessibilityLabel={t('envelopes.deleteEnvelope')}
            style={{ display: 'flex', justifyContent: 'center' }}
          >
            <Ionicons name="trash-outline" size={20} color="#dc2626" style={{ marginBottom: 2 }} />
            <Text className="text-red-700 font-semibold text-base">{t('envelopes.deleteEnvelope')}</Text>
          </TouchableOpacity>
          <DeleteConfirmationModal
            visible={deleteModalOpen}
            onClose={() => setDeleteModalOpen(false)}
            onConfirm={handleDeleteEnvelope}
            name={details.envelope.name}
            message={t('modals.deleteConfirmation', { name: details.envelope.name })}
          />
          <DescriptionModal
            visible={descriptionModalOpen}
            onClose={() => setDescriptionModalOpen(false)}
            onSubmit={handleDescriptionSubmit}
            actionType={currentAction?.type || 'credit'}
          />
        </div>
        <div className="h-32" />
      </div>
    );
  }

  return (
    <SwipeBackWrapper>
      <View className="flex-1 bg-background-light">
        <AnimatedHeaderLayout
          title={details.envelope.name}
          showBackButton
          headerHeight={getHeaderHeight()}
        >
          <StatusBar style="dark" />
          <ScrollView
            refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
            className="flex-1 bg-background-light"
            contentContainerStyle={{ paddingBottom: 120 }}
          >
            <EnvelopeProgressCard
              name={details.envelope.name}
              currentAmount={Number(details.envelope.currentAmount)}
              targetedAmount={Number(details.envelope.targetedAmount)}
              currency={details.envelope.currency}
              progress={progress}
              editingName={editingName}
              setEditingName={setEditingName}
              editingTarget={editingTarget}
              setEditingTarget={setEditingTarget}
              onUpdateName={handleUpdateName}
              onUpdateTarget={handleUpdateTarget}
              pending={!!details.envelope.pending}
              editingTargetError={editingTargetError ?? undefined}
            />
            <EnvelopeQuickActionsCard
              onQuickCredit={handleQuickCredit}
              onQuickDebit={handleQuickDebit}
              disabled={!!details.envelope.pending}
              currency={details.envelope.currency}
            />
            <EnvelopeCustomAmountCard
              amount={amount}
              setAmount={setAmount}
              onCredit={handleCredit}
              onDebit={handleDebit}
              disabled={!!details.envelope.pending}
              currency={details.envelope.currency}
              currentAmount={Number(details.envelope.currentAmount)}
              targetAmount={Number(details.envelope.targetedAmount)}
              setError={setError}
            />
            <EnvelopeTransactionHistoryCard
              ledger={details.ledger}
              currency={details.envelope.currency}
              loading={loading}
            />
            <TouchableOpacity
              onPress={() => setDeleteModalOpen(true)}
              className="bg-red-100 rounded-xl p-4 items-center mt-6 mb-10"
              disabled={!!details.envelope.pending}
              accessibilityRole="button"
              accessibilityLabel={t('envelopes.deleteEnvelope')}
            >
              <Ionicons name="trash-outline" size={20} color="#dc2626" style={{ marginBottom: 2 }} />
              <Text className="text-red-700 font-semibold text-base">{t('envelopes.deleteEnvelope')}</Text>
            </TouchableOpacity>
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
          </ScrollView>
        </AnimatedHeaderLayout>
      </View>
    </SwipeBackWrapper>
  );
}

