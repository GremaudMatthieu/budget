import { useState, useCallback, useRef, useEffect } from 'react';
import { useEnvelopes } from '@/contexts/EnvelopeContext';
import { useErrorContext } from '@/contexts/ErrorContext';
import { useTranslation } from '@/utils/useTranslation';
import { validateEnvelopeAmountField } from '@/utils/validateEnvelopeAmount';

/**
 * Custom hook for managing envelope detail data and UI state.
 */
export function useEnvelopeData(uuid: string) {
  const { t } = useTranslation();
  const { setError } = useErrorContext();
  const {
    fetchEnvelopeDetails,
    deleteEnvelope,
    creditEnvelope,
    debitEnvelope,
    updateEnvelopeName,
    updateTargetBudget,
    currentEnvelopeDetails,
  } = useEnvelopes();

  const [details, setDetails] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [editingName, setEditingName] = useState<string | null>(null);
  const [editingTarget, setEditingTarget] = useState<string | null>(null);
  const [amount, setAmount] = useState('');
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [descriptionModalOpen, setDescriptionModalOpen] = useState(false);
  const [description, setDescription] = useState('');
  const [currentAction, setCurrentAction] = useState<{ type: 'credit' | 'debit'; amount: string } | null>(null);
  const [editingTargetError, setEditingTargetError] = useState<string | null>(null);
  const isMounted = useRef(true);

  const loadEnvelopeDetails = useCallback(async () => {
    if (!uuid || !isMounted.current) return;
    try {
      if (!refreshing) setLoading(true);
      const response = await fetchEnvelopeDetails(uuid);
      if (isMounted.current && response) setDetails(response);
    } catch (err) {
      if (isMounted.current) setError(t('errors.loadEnvelopeDetails'));
    } finally {
      if (isMounted.current) setLoading(false);
    }
  }, [uuid, fetchEnvelopeDetails, setError, refreshing, t]);

  useEffect(() => {
    isMounted.current = true;
    loadEnvelopeDetails();
    return () => { isMounted.current = false; };
  }, [loadEnvelopeDetails]);

  useEffect(() => {
    if (!currentEnvelopeDetails || !uuid) return;
    if (currentEnvelopeDetails.envelope.uuid === uuid) {
      setDetails(currentEnvelopeDetails);
      setLoading(false);
    }
  }, [currentEnvelopeDetails, uuid]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await loadEnvelopeDetails();
    setRefreshing(false);
  }, [loadEnvelopeDetails]);

  // Handler to update envelope name
  const handleUpdateName = useCallback(async () => {
    if (!details || editingName === null) return;
    const newName = editingName.trim();
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
      await updateEnvelopeName(details.envelope.uuid, newName, setError);
      setEditingName(null);
      await loadEnvelopeDetails();
    } catch (err) {
      setError(t('envelopes.failedToUpdateName'));
    }
  }, [details, editingName, updateEnvelopeName, setError, t, loadEnvelopeDetails]);

  // Validate target amount on change
  useEffect(() => {
    if (editingTarget !== null) {
      setEditingTargetError(validateEnvelopeAmountField(editingTarget, t));
    } else {
      setEditingTargetError(null);
    }
  }, [editingTarget, t]);

  // Handler to update target budget
  const handleUpdateTarget = useCallback(async () => {
    if (!details || editingTarget === null) return;
    const newTarget = editingTarget.trim();
    const validationError = validateEnvelopeAmountField(newTarget, t);
    if (validationError) {
      setEditingTargetError(validationError);
      setError(validationError);
      return;
    }
    if (Number(newTarget) === Number(details.envelope.targetedAmount)) {
      setEditingTarget(null);
      return;
    }
    if (Number(newTarget) < Number(details.envelope.currentAmount)) {
      setError(t('envelopes.targetLessThanCurrent'));
      setEditingTargetError(t('envelopes.targetLessThanCurrent'));
      return;
    }
    try {
      await updateTargetBudget(details.envelope.uuid, newTarget, details.envelope.currentAmount, setError);
      setEditingTarget(null);
      setEditingTargetError(null);
      await loadEnvelopeDetails();
    } catch (err) {
      setError(t('envelopes.failedToUpdateTarget'));
      setEditingTargetError(t('envelopes.failedToUpdateTarget'));
    }
  }, [details, editingTarget, updateTargetBudget, setError, t, loadEnvelopeDetails]);

  // Handler to open the description modal for credit/debit
  const openDescriptionModal = (type: 'credit' | 'debit', amt: string) => {
    setCurrentAction({ type, amount: amt });
    setDescriptionModalOpen(true);
  };

  // Handler for quick credit
  const handleQuickCredit = (amt: string) => {
    if (!details) return;
    const entered = parseFloat(amt);
    if (isNaN(entered) || entered <= 0) {
      setError(t('errors.invalidAmount'));
      return;
    }
    if (entered + Number(details.envelope.currentAmount) > Number(details.envelope.targetedAmount)) {
      setError(t('envelopes.cannotCreditMoreThanTarget'));
      return;
    }
    openDescriptionModal('credit', amt);
  };
  // Handler for quick debit
  const handleQuickDebit = (amt: string) => {
    if (!details) return;
    const entered = parseFloat(amt);
    if (isNaN(entered) || entered <= 0) {
      setError(t('errors.invalidAmount'));
      return;
    }
    if (entered > Number(details.envelope.currentAmount)) {
      setError(t('envelopes.cannotDebitMoreThanBalance'));
      return;
    }
    openDescriptionModal('debit', amt);
  };
  // Handler for custom credit
  const handleCredit = () => openDescriptionModal('credit', amount);
  // Handler for custom debit
  const handleDebit = () => openDescriptionModal('debit', amount);

  // Handler for submitting the description modal
  const handleDescriptionSubmit = async (desc: string) => {
    if (!details || !currentAction) return;
    const { type, amount: amt } = currentAction;
    if (!amt || isNaN(Number(amt)) || Number(amt) <= 0) {
      setError(t('errors.invalidAmount'));
      return;
    }
    if (type === 'debit' && Number(amt) > Number(details.envelope.currentAmount)) {
      setError(t('errors.cannotDebitMoreThanBalance'));
      return;
    }
    try {
      if (type === 'credit') {
        await creditEnvelope(details.envelope.uuid, amt, desc, setError);
      } else {
        await debitEnvelope(details.envelope.uuid, amt, desc, setError);
      }
      setAmount('');
      setDescription('');
      setCurrentAction(null);
      setDescriptionModalOpen(false);
      await loadEnvelopeDetails();
    } catch (err) {
      setError(type === 'credit' ? t('envelopes.failedToCredit') : t('envelopes.failedToDebit'));
    }
  };

  return {
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
    setEditingTargetError,
    deleteEnvelope,
  };
} 