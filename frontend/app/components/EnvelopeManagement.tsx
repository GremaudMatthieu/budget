"use client"

import { useState, useEffect, useCallback, useMemo } from "react"
import { useEnvelopes } from "../domain/envelope/envelopeHooks"
import { PlusCircle, Trash2, Edit2, Loader2, Check, X } from "lucide-react"
import { PieChart, Pie, Cell, ResponsiveContainer, Label } from "recharts"
import { motion, AnimatePresence } from "framer-motion"
import { DeleteConfirmationModal } from "./DeleteConfirmationModal"
import { useTranslation } from "../hooks/useTranslation"
import { useError } from "../contexts/ErrorContext"
import { useValidMessage } from "../contexts/ValidContext"
import Link from "next/link"
import { DescriptionModal } from "./DescriptionModal"
import type React from "react"
import InputNumber from "./inputs/inputNumber"
import ActionButton from "./buttons/actionButton"
import InputNameEnvelope from "./inputs/envelopeInput/inputNameEnvelope"
import InputText from "./inputs/envelopeInput/textInput"
import ValidInputButton from "./buttons/validInputButton"
import EnvelopeCard from "./card/EnvelopeCard"
import DeletButton from "./buttons/deletButton"
import cancelEditing from "../utils/form/CancelEditing"
import isInvalidInput from "../utils/validation/IsInvalidValidInput"
import { handleDebitEnvelope } from "../services/envelopeService/debitEnvelope"
import { handleCreditEnvelope } from "../services/envelopeService/creditEnvelope"
import handleNameChange from "../utils/envelope/changeName"
import handleStartEditingName from "../utils/form/startEditing"
import formatAmount from "../utils/envelope/formatAmount"
import handleDeleteEntity from "../utils/envelope/deleteUtils"
import handleUpdateEnvelopeName from "../function/EnvelopeFunction/handleUpdateEnvelopeName"
import handleAmountChange from "../utils/envelope/handleAmountChange"
import { formatCurrency } from "../utils/envelope/currencyUtils"
import AnimatedCard from "./card/AnimatedCard"
import { currencyOptions } from "../constants/currencyOption"
import CustomSelect from "./inputs/customSelect"

