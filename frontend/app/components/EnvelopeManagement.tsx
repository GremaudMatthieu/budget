'use client'

import { useState, useEffect } from 'react'
import { useEnvelopes } from '../domain/envelope/envelopeHooks'
import { PlusCircle, Trash2, Edit2, Loader2, Check, X } from 'lucide-react'
import { PieChart, Pie, Cell, ResponsiveContainer } from 'recharts'
import { motion, AnimatePresence } from 'framer-motion'
import { DeleteConfirmationModal } from './DeleteConfirmationModal'
import { useTranslation } from '../hooks/useTranslation'

export default function EnvelopeManagement() {
    const { envelopesData, createEnvelope, creditEnvelope, debitEnvelope, deleteEnvelope, updateEnvelopeName, loading, errorEnvelope } = useEnvelopes()
    const [amounts, setAmounts] = useState<{ [key: string]: string }>({})
    const [isCreating, setIsCreating] = useState(false)
    const [newEnvelopeName, setNewEnvelopeName] = useState('')
    const [newEnvelopeTarget, setNewEnvelopeTarget] = useState('')
    const [pendingActions, setPendingActions] = useState<{ [key: string]: string }>({})
    const [deleteModalOpen, setDeleteModalOpen] = useState(false)
    const [envelopeToDelete, setEnvelopeToDelete] = useState<{ id: string, name: string } | null>(null)
    const [editingName, setEditingName] = useState<{ id: string, name: string } | null>(null)
    const [errorMessage, setErrorMessage] = useState<string | null>(null)
    const { t } = useTranslation()

    const handleAmountChange = (id: string, value: string, isNewEnvelope: boolean = false) => {
        // Remove any non-digit and non-dot characters
        value = value.replace(/[^\d.]/g, '');

        // Handle cases where the decimal point might be the first character
        if (value.startsWith('.')) {
            value = '0' + value;
        }

        // Ensure only one decimal point
        const parts = value.split('.');
        if (parts.length > 2) {
            parts.pop();
            value = parts.join('.');
        }

        // Enforce character limits
        if (value.includes('.')) {
            // With decimal: limit to 13 characters (10 before decimal, 1 decimal point, 2 after decimal)
            const [integerPart, decimalPart] = value.split('.');
            value = `${integerPart.slice(0, 10)}.${decimalPart.slice(0, 2)}`;
        } else {
            // Without decimal: limit to 10 characters
            value = value.slice(0, 10);
        }

        // Remove leading zeros, except if it's "0." or "0"
        if (value.length > 1 && value.startsWith('0') && !value.startsWith('0.')) {
            value = value.replace(/^0+/, '');
        }

        if (isNewEnvelope) {
            setNewEnvelopeTarget(value);
        } else {
            setAmounts(prev => ({ ...prev, [id]: value }));
        }
    };

    const formatAmount = (amount: string): string => {
        if (!amount) return '';
        let [integerPart, decimalPart] = amount.split('.');
        integerPart = integerPart || '0';
        decimalPart = decimalPart || '00';
        decimalPart = decimalPart.padEnd(2, '0').slice(0, 2);
        return `${integerPart}.${decimalPart}`;
    };

    const handleCreditEnvelope = (id: string, currentBudget: string, targetBudget: string) => {
        if (amounts[id]) {
            const formattedAmount = formatAmount(amounts[id]);
            const maxCredit = (parseFloat(targetBudget) - parseFloat(currentBudget)).toFixed(2);
            if (validateAmount(formattedAmount, currentBudget, targetBudget, true)) {
                creditEnvelope(id, formattedAmount);
                handleAmountChange(id, '');
                setErrorMessage(null);
            } else {
                const errorMessage = t('envelopes.creditError').replace('${amount}', formattedAmount).replace('${max}', maxCredit);
                showErrorMessage(errorMessage);
            }
        }
    };

    const handleDebitEnvelope = (id: string, currentBudget: string) => {
        if (amounts[id]) {
            const formattedAmount = formatAmount(amounts[id]);
            const maxDebit = parseFloat(currentBudget).toFixed(2);
            if (validateAmount(formattedAmount, currentBudget, '0', false)) {
                debitEnvelope(id, formattedAmount);
                handleAmountChange(id, '');
                setErrorMessage(null);
            } else {
                const errorMessage = t('envelopes.debitError').replace('${amount}', formattedAmount).replace('${max}', maxDebit);
                showErrorMessage(errorMessage);
            }
        }
    };

    const handleCreateEnvelope = async () => {
        if (newEnvelopeName && newEnvelopeTarget && !isInvalidInput(newEnvelopeTarget)) {
            const formattedTarget = formatAmount(newEnvelopeTarget);
            await createEnvelope(newEnvelopeName, formattedTarget)
            setIsCreating(false)
            setNewEnvelopeName('')
            setNewEnvelopeTarget('')
        }
    }

    const handleDeleteEnvelope = async () => {
        if (envelopeToDelete) {
            const { id } = envelopeToDelete
            setDeleteModalOpen(false)
            await deleteEnvelope(id)
            setEnvelopeToDelete(null)
        }
    }

    const openDeleteModal = (id: string, name: string) => {
        setEnvelopeToDelete({ id, name })
        setDeleteModalOpen(true)
    }

    const handleStartEditingName = (id: string, currentName: string) => {
        setEditingName({ id, name: currentName })
    }

    const handleNameChange = (newName: string) => {
        if (editingName) {
            setEditingName({ ...editingName, name: newName })
        }
    }

    const handleUpdateEnvelopeName = async () => {
        if (editingName && editingName.name.trim() !== '') {
            const { id, name } = editingName
            setPendingActions(prev => ({ ...prev, [id]: 'updating' }))

            try {
                await updateEnvelopeName(id, name)
            } catch (error) {
                console.error('Failed to update envelope name:', error)
            } finally {
                setPendingActions(prev => {
                    const newPending = { ...prev }
                    delete newPending[id]
                    return newPending
                })
                setEditingName(null)
            }
        }
    }

    const cancelNameEdit = () => {
        setEditingName(null)
    }

    const isEmptyEnvelopes = !envelopesData || envelopesData.envelopes.length === 0

    const isInvalidInput = (value: string): boolean => {
        // Allow empty input
        if (value === '') return false;

        // Check if the input is a valid number or a partial decimal input
        return !/^\d{1,10}(\.\d{0,2})?$/.test(value);
    };

    const validateAmount = (amount: string, currentBudget: string, targetBudget: string, isCredit: boolean): boolean => {
        const amountFloat = parseFloat(amount)
        const currentBudgetFloat = parseFloat(currentBudget)
        const targetBudgetFloat = parseFloat(targetBudget)

        if (isCredit) {
            return currentBudgetFloat + amountFloat <= targetBudgetFloat
        } else {
            return currentBudgetFloat - amountFloat >= 0
        }
    }

    const showErrorMessage = (message: string) => {
        setErrorMessage(message);
        setTimeout(() => setErrorMessage(null), 3000);
    };

    const ErrorMessage = ({ message }: { message: string }) => {
        const [progress, setProgress] = useState(100);

        useEffect(() => {
            const timer = setInterval(() => {
                setProgress((oldProgress) => {
                    if (oldProgress <= 0) {
                        clearInterval(timer);
                        return 0;
                    }
                    return oldProgress - (100 / 30); // 100% to 0% in 3 seconds (30 steps of 100ms)
                });
            }, 100);

            return () => {
                clearInterval(timer);
            };
        }, []);

        return (
            <motion.div
                initial={{ opacity: 0, y: -50 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -50 }}
                className="fixed top-4 left-0 right-0 mx-auto z-50 p-4"
            >
                <div className="bg-red-500 text-white p-4 rounded-md shadow-lg max-w-md w-full mx-auto">
                    <div className="mb-2 text-center">{message}</div>
                    <div className="w-full bg-red-300 rounded-full h-1.5">
                        <div
                            className="bg-white h-1.5 rounded-full"
                            style={{ width: `${progress}%` }}
                        />
                    </div>
                </div>
            </motion.div>
        );
    };

    return (
        <div className="space-y-8">
            <AnimatePresence>
                {errorMessage && (
                    <ErrorMessage message={errorMessage} />
                )}
            </AnimatePresence>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl md:text-3xl font-bold">{t('envelopes.title')}</h1>
                <button
                    onClick={() => setIsCreating(true)}
                    className="p-3 neomorphic-button text-primary hover:text-primary-dark transition-colors rounded-full"
                    aria-label={t('envelopes.createNew')}
                >
                    <PlusCircle className="h-6 w-6" />
                </button>
            </div>

            {isEmptyEnvelopes ? (
                <div className="text-center py-12">
                    <p className="text-lg md:text-xl mb-6">{t('envelopes.empty')}</p>
                </div>
            ) : (
                <AnimatePresence initial={false}>
                    <motion.div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {envelopesData.envelopes.map(envelope => (
                            <motion.div
                                key={envelope.uuid}
                                layout
                                initial={{ opacity: 0, scale: 0.8 }}
                                animate={{ opacity: 1, scale: 1 }}
                                exit={{ opacity: 0, scale: 0.8 }}
                                transition={{ duration: 0.3 }}
                                className={`neomorphic p-3 md:p-4 ${envelope.pending ? 'opacity-70' : ''} ${envelope.deleted ? 'bg-red-100' : ''}`}
                            >
                                <div className="flex items-center justify-between mb-4">
                                    <div className="flex-grow">
                                        {editingName && editingName.id === envelope.uuid ? (
                                            <div className="flex items-center">
                                                <input
                                                    type="text"
                                                    value={editingName.name}
                                                    onChange={(e) => handleNameChange(e.target.value)}
                                                    className="flex-grow p-1 mr-2 neomorphic-input text-lg md:text-xl font-bold"
                                                    autoFocus
                                                />
                                                <button
                                                    onClick={handleUpdateEnvelopeName}
                                                    className="p-1 neomorphic-button text-green-500 mr-1"
                                                    disabled={envelope.pending || !!pendingActions[envelope.uuid]}
                                                >
                                                    <Check className="h-4 w-4 md:h-5 md:w-5" />
                                                </button>
                                                <button
                                                    onClick={cancelNameEdit}
                                                    className="p-1 neomorphic-button text-red-500"
                                                    disabled={envelope.pending || !!pendingActions[envelope.uuid]}
                                                >
                                                    <X className="h-4 w-4 md:h-5 md:w-5" />
                                                </button>
                                            </div>
                                        ) : (
                                            <h3
                                                className="text-base md:text-lg font-bold cursor-pointer"
                                                onClick={() => handleStartEditingName(envelope.uuid, envelope.name)}
                                            >
                                                {envelope.name}
                                            </h3>
                                        )}
                                    </div>
                                    <div className="flex items-center">
                                        {envelope.pending && <Loader2 className="ml-2 h-4 w-4 animate-spin" />}
                                    </div>
                                </div>
                                <div className="flex justify-between items-center mb-4">
                                    <div>
                                        <p className="text-lg md:text-xl font-semibold">
                                            ${parseFloat(envelope.currentBudget).toFixed(2)}
                                        </p>
                                        <p className="text-xs md:text-sm text-muted-foreground">
                                            {t('envelopes.of')} ${parseFloat(envelope.targetBudget).toFixed(2)}
                                        </p>
                                    </div>
                                    <div className="w-16 h-16 md:w-20 md:h-20 neomorphic-circle flex items-center justify-center">
                                        <ResponsiveContainer width="100%" height="100%">
                                            <PieChart>
                                                <Pie
                                                    data={[
                                                        { name: 'Used', value: parseFloat(envelope.currentBudget) },
                                                        { name: 'Remaining', value: parseFloat(envelope.targetBudget) - parseFloat(envelope.currentBudget) }
                                                    ]}
                                                    cx="50%"
                                                    cy="50%"
                                                    innerRadius={20}
                                                    outerRadius={30}
                                                    fill="#8884d8"
                                                    dataKey="value"
                                                    strokeWidth={0}
                                                >
                                                    <Cell key="cell-0" fill="#4CAF50" />
                                                    <Cell key="cell-1" fill="#E0E0E0" />
                                                </Pie>
                                            </PieChart>
                                        </ResponsiveContainer>
                                    </div>
                                </div>
                                <div className="space-y-4">
                                    <div className="flex flex-col space-y-2">
                                        <div className="flex items-center space-x-2">
                                            <input
                                                type="text"
                                                inputMode="decimal"
                                                value={amounts[envelope.uuid] || ''}
                                                onChange={(e) => handleAmountChange(envelope.uuid, e.target.value)}
                                                placeholder={t('envelopes.amount')}
                                                className="w-1/2 p-1 md:p-2 neomorphic-input text-sm md:text-base"
                                                disabled={envelope.pending || !!pendingActions[envelope.uuid]}
                                            />
                                            <button
                                                onClick={() => handleCreditEnvelope(envelope.uuid, envelope.currentBudget, envelope.targetBudget)}
                                                className="w-1/4 p-1 md:p-2 neomorphic-button text-green-500 text-xs md:text-sm font-semibold"
                                                disabled={envelope.pending || !!pendingActions[envelope.uuid] || isInvalidInput(amounts[envelope.uuid] || '') || !amounts[envelope.uuid]}
                                            >
                                                {t('envelopes.credit')}
                                            </button>
                                            <button
                                                onClick={() => handleDebitEnvelope(envelope.uuid, envelope.currentBudget)}
                                                className="w-1/4 p-1 md:p-2 neomorphic-button text-red-500 text-xs md:text-sm font-semibold"
                                                disabled={envelope.pending || !!pendingActions[envelope.uuid] || isInvalidInput(amounts[envelope.uuid] || '') || !amounts[envelope.uuid]}
                                            >
                                                {t('envelopes.debit')}
                                            </button>
                                        </div>
                                    </div>
                                    <div className="flex justify-end mt-4">
                                        <button
                                            onClick={() => openDeleteModal(envelope.uuid, envelope.name)}
                                            className="p-2 neomorphic-button text-red-500 hover:text-red-600"
                                            disabled={envelope.pending || !!pendingActions[envelope.uuid]}
                                        >
                                            <Trash2 className="h-4 w-4 md:h-5 md:w-5" />
                                        </button>
                                    </div>
                                </div>
                                {envelope.deleted && <p className="text-red-500 mt-2">Deleting...</p>}
                            </motion.div>
                        ))}
                    </motion.div>
                </AnimatePresence>
            )}
            {isCreating && (
                <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
                >
                    <motion.div
                        initial={{ scale: 0.8, opacity: 0 }}
                        animate={{ scale: 1, opacity: 1 }}
                        exit={{ scale: 0.8, opacity: 0 }}
                        className="neomorphic p-4 md:p-6 w-full max-w-md bg-white rounded-lg"
                    >
                        <h2 className="text-xl md:text-2xl font-bold mb-4">{t('envelopes.createNewEnvelope')}</h2>
                        <input
                            type="text"
                            value={newEnvelopeName}
                            onChange={(e) => setNewEnvelopeName(e.target.value)}
                            placeholder={t('envelopes.envelopeName')}
                            className="w-full p-2 md:p-3 mb-4 neomorphic-input"
                        />
                        <div className="mb-4">
                            <input
                                type="text"
                                inputMode="decimal"
                                value={newEnvelopeTarget}
                                onChange={(e) => handleAmountChange('new', e.target.value, true)}
                                placeholder={t('envelopes.targetBudget')}
                                className="w-full p-2 md:p-3 neomorphic-input"
                            />
                        </div>
                        <div className="flex justify-between">
                            <button
                                onClick={handleCreateEnvelope}
                                className="py-2 px-4 neomorphic-button text-green-500"
                                disabled={!newEnvelopeName || isInvalidInput(newEnvelopeTarget) || !newEnvelopeTarget}
                            >
                                {t('envelopes.create')}
                            </button>
                            <button onClick={() => setIsCreating(false)} className="py-2 px-4 neomorphic-button text-red-500">
                                {t('envelopes.cancel')}
                            </button>
                        </div>
                    </motion.div>
                </motion.div>
            )}
            <DeleteConfirmationModal
                isOpen={deleteModalOpen}
                onClose={() => setDeleteModalOpen(false)}
                onConfirm={handleDeleteEnvelope}
                envelopeName={envelopeToDelete?.name || ''}
            />
        </div>
    )
}
