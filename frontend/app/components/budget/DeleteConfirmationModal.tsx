"use client"

import { motion } from "framer-motion"
import { useTranslation } from "../../hooks/useTranslation"
import { X, AlertTriangle } from "lucide-react"

interface DeleteConfirmationModalProps {
    isOpen: boolean
    onClose: () => void
    onConfirm: () => void
    itemName: string
}

export default function DeleteConfirmationModal({
    isOpen,
    onClose,
    onConfirm,
    itemName,
}: DeleteConfirmationModalProps) {
    const { t } = useTranslation()

    if (!isOpen) return null

    return (
        <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        >
            <motion.div
                initial={{ scale: 0.9, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                exit={{ scale: 0.9, opacity: 0 }}
                className="neomorphic p-6 w-full max-w-md bg-white rounded-lg"
            >
                <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center">
                        <AlertTriangle className="h-6 w-6 text-red-500" />
                        <h2 className="text-2xl font-bold ml-2">{t("budgetTracker.deleteItem")}</h2>
                    </div>
                    <button
                        onClick={onClose}
                        className="p-2 neomorphic-button rounded-full text-gray-500 hover:text-gray-700 transition-colors"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>
                
                <div className="mb-8 neomorphic-inset p-4 bg-red-50 rounded-lg text-center">
                    <p className="text-lg">
                        {t("budgetTracker.confirmDelete")} <strong className="text-red-600">{itemName}</strong>?
                    </p>
                    <p className="text-sm text-muted-foreground mt-2">
                        {t("budgetTracker.deleteWarning")}
                    </p>
                </div>

                <div className="flex justify-between">
                    <button
                        onClick={onClose}
                        className="px-6 py-3 text-base font-semibold text-gray-700 bg-white border-2 border-gray-300 rounded-full neomorphic-button hover:bg-gray-100 transition-colors"
                    >
                        {t("budgetTracker.cancel")}
                    </button>
                    <button
                        onClick={onConfirm}
                        className="px-6 py-3 text-base font-semibold text-white bg-red-600 rounded-full neomorphic-button hover:bg-red-700 transition-colors"
                    >
                        {t("budgetTracker.delete")}
                    </button>
                </div>
            </motion.div>
        </motion.div>
    )
}
