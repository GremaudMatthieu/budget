"use client"

import { useState } from "react"
import { motion } from "framer-motion"
import { useTranslation } from "../../hooks/useTranslation"
import InputText from "../inputs/envelopeInput/textInput"
import InputNumber from "../inputs/inputNumber"
import { X, DollarSign, ShoppingBag, PiggyBank, Calculator, Tag } from "lucide-react"
import type { Category } from "../../domain/budget/budgetTypes"

interface BudgetItemModalProps {
    isOpen: boolean
    onClose: () => void
    onSubmit: (name: string, amount: string, category: string) => void
    title: string
    initialName?: string
    initialAmount?: string
    initialCategory?: string
    isEdit?: boolean
    itemType: "need" | "want" | "saving" | "income"
    categories: {
        needs: Category[]
        wants: Category[]
        savings: Category[]
        incomes: Category[]
    }
}

export default function BudgetItemModal({
    isOpen,
    onClose,
    onSubmit,
    title,
    initialName = "",
    initialAmount = "",
    initialCategory = "",
    isEdit = false,
    itemType,
    categories,
}: BudgetItemModalProps) {
    const { t } = useTranslation()
    const [name, setName] = useState(initialName)
    const [amount, setAmount] = useState(initialAmount)
    const [category, setCategory] = useState(initialCategory)

    const itemCategories =
        categories[
        itemType === "need" ? "needs" : itemType === "want" ? "wants" : itemType === "saving" ? "savings" : "incomes"
        ]

    const handleAmountChange = (value: string) => {
        // Remove any non-digit and non-dot characters
        value = value.replace(/[^\d.]/g, "")

        // Handle cases where the decimal point might be the first character
        if (value.startsWith(".")) {
            value = "0" + value
        }

        // Ensure only one decimal point
        const parts = value.split(".")
        if (parts.length > 2) {
            parts.pop()
            value = parts.join(".")
        }

        // Enforce character limits
        if (value.includes(".")) {
            // With decimal: limit to 13 characters (10 before decimal, 1 decimal point, 2 after decimal)
            const [integerPart, decimalPart] = value.split(".")
            value = `${integerPart.slice(0, 10)}.${decimalPart.slice(0, 2)}`
        } else {
            // Without decimal: limit to 10 characters
            value = value.slice(0, 10)
        }

        // Remove leading zeros, except if it's "0." or "0"
        if (value.length > 1 && value.startsWith("0") && !value.startsWith("0.")) {
            value = value.replace(/^0+/, "")
        }

        setAmount(value)
    }

    const handleSubmit = () => {
        // Format amount to have two decimal places
        let formattedAmount = amount
        if (formattedAmount.includes(".")) {
            const [integerPart, decimalPart] = formattedAmount.split(".")
            formattedAmount = `${integerPart}.${(decimalPart || "").padEnd(2, "0").slice(0, 2)}`
        } else {
            formattedAmount = `${formattedAmount}.00`
        }

        onSubmit(name, formattedAmount, category)
        onClose()
    }

    const isValid = name.trim() !== "" && amount.trim() !== "" && /^\d+(\.\d{0,2})?$/.test(amount) && category !== ""

    // Get icon based on item type
    const getIcon = () => {
        switch (itemType) {
            case "need": return <DollarSign className="h-6 w-6 text-green-600" />
            case "want": return <ShoppingBag className="h-6 w-6 text-blue-600" />
            case "saving": return <PiggyBank className="h-6 w-6 text-amber-600" />
            case "income": return <Calculator className="h-6 w-6 text-purple-600" />
        }
    }

    // Get color scheme based on item type
    const getColorScheme = () => {
        switch (itemType) {
            case "need": return "green"
            case "want": return "blue"
            case "saving": return "amber"
            case "income": return "purple"
        }
    }

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
                        {getIcon()}
                        <h2 className="text-2xl font-bold ml-2">{title}</h2>
                    </div>
                    <button
                        onClick={onClose}
                        className="p-2 neomorphic-button rounded-full text-gray-500 hover:text-gray-700 transition-colors"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                <div className="space-y-6">
                    <div>
                        <label className="block text-base font-medium mb-2">{t("budgetTracker.itemName")}</label>
                        <InputText 
                            value={name} 
                            onChange={setName} 
                            placeholder={t("budgetTracker.enterItemName")} 
                            className="w-full neomorphic-inset p-3 rounded-lg text-base" 
                        />
                    </div>

                    <div>
                        <label className="block text-base font-medium mb-2">{t("budgetTracker.itemAmount")}</label>
                        <div className="relative">
                            <InputNumber 
                                value={amount} 
                                onChange={handleAmountChange} 
                                placeholder="0.00" 
                                className="w-full neomorphic-inset p-3 pl-8 rounded-lg text-base" 
                            />
                            <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                        </div>
                    </div>

                    <div>
                        <label className="flex items-center text-base font-medium mb-2">
                            <Tag className="h-4 w-4 mr-1" />
                            {t("budgetTracker.itemCategory")}
                        </label>
                        <select
                            value={category}
                            onChange={(e) => setCategory(e.target.value)}
                            className="w-full neomorphic-inset p-3 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <option value="">{t("budgetTracker.selectCategory")}</option>
                            {itemCategories.map((category: Category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="flex justify-between mt-8">
                    <button
                        onClick={onClose}
                        className="px-6 py-3 text-base font-semibold text-gray-700 bg-white border-2 border-gray-300 rounded-full neomorphic-button hover:bg-gray-100 transition-colors"
                    >
                        {t("budgetTracker.cancel")}
                    </button>
                    <button
                        onClick={handleSubmit}
                        disabled={!isValid}
                        className={`px-6 py-3 text-base font-semibold text-white bg-${getColorScheme()}-600 rounded-full neomorphic-button hover:bg-${getColorScheme()}-700 transition-colors ${!isValid ? 'opacity-50 cursor-not-allowed' : ''}`}
                    >
                        {t("budgetTracker.save")}
                    </button>
                </div>
            </motion.div>
        </motion.div>
    )
}