export default function EnvelopeManagement() {
    const {
        envelopesData,
        createEnvelope,
        creditEnvelope,
        debitEnvelope,
        deleteEnvelope,
        updateEnvelopeName,
    } = useEnvelopes()
    const [amounts, setAmounts] = useState<{ [key: string]: string }>({})
    const [isCreating, setIsCreating] = useState(false)
    const [newEnvelopeName, setNewEnvelopeName] = useState("")
    const [newEnvelopeTarget, setNewEnvelopeTarget] = useState("")
    const [pendingActions, setPendingActions] = useState<{ [key: string]: string }>({})
    const [deleteModalOpen, setDeleteModalOpen] = useState(false)
    const [envelopeToDelete, setEnvelopeToDelete] = useState<{ id: string; name: string } | null>(null)
    const [editingName, setEditingName] = useState<{ id: string; name: string } | null>(null)
    const [descriptionModalOpen, setDescriptionModalOpen] = useState(false)
    const [currentAction, setCurrentAction] = useState<{ type: "credit" | "debit"; id: string; amount: string } | null>(
        null,
    )
    const { t } = useTranslation()
    const { setError } = useError()
    const { setValidMessage } = useValidMessage()
    const [isEmptyEnvelopes, setIsEmptyEnvelopes] = useState(true)
    const [currency, setCurrency] = useState("USD");
    const handleCurrencyChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        setCurrency(e.target.value);
    };
    const handleDescriptionSubmit = async (description: string) => {
        if (currentAction) {
            const { type, id, amount } = currentAction
            try {
                if (type === "credit") {
                    await creditEnvelope(id, amount, description, setError)
                } else {
                    await debitEnvelope(id, amount, description, setError)
                }
                handleAmountChange(id, "", false, setNewEnvelopeTarget, setAmounts)
            } catch (err) {
                console.error("Error in handleDescriptionSubmit:", err)
            }
        }
        setDescriptionModalOpen(false)
        setCurrentAction(null)
    }

    const handleCreateEnvelope = async () => {
        if (newEnvelopeName && newEnvelopeTarget && !isInvalidInput(newEnvelopeTarget)) {
            if (newEnvelopeName.length <= 25) {
                const formattedTarget = formatAmount(newEnvelopeTarget)
                await createEnvelope(newEnvelopeName, formattedTarget, currency)
                setIsCreating(false)
                setNewEnvelopeName("")
                setNewEnvelopeTarget("")
            } else {
                setError('envelopes.validationError.createNewEnvelopeTooLong')
                setNewEnvelopeName("")
            }
        }
    }

    const handleDeleteEnvelope = async () => {
        await handleDeleteEntity({
            entityToDelete: envelopeToDelete,
            deleteFunction: deleteEnvelope,
            setDeleteModalOpen,
            setError,
            setEntityToDelete: setEnvelopeToDelete
        });
    }

    const openDeleteModal = (id: string, name: string, e: any) => {
        e.preventDefault()
        setEnvelopeToDelete({ id, name })
        setDeleteModalOpen(true)
    }

    useEffect(() => {
        if (envelopesData?.envelopes.length === 0) {
            setIsEmptyEnvelopes(false)
        } else setIsEmptyEnvelopes(true)
    }, [envelopesData])
    const renderedEnvelopes = useMemo(() => {
        return envelopesData?.envelopes.map((envelope) => (
            <Link key={envelope.uuid} href={`/envelopes/${envelope.uuid}`} className="block">
                <AnimatedCard
                    pending={envelope.pending}
                    deleted={envelope.deleted}
                    className="my-custom-class"
                    onClick={() => console.log("Card clicked!")}
                    preventClickOnSelectors="button, input"
                >
                    <EnvelopeCard>
                        {editingName && editingName.id === envelope.uuid ? (
                            <div className="flex items-center flex-grow">
                                <InputNameEnvelope
                                    value={editingName.name}
                                    onChange={(value) => handleNameChange(value, editingName, setEditingName)}
                                    autoFocus
                                    className="custom-input-class"
                                />
                                <ValidInputButton
                                    onClick={(e) => handleUpdateEnvelopeName({
                                        e,
                                        editingName,
                                        name: editingName.name,
                                        envelopesData,
                                        updateEnvelopeName,
                                        setError,
                                        setPendingActions,
                                        setEditingName
                                    })}
                                    icon={<Check className="h-4 w-4 md:h-5 md:w-5" />}
                                    className=" text-green-500 mr-1 "
                                    disabled={envelope.pending || !!pendingActions[envelope.uuid]}
                                    text=""
                                />

                                {/* Utilisation pour le bouton de l'annulation */}
                                <ValidInputButton
                                    onClick={(e) => {
                                        setEditingName((prev) => ({ ...prev, name: envelope.name }));
                                        cancelEditing({ e, setEditing: setEditingName });
                                    }}
                                    icon={<X className="h-4 w-4 md:h-5 md:w-5" />}
                                    className="text-red-500"
                                    disabled={envelope.pending || !!pendingActions[envelope.uuid]}
                                    text=""
                                />
                            </div>
                        ) : (
                            <>
                                <InputNameEnvelope
                                    value={envelope.name}
                                    onChange={() => handleStartEditingName(envelope.uuid, envelope.name, setEditingName)}
                                    onFocus={() => handleStartEditingName(envelope.uuid, envelope.name, setEditingName)}
                                    className="custom-input-class cursor-pointer"
                                />
                            </>
                        )}

                        <DeletButton
                            onClick={(e) => openDeleteModal(envelope.uuid, envelope.name, e)}
                            icon={<Trash2 className="h-4 w-4 md:h-5 md:w-5" />
                            }
                            className={''}
                            disabled={envelope.pending || !!pendingActions[envelope.uuid]}
                        />

                    </EnvelopeCard>
                    <div className="flex items-center">
                        {envelope.pending && <Loader2 className="ml-2 h-4 w-4 animate-spin" />}
                    </div>
                    <div className="flex justify-between items-center mb-4">
                        <div>
                            <p className="text-lg md:text-xl font-semibold">
                                {formatCurrency(envelope.currentAmount, envelope.currency)}
                            </p>
                            <p className="text-xs md:text-sm text-muted-foreground">
                                {t("envelopes.of")} {formatCurrency(envelope.targetedAmount, envelope.currency)}
                            </p>
                        </div>
                        <div className="w-16 h-16 md:w-20 md:h-20 neomorphic-circle flex items-center justify-center">
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={[
                                            { name: "Used", value: Number.parseFloat(envelope.currentAmount) },
                                            {
                                                name: "Remaining",
                                                value: Math.max(0, Number.parseFloat(envelope.targetedAmount) - Number.parseFloat(envelope.currentAmount)),
                                            },
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

                                        {/* ✅ Ajout du pourcentage au centre */}
                                        <Label
                                            value={`${((Number.parseFloat(envelope.currentAmount) / Number.parseFloat(envelope.targetedAmount)) * 100).toFixed(0)}%`}
                                            position="center"
                                            fontSize={12}
                                            fill="#333"
                                            fontWeight="bold"
                                        />
                                    </Pie>
                                </PieChart>
                            </ResponsiveContainer>

                        </div>
                    </div>
                    <div className="space-y-4">
                        <div className="flex flex-col space-y-2">
                            <div className="flex items-center space-x-2">
                                <InputNumber
                                    value={amounts[envelope.uuid] || ""}
                                    onChange={(value) => handleAmountChange(envelope.uuid, value, false, setNewEnvelopeTarget, setAmounts)}
                                    placeholder={t("envelopes.amount")}
                                    disabled={envelope.pending || !!pendingActions[envelope.uuid]}
                                />
                                <ActionButton
                                    onClick={() => handleCreditEnvelope(
                                        envelope.uuid,
                                        envelope.currentAmount,
                                        envelope.targetedAmount,
                                        amounts,
                                        setCurrentAction,
                                        setDescriptionModalOpen,
                                        setError,
                                        t
                                    )}
                                    label={t("envelopes.credit")}
                                    disabled={
                                        envelope.pending ||
                                        !!pendingActions[envelope.uuid] ||
                                        isInvalidInput(amounts[envelope.uuid] || "") ||
                                        !amounts[envelope.uuid]
                                    }
                                    className="text-green-500"
                                />

                                <ActionButton
                                    onClick={() => handleDebitEnvelope(
                                        envelope.uuid,
                                        envelope.currentAmount,
                                        amounts,
                                        setCurrentAction,
                                        setDescriptionModalOpen,
                                        setError,
                                        t
                                    )}
                                    label={t("envelopes.debit")}
                                    disabled={
                                        envelope.pending ||
                                        !!pendingActions[envelope.uuid] ||
                                        isInvalidInput(amounts[envelope.uuid] || "") ||
                                        !amounts[envelope.uuid]
                                    }
                                    className="text-red-500"
                                />

                            </div>
                        </div>

                    </div>
                    {envelope.deleted && <p className="text-red-500 mt-2">Deleting...</p>}
                </AnimatedCard>
            </Link >
        ))
    }, [
        envelopesData,
        amounts,
        editingName,
        pendingActions,
        handleAmountChange,
        handleDebitEnvelope,
        handleUpdateEnvelopeName,
        openDeleteModal,
    ])

    return (
        <div className="space-y-8">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl md:text-3xl font-bold">{t("envelopes.title")}</h1>
                <button
                    onClick={() => setIsCreating(true)}
                    className="p-3 neomorphic-button text-primary hover:text-primary-dark transition-colors rounded-full"
                    aria-label={t("envelopes.createNew")}
                >
                    <PlusCircle className="h-6 w-6" />
                </button>
            </div>

            {isEmptyEnvelopes === false ? (
                <div className="text-center py-12">
                    <p className="text-lg md:text-xl mb-6">{t("envelopes.empty")}</p>
                </div>
            ) : (
                <AnimatePresence initial={false}>
                    <motion.div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">{renderedEnvelopes}</motion.div>
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
                        <h2 className="text-xl md:text-2xl font-bold mb-4">{t("envelopes.createNewEnvelope")}</h2>
                        <InputText
                            value={newEnvelopeName}
                            onChange={setNewEnvelopeName}
                            placeholder={t("envelopes.envelopeName")}
                            className="custom-class"
                        />
                        <div className="mb-4">
                            <InputNumber
                                value={newEnvelopeTarget}
                                onChange={(value) => handleAmountChange("new", value, true, setNewEnvelopeTarget, setAmounts)}
                                placeholder={t("envelopes.targetedAmount")}
                                className="w-full p-2 md:p-3 neomorphic-input"
                            />
                        </div>
                        <div>
                            <CustomSelect options={currencyOptions}
                                onChange={handleCurrencyChange}
                                value={currency} className={''}
                                t={t}
                            />
                        </div>
                        <div className="flex justify-between">
                            <ActionButton
                                onClick={handleCreateEnvelope}
                                label={t("envelopes.create")}
                                disabled={!newEnvelopeName || isInvalidInput(newEnvelopeTarget) || !newEnvelopeTarget}
                                className="text-green-500"
                            />
                            <ActionButton
                                onClick={() => setIsCreating(false)}
                                label={t("envelopes.cancel")}
                                className="text-red-500"
                            />
                        </div>
                    </motion.div>
                </motion.div>
            )}
            <DeleteConfirmationModal
                isOpen={deleteModalOpen}
                onClose={() => setDeleteModalOpen(false)}
                onConfirm={handleDeleteEnvelope}
                envelopeName={envelopeToDelete?.name || ""}
            />
            <DescriptionModal
                isOpen={descriptionModalOpen}
                onClose={() => setDescriptionModalOpen(false)}
                onSubmit={handleDescriptionSubmit}
                actionType={currentAction?.type || "credit"}
            />
        </div>
    )
}
