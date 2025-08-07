import React, { useEffect, useState } from 'react';
import { View, ScrollView, RefreshControl, TouchableOpacity, Text, Platform, TextInput, ActivityIndicator } from 'react-native';
import { useLocalSearchParams, useRouter, useFocusEffect } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useTranslation } from '@/utils/useTranslation';
import SwipeBackWrapper from '@/components/SwipeBackWrapper';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';
import EnvelopeTransactionHistoryCard from '@/components/card/EnvelopeTransactionHistoryCard';
import DescriptionModal from '@/components/modals/DescriptionModal';
import DeleteConfirmationModal from '@/components/modals/DeleteConfirmationModal';
import ChangeCurrencyModal from '@/components/modals/ChangeCurrencyModal';
import { Ionicons } from '@expo/vector-icons';
import { useEnvelopeData } from '@/hooks/useEnvelopeData';
import { useEnvelopes } from '@/contexts/EnvelopeContext';
import { formatCurrency } from '@/utils/currencyUtils';
import { normalizeAmountInput } from '@/utils/normalizeAmountInput';
import { validateEnvelopeAmountField } from '@/utils/validateEnvelopeAmount';

export default function EnvelopeDetailScreen() {
  const { uuid } = useLocalSearchParams<{ uuid: string }>();
  const { t } = useTranslation();
  const router = useRouter();
  const [hasAttemptedLoad, setHasAttemptedLoad] = useState(false);
  const { clearCurrentEnvelopeDetails } = useEnvelopes();
  
  // New state for inline editing - must be declared before any conditional logic
  const [amountFocused, setAmountFocused] = useState(false);
  const [amountError, setAmountError] = useState<string | null>(null);
  const [amountTouched, setAmountTouched] = useState(false);
  
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
    changeCurrencyModalOpen,
    setChangeCurrencyModalOpen,
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
    handleChangeCurrency,
    setError,
    editingTargetError,
    deleteEnvelope,
  } = useEnvelopeData(uuid as string);
  
  // Validate amount field using shared validation
  const validateAmountField = (value: string) => validateEnvelopeAmountField(value, t);
  
  // Update validation when amount changes
  useEffect(() => {
    if (amountTouched) {
      setAmountError(validateAmountField(amount));
    }
  }, [amount, amountTouched]);

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
      <div className="min-h-screen bg-slate-50">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 max-w-7xl">
          {/* Mobile-First Responsive Header */}
          <div className="mb-4 sm:mb-8">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 sm:gap-6 mb-4 sm:mb-6">
              <div className="flex-1 min-w-0">
                <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900 tracking-tight break-words leading-tight">
                  {details.envelope.name}
                </h1>
                <p className="text-slate-600 mt-1 text-sm sm:text-base">
                  {formatCurrency(Number(details.envelope.currentAmount), details.envelope.currency)} 
                  <span className="text-slate-400 mx-1 sm:mx-2">•</span>
                  {Math.round(progress * 100)}% {t('envelopes.complete')}
                </p>
              </div>
              <div className="flex items-center justify-end space-x-2 sm:space-x-3 flex-shrink-0">
                <button
                  onClick={() => setChangeCurrencyModalOpen(true)}
                  className="flex items-center px-3 py-2 bg-blue-100 rounded-xl hover:bg-blue-200 transition text-sm sm:text-base"
                  disabled={!!details.envelope.pending}
                >
                  <Ionicons name="cash-outline" size={18} color="#0284c7" style={{ marginRight: 6 }} />
                  <span className="text-blue-700 font-semibold hidden sm:inline">{t('envelopes.changeCurrency')}</span>
                  <span className="text-blue-700 font-semibold sm:hidden">{details.envelope.currency}</span>
                </button>
                <button
                  onClick={() => setDeleteModalOpen(true)}
                  className="p-2 sm:p-3 rounded-xl bg-red-50 hover:bg-red-100 transition-colors"
                  disabled={!!details.envelope.pending}
                >
                  <Ionicons name="trash-outline" size={16} color="#dc2626" />
                </button>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-5 xl:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
            {/* Left Column - Main Content */}
            <div className="lg:col-span-3 xl:col-span-2 space-y-4 sm:space-y-6">
              {/* Progress Overview Card */}
              <div className="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-slate-200 p-4 sm:p-6 lg:p-8">
                <div className="flex items-center justify-between mb-4 sm:mb-6">
                  <h2 className="text-lg sm:text-xl lg:text-2xl font-bold text-slate-900">{t('envelopes.progress')}</h2>
                  <div className="flex items-center space-x-2">
                    <div className="px-2 sm:px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs sm:text-sm font-semibold">
                      {Math.round(progress * 100)}%
                    </div>
                  </div>
                </div>
                
                {/* Progress Bar */}
                <div className="relative">
                  <div className="h-4 bg-slate-200 rounded-full overflow-hidden">
                    <div 
                      className="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-500"
                      style={{ width: `${Math.min(progress * 100, 100)}%` }}
                    />
                  </div>
                  <div className="flex justify-between text-sm text-slate-600 mt-2">
                    <span>{formatCurrency(Number(details.envelope.currentAmount), details.envelope.currency)}</span>
                    <span>{formatCurrency(Number(details.envelope.targetedAmount), details.envelope.currency)}</span>
                  </div>
                </div>

                {/* Edit Target Amount */}
                <div className="mt-6">
                  <div className="flex items-center justify-between">
                    <label className="text-sm font-medium text-slate-700">{t('envelopes.target')}</label>
                    {editingTarget !== null ? (
                      <div className="flex items-center space-x-2">
                        <input
                          type="text"
                          value={editingTarget}
                          onChange={(e) => setEditingTarget(normalizeAmountInput(e.target.value))}
                          className="w-32 px-3 py-1 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          maxLength={13}
                          autoFocus
                          disabled={!!details.envelope.pending}
                        />
                        <TouchableOpacity
                          onPress={handleUpdateTarget}
                          className="p-2 rounded-full bg-green-50"
                        >
                          <Ionicons name="checkmark" size={16} color="#16a34a" />
                        </TouchableOpacity>
                        <TouchableOpacity
                          onPress={() => setEditingTarget(null)}
                          className="p-2 rounded-full bg-red-50"
                        >
                          <Ionicons name="close" size={16} color="#dc2626" />
                        </TouchableOpacity>
                      </div>
                    ) : (
                      <button
                        onClick={() => setEditingTarget(String(details.envelope.targetedAmount))}
                        disabled={!!details.envelope.pending}
                        className="flex items-center space-x-1 px-2 py-1 rounded-lg hover:bg-slate-100 transition-colors"
                      >
                        <span className="text-lg font-semibold text-slate-900">
                          {formatCurrency(Number(details.envelope.targetedAmount), details.envelope.currency)}
                        </span>
                        <Ionicons name="create-outline" size={16} color="#64748b" />
                      </button>
                    )}
                  </div>
                  {editingTargetError && (
                    <p className="text-red-500 text-sm mt-1">{editingTargetError}</p>
                  )}
                </div>

                {/* Edit Name */}
                <div className="mt-4 pt-4 border-t border-slate-200">
                  <div className="flex items-center justify-between gap-4">
                    <label className="text-sm font-medium text-slate-700 shrink-0">{t('envelopes.name')}</label>
                    {editingName !== null ? (
                      <div className="flex items-center space-x-2 flex-1 min-w-0">
                        <input
                          type="text"
                          value={editingName}
                          onChange={(e) => setEditingName(e.target.value)}
                          className="flex-1 min-w-0 px-3 py-1 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          maxLength={25}
                          autoFocus
                          disabled={!!details.envelope.pending}
                        />
                        <TouchableOpacity
                          onPress={handleUpdateName}
                          className="p-2 rounded-full bg-green-50 shrink-0"
                        >
                          <Ionicons name="checkmark" size={16} color="#16a34a" />
                        </TouchableOpacity>
                        <TouchableOpacity
                          onPress={() => setEditingName(null)}
                          className="p-2 rounded-full bg-red-50 shrink-0"
                        >
                          <Ionicons name="close" size={16} color="#dc2626" />
                        </TouchableOpacity>
                      </div>
                    ) : (
                      <button
                        onClick={() => setEditingName(details.envelope.name)}
                        disabled={!!details.envelope.pending}
                        className="flex items-center space-x-1 px-2 py-1 rounded-lg hover:bg-slate-100 transition-colors max-w-md"
                      >
                        <span className="text-slate-900 truncate">{details.envelope.name}</span>
                        <Ionicons name="create-outline" size={16} color="#64748b" />
                      </button>
                    )}
                  </div>
                </div>
              </div>

              {/* Actions Card */}
              <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                <h3 className="text-xl font-bold text-slate-900 mb-6">{t('envelopes.actions')}</h3>
                
                {/* Custom Amount Input */}
                <div className="mb-6">
                  <label className="block text-sm font-medium text-slate-700 mb-2">
                    {t('envelopes.customAmount')}
                  </label>
                  {amountTouched && amountFocused && amountError && (
                    <p className="text-red-500 text-xs mb-1 ml-1">{amountError}</p>
                  )}
                  <div>
                    <input
                      type="text"
                      value={amount}
                      onChange={(e) => {
                        const normalized = normalizeAmountInput(e.target.value);
                        const filtered = normalized.replace(/[^0-9.]/g, '');
                        setAmount(filtered);
                        setAmountTouched(true);
                      }}
                      onFocus={() => setAmountFocused(true)}
                      onBlur={() => setAmountFocused(false)}
                      placeholder={t('envelopes.enterAmount')}
                      className="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                      maxLength={13}
                      disabled={!!details.envelope.pending}
                    />
                    <div className="flex justify-end space-x-2 mt-2">
                      <button
                        onClick={() => {
                          const error = validateEnvelopeAmountField(amount, t);
                          setAmountError(error);
                          setAmountTouched(true);
                          if (error || !amount.trim()) return;
                          const current = Number(details.envelope.currentAmount);
                          const target = Number(details.envelope.targetedAmount);
                          const entered = parseFloat(amount);
                          if (entered + current > target) {
                            setError(t('envelopes.cannotCreditMoreThanTarget'));
                            return;
                          }
                          handleCredit();
                        }}
                        disabled={!amount || !!details.envelope.pending || !!amountError}
                        className="px-3 py-2 bg-success-100 rounded-lg hover:bg-success-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <span className="text-success-700 font-medium text-sm">{t('envelopes.addFunds')}</span>
                      </button>
                      <button
                        onClick={() => {
                          const error = validateEnvelopeAmountField(amount, t);
                          setAmountError(error);
                          setAmountTouched(true);
                          if (error || !amount.trim()) return;
                          const current = Number(details.envelope.currentAmount);
                          const entered = parseFloat(amount);
                          if (entered > current) {
                            setError(t('envelopes.cannotDebitMoreThanBalance'));
                            return;
                          }
                          handleDebit();
                        }}
                        disabled={!amount || !!details.envelope.pending || !!amountError}
                        className="px-3 py-2 bg-danger-100 rounded-lg hover:bg-danger-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <span className="text-danger-700 font-medium text-sm">{t('envelopes.withdraw')}</span>
                      </button>
                    </div>
                  </div>
                </div>

                {/* Quick Actions */}
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-3">
                    {t('envelopes.quickActions')}
                  </label>
                  <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                    {[10, 25, 50, 100].map((quickAmount) => (
                      <div key={quickAmount} className="space-y-2">
                        <button
                          onClick={() => handleQuickCredit(String(quickAmount))}
                          disabled={!!details.envelope.pending}
                          className="w-full px-3 py-2 bg-green-100 text-green-800 rounded-lg hover:bg-green-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm font-medium"
                        >
                          +{quickAmount} {details.envelope.currency}
                        </button>
                        <button
                          onClick={() => handleQuickDebit(String(quickAmount))}
                          disabled={!!details.envelope.pending}
                          className="w-full px-3 py-2 bg-red-100 text-red-800 rounded-lg hover:bg-red-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm font-medium"
                        >
                          -{quickAmount} {details.envelope.currency}
                        </button>
                      </div>
                    ))}
                  </div>
                </div>

                {details.envelope.pending && (
                  <div className="mt-4 p-4 bg-blue-50 rounded-xl flex items-center space-x-3">
                    <ActivityIndicator size="small" color="#3b82f6" />
                    <span className="text-blue-800 text-sm font-medium">{t('common.processing')}</span>
                  </div>
                )}
              </div>
            </div>

            {/* Right Column - Transaction History */}
            <div className="lg:col-span-2 xl:col-span-1">
              <div className="lg:sticky lg:top-8">
                <EnvelopeTransactionHistoryCard
                  ledger={details.ledger}
                  currency={details.envelope.currency}
                  loading={loading}
                />
              </div>
            </div>
          </div>

          {/* Modals */}
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
          <ChangeCurrencyModal
            visible={changeCurrencyModalOpen}
            onClose={() => setChangeCurrencyModalOpen(false)}
            onSubmit={handleChangeCurrency}
            currentCurrency={details.envelope.currency}
            itemName={details.envelope.name}
            loading={loading}
          />
        </div>
      </div>
    );
  }

  return (
    <SwipeBackWrapper>
      <View className="flex-1 bg-slate-50">
        <AnimatedHeaderLayout
          title={editingName !== null ? (
            <View className="flex-row items-center flex-1 max-w-full">
              <TextInput
                value={editingName}
                onChangeText={setEditingName}
                className="flex-1 bg-white/20 px-3 py-2 rounded-lg text-white text-lg font-semibold"
                maxLength={25}
                autoFocus
                editable={!details.envelope.pending}
                placeholder={t('envelopes.enterName')}
                placeholderTextColor="rgba(255, 255, 255, 0.7)"
              />
              <TouchableOpacity 
                onPress={handleUpdateName} 
                className="ml-2 p-2 bg-white/20 rounded-full"
                disabled={details.envelope.pending}
              >
                <Ionicons name="checkmark" size={20} color="white" />
              </TouchableOpacity>
              <TouchableOpacity 
                onPress={() => setEditingName(null)} 
                className="ml-1 p-2 bg-white/20 rounded-full"
              >
                <Ionicons name="close" size={20} color="white" />
              </TouchableOpacity>
            </View>
          ) : (
            <TouchableOpacity 
              onPress={() => setEditingName(details.envelope.name)}
              disabled={details.envelope.pending}
              className="flex-row items-center flex-1"
            >
              <Text className="text-white text-xl font-bold flex-shrink mr-2" numberOfLines={2}>
                {details.envelope.name}
              </Text>
              <Ionicons name="create-outline" size={18} color="rgba(255, 255, 255, 0.8)" />
            </TouchableOpacity>
          )}
          subtitle={`${formatCurrency(Number(details.envelope.currentAmount), details.envelope.currency)} • ${Math.round(progress * 100)}% ${t('envelopes.complete')}`}
          showBackButton
          headerHeight={getHeaderHeight()}
          rightComponent={
            <View className="flex-row gap-2">
              <TouchableOpacity
                onPress={() => setChangeCurrencyModalOpen(true)}
                className="flex-row items-center px-3 py-2 bg-blue-100 rounded-xl"
                accessibilityRole="button"
                accessibilityLabel={t('envelopes.changeCurrency')}
                activeOpacity={0.8}
                disabled={!!details.envelope.pending}
              >
                <Ionicons name="cash-outline" size={18} color="#0284c7" style={{ marginRight: 6 }} />
                <Text className="text-blue-700 font-semibold">{t('envelopes.changeCurrency')}</Text>
              </TouchableOpacity>
              <TouchableOpacity
                onPress={() => setDeleteModalOpen(true)}
                className="p-2 bg-white/20 rounded-xl"
                disabled={!!details.envelope.pending}
              >
                <Ionicons name="trash-outline" size={18} color="white" />
              </TouchableOpacity>
            </View>
          }
        >
          <StatusBar style="light" />
          <ScrollView
            refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
            className="flex-1"
            contentContainerStyle={{ paddingBottom: 120 }}
          >
            {/* Enhanced Progress Card */}
            <View className="bg-white rounded-2xl shadow-sm mx-4 mb-6 overflow-hidden">
              {/* Progress Header */}
              <View className="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white">
                <View className="flex-row items-center justify-between mb-4">
                  <Text className="text-white text-lg font-bold">{t('envelopes.progress')}</Text>
                  <View className="bg-white/20 px-3 py-1 rounded-full">
                    <Text className="text-white font-bold text-sm">{Math.round(progress * 100)}%</Text>
                  </View>
                </View>
                
                {/* Progress Bar */}
                <View className="mb-4">
                  <View className="h-3 bg-white/20 rounded-full overflow-hidden">
                    <View 
                      className="h-full bg-white rounded-full" 
                      style={{ width: `${Math.min(progress * 100, 100)}%` }}
                    />
                  </View>
                  <View className="flex-row justify-between mt-2">
                    <Text className="text-white/90 text-sm font-medium">
                      {formatCurrency(Number(details.envelope.currentAmount), details.envelope.currency)}
                    </Text>
                    <Text className="text-white/90 text-sm font-medium">
                      {formatCurrency(Number(details.envelope.targetedAmount), details.envelope.currency)}
                    </Text>
                  </View>
                </View>
                
                {/* Target Edit */}
                <View className="flex-row items-center justify-between">
                  <Text className="text-white/90 text-sm font-medium">{t('envelopes.target')}</Text>
                  {editingTarget !== null ? (
                    <View className="flex-row items-center space-x-2">
                      <TextInput
                        value={editingTarget}
                        onChangeText={v => setEditingTarget(normalizeAmountInput(v))}
                        className="bg-white/20 px-3 py-1 rounded-lg text-white font-semibold text-sm w-24"
                        keyboardType="numeric"
                        maxLength={13}
                        autoFocus
                        editable={!details.envelope.pending}
                      />
                      <TouchableOpacity onPress={handleUpdateTarget} className="p-1 bg-white/20 rounded-full">
                        <Ionicons name="checkmark" size={16} color="white" />
                      </TouchableOpacity>
                      <TouchableOpacity onPress={() => setEditingTarget(null)} className="p-1 bg-white/20 rounded-full">
                        <Ionicons name="close" size={16} color="white" />
                      </TouchableOpacity>
                    </View>
                  ) : (
                    <TouchableOpacity 
                      onPress={() => setEditingTarget(String(details.envelope.targetedAmount))} 
                      disabled={details.envelope.pending}
                      className="flex-row items-center space-x-1"
                    >
                      <Text className="text-white font-bold">
                        {formatCurrency(Number(details.envelope.targetedAmount), details.envelope.currency)}
                      </Text>
                      <Ionicons name="create-outline" size={14} color="rgba(255, 255, 255, 0.8)" />
                    </TouchableOpacity>
                  )}
                </View>
                {editingTargetError && (
                  <Text className="text-red-200 text-xs mt-1">{editingTargetError}</Text>
                )}
              </View>
              
              {/* Actions Section */}
              <View className="p-6">
                <Text className="text-slate-900 text-lg font-bold mb-4">{t('envelopes.actions')}</Text>
                
                {/* Custom Amount */}
                <View className="mb-6">
                  <Text className="text-slate-700 text-sm font-medium mb-2">{t('envelopes.customAmount')}</Text>
                  <View className="flex-row space-x-3">
                    <View className="flex-1">
                      <TextInput
                        value={amount}
                        onChangeText={v => {
                          const normalized = normalizeAmountInput(v);
                          const filtered = normalized.replace(/[^0-9.]/g, '');
                          setAmount(filtered);
                          setAmountTouched(true);
                        }}
                        onFocus={() => setAmountFocused(true)}
                        onBlur={() => setAmountFocused(false)}
                        placeholder={`${t('envelopes.enterAmount')} (${details.envelope.currency})`}
                        className="px-4 py-3 border border-slate-200 rounded-xl text-base bg-white focus:border-blue-500"
                        keyboardType="decimal-pad"
                        maxLength={13}
                        editable={!details.envelope.pending}
                      />
                      {amountTouched && amountError && (
                        <Text className="text-red-500 text-xs mt-1">{amountError}</Text>
                      )}
                    </View>
                  </View>
                  
                  <View className="flex-row justify-end space-x-3 mt-3">
                    <TouchableOpacity
                      onPress={() => {
                        const error = validateEnvelopeAmountField(amount, t);
                        setAmountError(error);
                        setAmountTouched(true);
                        if (error || !amount.trim()) return;
                        const current = Number(details.envelope.currentAmount);
                        const target = Number(details.envelope.targetedAmount);
                        const entered = parseFloat(amount);
                        if (entered + current > target) {
                          setError(t('envelopes.cannotCreditMoreThanTarget'));
                          return;
                        }
                        handleCredit();
                      }}
                      disabled={!amount || !!details.envelope.pending || !!amountError}
                      className="px-3 py-2 bg-success-100 rounded-lg"
                    >
                      <Text className="text-success-700 font-medium text-sm">{t('envelopes.addFunds')}</Text>
                    </TouchableOpacity>
                    <TouchableOpacity
                      onPress={() => {
                        const error = validateEnvelopeAmountField(amount, t);
                        setAmountError(error);
                        setAmountTouched(true);
                        if (error || !amount.trim()) return;
                        const current = Number(details.envelope.currentAmount);
                        const entered = parseFloat(amount);
                        if (entered > current) {
                          setError(t('envelopes.cannotDebitMoreThanBalance'));
                          return;
                        }
                        handleDebit();
                      }}
                      disabled={!amount || !!details.envelope.pending || !!amountError}
                      className="px-3 py-2 bg-danger-100 rounded-lg"
                    >
                      <Text className="text-danger-700 font-medium text-sm">{t('envelopes.withdraw')}</Text>
                    </TouchableOpacity>
                  </View>
                </View>
                
                {/* Quick Actions */}
                <View>
                  <Text className="text-slate-700 text-sm font-medium mb-3">{t('envelopes.quickActions')}</Text>
                  <View className="space-y-3">
                    {[10, 25, 50, 100].map((quickAmount, index) => (
                      <View key={quickAmount} className="flex-row space-x-3">
                        <TouchableOpacity
                          onPress={() => handleQuickCredit(String(quickAmount))}
                          disabled={!!details.envelope.pending}
                          className="flex-1 bg-green-100 py-3 rounded-xl flex-row items-center justify-center"
                          style={{ opacity: details.envelope.pending ? 0.5 : 1 }}
                        >
                          <Ionicons name="add" size={16} color="#16a34a" />
                          <Text className="text-green-800 font-semibold ml-1 text-sm">
                            {quickAmount} {details.envelope.currency}
                          </Text>
                        </TouchableOpacity>
                        <TouchableOpacity
                          onPress={() => handleQuickDebit(String(quickAmount))}
                          disabled={!!details.envelope.pending}
                          className="flex-1 bg-red-100 py-3 rounded-xl flex-row items-center justify-center"
                          style={{ opacity: details.envelope.pending ? 0.5 : 1 }}
                        >
                          <Ionicons name="remove" size={16} color="#dc2626" />
                          <Text className="text-red-800 font-semibold ml-1 text-sm">
                            {quickAmount} {details.envelope.currency}
                          </Text>
                        </TouchableOpacity>
                      </View>
                    ))}
                  </View>
                </View>
                
                {details.envelope.pending && (
                  <View className="mt-4 p-3 bg-blue-50 rounded-xl flex-row items-center justify-center">
                    <ActivityIndicator size="small" color="#3b82f6" />
                    <Text className="text-blue-800 text-sm font-medium ml-2">{t('common.processing')}</Text>
                  </View>
                )}
              </View>
            </View>

            {/* Transaction History */}
            <View className="mx-4">
              <EnvelopeTransactionHistoryCard
                ledger={details.ledger}
                currency={details.envelope.currency}
                loading={loading}
              />
            </View>
          </ScrollView>
        </AnimatedHeaderLayout>
        
        {/* Modals */}
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
        <ChangeCurrencyModal
          visible={changeCurrencyModalOpen}
          onClose={() => setChangeCurrencyModalOpen(false)}
          onSubmit={handleChangeCurrency}
          currentCurrency={details.envelope.currency}
          itemName={details.envelope.name}
          loading={loading}
        />
      </View>
    </SwipeBackWrapper>
  );
}

