import React from 'react';
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

export default function EnvelopeDetailScreen() {
  const { uuid } = useLocalSearchParams<{ uuid: string }>();
  const { t } = useTranslation();
  const router = useRouter();
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

  // Always fetch latest details when page is focused
  useFocusEffect(
    React.useCallback(() => {
      loadEnvelopeDetails();
    }, [loadEnvelopeDetails])
  );

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
    return (
      <View className="flex-1 items-center justify-center bg-white">
        <StatusBar style="dark" />
        <Text>{t('envelopes.notFound')}</Text>
      </View>
    );
  }

  const progress = Number(details.envelope.currentAmount) / Number(details.envelope.targetedAmount);

  // Helper to determine if the envelope name is long
  const getHeaderHeight = () => {
    if (!details?.envelope?.name) return 130;
    return details.envelope.name.length > 40 || details.envelope.name.includes('\n') ? 180 : 130;
  };

  // Web-specific delete confirmation dialog
  const renderWebDeleteDialog = () => {
    if (!deleteModalOpen) return null;
    return (
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="delete-envelope-title"
        className="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        tabIndex={-1}
      >
        <div className="bg-white rounded-2xl shadow-xl w-full max-w-md p-8 flex flex-col items-center">
          <div className="w-16 h-16 rounded-full bg-danger-100 flex items-center justify-center mb-4">
            <Ionicons name="alert-outline" size={32} color="#dc2626" />
          </div>
          <h2 id="delete-envelope-title" className="text-2xl font-bold text-text-primary mb-2">{t('modals.confirmDeletion')}</h2>
          <p className="text-center text-text-secondary mb-6">
            {t('modals.deleteConfirmation', { name: details.envelope.name })}
          </p>
          <div className="flex w-full gap-3 mt-2">
            <button
              onClick={() => setDeleteModalOpen(false)}
              className="flex-1 py-3 border border-gray-300 rounded-xl text-text-primary text-base font-medium bg-white hover:bg-gray-50 transition"
              autoFocus
            >
              {t('common.cancel')}
            </button>
            <button
              onClick={handleDeleteEnvelope}
              className="flex-1 py-3 bg-danger-600 rounded-xl text-white text-base font-semibold hover:bg-danger-700 transition"
            >
              {t('common.delete')}
            </button>
          </div>
        </div>
      </div>
    );
  };

  if (Platform.OS === 'web') {
    return (
      <div className="mb-8 mt-2">
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
          {renderWebDeleteDialog()}
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

