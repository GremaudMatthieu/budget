"use client"

import { useState, useCallback, useEffect } from "react"
import { PieChart, Pie, Cell, ResponsiveContainer, Legend, Tooltip } from "recharts"
import { useTranslation } from "../../hooks/useTranslation"
import { formatCurrency } from "../../utils/envelope/currencyUtils"
import { useBudgetPlans } from "../../domain/budget/budgetHooks"
import type { BudgetPlan, Category } from "../../domain/budget/budgetTypes"
import { Calculator, DollarSign, PiggyBank, ShoppingBag, Plus, Edit2, Trash2, Loader2, Tag, ChevronRight } from "lucide-react"
import BudgetItemModal from "./BudgetItemModal"
import DeleteConfirmationModal from "./DeleteConfirmationModal"
import { useSocket } from "../../hooks/useSocket"

interface BudgetPlanDetailsProps {
    budgetPlan: BudgetPlan
    categories: {
        needs: Category[]
        wants: Category[]
        savings: Category[]
        incomes: Category[]
    }
}

type TabType = "overview" | "needs" | "wants" | "savings" | "incomes"

export default function BudgetPlanDetails({ budgetPlan, categories }: BudgetPlanDetailsProps) {
    const { t, language } = useTranslation()
    const {
        addBudgetItem,
        adjustBudgetItem,
        removeBudgetItem,
        loading,
        fetchBudgetPlan,
        selectedBudgetPlan,
        setSelectedBudgetPlan,
    } = useBudgetPlans()

    const [activeTab, setActiveTab] = useState<TabType>(() => {
        if (typeof window !== "undefined") {
            return (localStorage.getItem("budgetActiveTab") as TabType) || "overview"
        }
        return "overview"
    })

    // Modal states
    const [isAddModalOpen, setIsAddModalOpen] = useState(false)
    const [isEditModalOpen, setIsEditModalOpen] = useState(false)
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false)
    const [currentItemType, setCurrentItemType] = useState<"need" | "want" | "saving" | "income">("need")
    const [currentItem, setCurrentItem] = useState<{ id: string; name: string; amount: string; category: string } | null>(
        null,
    )

    const { socket } = useSocket()

    useEffect(() => {
        setSelectedBudgetPlan(budgetPlan)
    }, [budgetPlan, setSelectedBudgetPlan])

    useEffect(() => {
        if (!socket) return

        const handleBudgetPlanEvent = (event: { aggregateId: string; type: string }) => {
            if (selectedBudgetPlan && event.aggregateId === selectedBudgetPlan.budgetPlan.uuid) {
                fetchBudgetPlan(event.aggregateId)
            }
        }

        const eventTypes = [
            "BudgetPlanIncomeAdded",
            "BudgetPlanIncomeAdjusted",
            "BudgetPlanIncomeRemoved",
            "BudgetPlanNeedAdded",
            "BudgetPlanNeedAdjusted",
            "BudgetPlanNeedRemoved",
            "BudgetPlanSavingAdded",
            "BudgetPlanSavingAdjusted",
            "BudgetPlanSavingRemoved",
            "BudgetPlanWantAdded",
            "BudgetPlanWantAdjusted",
            "BudgetPlanWantRemoved",
        ]

        eventTypes.forEach((eventType) => {
            socket.on(eventType, handleBudgetPlanEvent)
        })

        return () => {
            eventTypes.forEach((eventType) => {
                socket.off(eventType, handleBudgetPlanEvent)
            })
        }
    }, [socket, fetchBudgetPlan, selectedBudgetPlan])

    const handleTabChange = useCallback((tab: TabType) => {
        setActiveTab(tab)
        localStorage.setItem("budgetActiveTab", tab)
    }, [])

    const { budgetPlan: planDetails, needs, wants, savings, incomes } = selectedBudgetPlan || {}

    // Calculate totals
    const totalIncome = incomes?.reduce((sum, income) => sum + Number.parseFloat(income.incomeAmount), 0) || 0
    const totalNeeds = needs?.reduce((sum, need) => sum + Number.parseFloat(need.needAmount), 0) || 0
    const totalWants = wants?.reduce((sum, want) => sum + Number.parseFloat(want.wantAmount), 0) || 0
    const totalSavings = savings?.reduce((sum, saving) => sum + Number.parseFloat(saving.savingAmount), 0) || 0

    // Calculate percentages
    const needsPercentage = totalIncome > 0 ? (totalNeeds / totalIncome) * 100 : 0
    const wantsPercentage = totalIncome > 0 ? (totalWants / totalIncome) * 100 : 0
    const savingsPercentage = totalIncome > 0 ? (totalSavings / totalIncome) * 100 : 0

    // Format date
    const formatDate = (dateString: string) => {
        const date = new Date(dateString)
        return language === "fr"
            ? date.toLocaleDateString("fr-FR", { year: "numeric", month: "long" })
            : date.toLocaleDateString("en-US", { year: "numeric", month: "long" })
    }

    // Prepare chart data
    const chartData = [
        { name: t("budgetTracker.needs"), value: totalNeeds, color: "#4CAF50" },
        { name: t("budgetTracker.wants"), value: totalWants, color: "#2196F3" },
        { name: t("budgetTracker.savings"), value: totalSavings, color: "#FFC107" },
    ]

    // Handle opening add modal
    const handleOpenAddModal = (type: "need" | "want" | "saving" | "income") => {
        setCurrentItemType(type)
        setIsAddModalOpen(true)
    }

    // Handle opening edit modal
    const handleOpenEditModal = (
        type: "need" | "want" | "saving" | "income",
        id: string,
        name: string,
        amount: string,
        category: string,
    ) => {
        setCurrentItemType(type)
        setCurrentItem({ id, name, amount, category })
        setIsEditModalOpen(true)
    }

    // Handle opening delete modal
    const handleOpenDeleteModal = (type: "need" | "want" | "saving" | "income", id: string, name: string) => {
        setCurrentItemType(type)
        setCurrentItem({ id, name, amount: "", category: "" })
        setIsDeleteModalOpen(true)
    }

    // Handle add item
    const handleAddItem = useCallback(
        async (name: string, amount: string, category: string) => {
            if (await addBudgetItem(currentItemType, name, amount, category)) {
                setIsAddModalOpen(false)
                if (planDetails) {
                    await fetchBudgetPlan(planDetails.uuid)
                }
            }
        },
        [addBudgetItem, currentItemType, fetchBudgetPlan, planDetails],
    )

    // Handle edit item
    const handleEditItem = useCallback(
        async (name: string, amount: string, category: string) => {
            if (currentItem && planDetails) {
                if (await adjustBudgetItem(currentItemType, currentItem.id, name, amount, category)) {
                    setIsEditModalOpen(false)
                    await fetchBudgetPlan(planDetails.uuid)
                }
            }
        },
        [adjustBudgetItem, currentItem, currentItemType, fetchBudgetPlan, planDetails],
    )

    // Handle delete item
    const handleDeleteItem = useCallback(async () => {
        if (currentItem && planDetails) {
            if (await removeBudgetItem(currentItemType, currentItem.id)) {
                setIsDeleteModalOpen(false)
                await fetchBudgetPlan(planDetails.uuid)
            }
        }
    }, [removeBudgetItem, currentItem, currentItemType, fetchBudgetPlan, planDetails])

    // Get the appropriate field names based on item type
    const getFieldNames = (type: "need" | "want" | "saving" | "income") => {
        switch (type) {
            case "need":
                return { name: "needName", amount: "needAmount", category: "category" }
            case "want":
                return { name: "wantName", amount: "wantAmount", category: "category" }
            case "saving":
                return { name: "savingName", amount: "savingAmount", category: "category" }
            case "income":
                return { name: "incomeName", amount: "incomeAmount", category: "category" }
        }
    }

    // Use the categories prop instead of fetching it from useBudgetPlans
    const getCategoryName = (type: "need" | "want" | "saving" | "income", categoryId: string) => {
        const categoryList =
            categories[type === "need" ? "needs" : type === "want" ? "wants" : type === "saving" ? "savings" : "incomes"]
        return categoryList.find((cat) => cat.id === categoryId)?.name || categoryId
    }

    // Render item list based on type - redesigned version
    const renderItemList = (type: "need" | "want" | "saving" | "income", items: any[]) => {
        const fields = getFieldNames(type)
        const typeColor = type === "need" ? "green" : type === "want" ? "blue" : type === "saving" ? "amber" : "purple";

        return (
            <div className="neomorphic p-6 rounded-lg">
                <div className="flex justify-between items-center mb-6">
                    <h3 className="text-xl md:text-2xl font-bold flex items-center">
                        {type === "need" && <DollarSign className={`h-6 w-6 mr-2 text-${typeColor}-600`} />}
                        {type === "want" && <ShoppingBag className={`h-6 w-6 mr-2 text-${typeColor}-600`} />}
                        {type === "saving" && <PiggyBank className={`h-6 w-6 mr-2 text-${typeColor}-600`} />}
                        {type === "income" && <Calculator className={`h-6 w-6 mr-2 text-${typeColor}-600`} />}
                        {t(`budgetTracker.${type}s`)}
                        {type !== "income" &&
                            ` (${(type === "need" ? needsPercentage : type === "want" ? wantsPercentage : savingsPercentage).toFixed(0)}%)`}
                    </h3>
                    <button
                        onClick={() => handleOpenAddModal(type)}
                        className="p-3 neomorphic-button text-primary rounded-full hover:bg-primary hover:text-white transition-colors"
                        aria-label={t("budgetTracker.addItem")}
                        disabled={loading}
                    >
                        {loading ? <Loader2 className="h-5 w-5 animate-spin" /> : <Plus className="h-5 w-5" />}
                    </button>
                </div>

                <p className="text-lg text-muted-foreground mb-6">{t(`budgetTracker.${type}Description`)}</p>

                <div className="space-y-4">
                    {items?.length === 0 ? (
                        <div className="text-center text-muted-foreground p-6 neomorphic-inset rounded-lg">
                            <p>{t(`budgetTracker.no${type.charAt(0).toUpperCase() + type.slice(1)}s`)}</p>
                        </div>
                    ) : (
                        items.map((item) => (
                            <div 
                                key={item.uuid} 
                                className="neomorphic-inset p-4 rounded-lg hover:shadow-md transition-shadow"
                            >
                                <div className="flex justify-between items-center">
                                    <div className="flex flex-col">
                                        <span className="text-lg font-semibold mb-1">
                                            {item[fields.name]}
                                        </span>
                                        <span className="text-sm inline-flex items-center text-muted-foreground">
                                            <Tag className="w-3 h-3 mr-1" />
                                            {getCategoryName(type, item[fields.category])}
                                        </span>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span className="text-xl font-bold">
                                            {formatCurrency(item[fields.amount], planDetails?.currency)}
                                        </span>
                                        <div className="flex space-x-2">
                                            <button
                                                onClick={() =>
                                                    handleOpenEditModal(
                                                        type,
                                                        item.uuid,
                                                        item[fields.name],
                                                        item[fields.amount],
                                                        item[fields.category],
                                                    )
                                                }
                                                className="p-2 neomorphic-button text-blue-500 rounded-full hover:bg-blue-500 hover:text-white transition-colors"
                                                disabled={loading}
                                            >
                                                <Edit2 className="h-4 w-4" />
                                            </button>
                                            <button
                                                onClick={() => handleOpenDeleteModal(type, item.uuid, item[fields.name])}
                                                className="p-2 neomorphic-button text-red-500 rounded-full hover:bg-red-500 hover:text-white transition-colors"
                                                disabled={loading}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>

                <div className="mt-8 pt-4 border-t border-gray-200">
                    <div className="flex justify-between items-center">
                        <span className="text-xl font-bold">{t(`budgetTracker.total${type.charAt(0).toUpperCase() + type.slice(1)}s`)}</span>
                        <span className="text-2xl font-bold text-primary">
                            {formatCurrency(
                                type === "need"
                                    ? totalNeeds
                                    : type === "want"
                                        ? totalWants
                                        : type === "saving"
                                            ? totalSavings
                                            : totalIncome,
                                planDetails?.currency
                            )}
                        </span>
                    </div>
                </div>
            </div>
        )
    }

    if (!selectedBudgetPlan) {
        return (
            <div className="flex justify-center items-center h-64">
                <div className="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-primary"></div>
            </div>
        )
    }

    return (
        <div className="neomorphic p-6 rounded-lg">
            <div className="text-center mb-8">
                <h2 className="text-3xl font-bold mb-3">{planDetails?.date ? formatDate(planDetails.date) : "No Date"}</h2>
                <div className="text-2xl font-bold text-primary mb-2">
                    {formatCurrency(totalIncome, planDetails?.currency)}
                </div>
                <p className="text-muted-foreground">{t("budgetTracker.totalMonthlyIncome")}</p>
            </div>

            <div className="grid gap-6 md:grid-cols-5 mb-8">
                {["overview", "needs", "wants", "savings", "incomes"].map((tab) => (
                    <button
                        key={tab}
                        onClick={() => handleTabChange(tab as TabType)}
                        className={`py-3 px-4 rounded-lg text-base font-semibold flex items-center justify-center
                            ${activeTab === tab 
                                ? "neomorphic-inset text-primary" 
                                : "neomorphic-button text-muted-foreground hover:text-primary-600 transition-colors"
                            }`}
                    >
                        {tab === "overview" && <Calculator className="h-5 w-5 mr-2" />}
                        {tab === "needs" && <DollarSign className="h-5 w-5 mr-2" />}
                        {tab === "wants" && <ShoppingBag className="h-5 w-5 mr-2" />}
                        {tab === "savings" && <PiggyBank className="h-5 w-5 mr-2" />}
                        {tab === "incomes" && <Calculator className="h-5 w-5 mr-2" />}
                        {t(`budgetTracker.${tab}`)}
                    </button>
                ))}
            </div>

            {activeTab === "overview" && (
                <div className="space-y-8">
                    <div className="neomorphic-inset p-6 rounded-lg bg-gradient-to-br from-gray-50 to-white">
                        <h3 className="text-2xl font-bold mb-6 text-center">{t("budgetTracker.expenseBreakdown")}</h3>
                        <div className="h-80">
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={chartData}
                                        cx="50%"
                                        cy="50%"
                                        innerRadius="40%"
                                        outerRadius="70%"
                                        fill="#8884d8"
                                        paddingAngle={6}
                                        dataKey="value"
                                        label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                                        stroke="#ffffff"
                                        strokeWidth={4}
                                    >
                                        {chartData.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={entry.color} className="drop-shadow-md" />
                                        ))}
                                    </Pie>
                                    <Tooltip formatter={(value) => formatCurrency(value as number, planDetails?.currency)}
                                        contentStyle={{
                                            backgroundColor: "white",
                                            border: "none",
                                            borderRadius: "0.5rem",
                                            boxShadow: "var(--neomorphic-shadow)",
                                        }}
                                    />
                                    <Legend 
                                        layout="horizontal" 
                                        verticalAlign="bottom" 
                                        align="center"
                                        wrapperStyle={{ paddingTop: "20px" }}
                                    />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    <div className="grid gap-6 md:grid-cols-2">
                        <div className="neomorphic p-6 rounded-lg">
                            <h3 className="text-xl font-bold mb-4 flex items-center">
                                <Calculator className="mr-2 h-6 w-6 text-purple-600" />
                                {t("budgetTracker.incomes")}
                            </h3>
                            <div className="neomorphic-inset p-4 rounded-lg mb-4">
                                <ul className="space-y-3">
                                    {incomes?.map((income) => (
                                        <li key={income.uuid} className="flex justify-between items-center py-2">
                                            <div className="flex items-center">
                                                <div className="w-2 h-2 bg-purple-500 rounded-full mr-2"></div>
                                                <span className="font-medium">{income.incomeName}</span>
                                            </div>
                                            <span className="font-semibold">
                                                {formatCurrency(income.incomeAmount, planDetails?.currency)}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                            <div className="flex justify-between items-center p-3 bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg">
                                <span className="text-lg font-bold">{t("budgetTracker.totalIncome")}</span>
                                <span className="text-xl font-bold text-purple-600">{formatCurrency(totalIncome, planDetails?.currency)}</span>
                            </div>
                            <button 
                                onClick={() => handleTabChange('incomes')}
                                className="w-full mt-4 py-2 px-4 neomorphic-button text-primary hover:bg-primary hover:text-white transition-colors flex items-center justify-center rounded-md"
                            >
                                {t("budgetTracker.viewDetails")} <ChevronRight className="ml-1 h-4 w-4" />
                            </button>
                        </div>

                        <div className="space-y-6">
                            {[
                                {
                                    title: t("budgetTracker.needs"),
                                    total: totalNeeds,
                                    percentage: needsPercentage,
                                    color: "green",
                                    tabKey: "needs",
                                    icon: <DollarSign className="h-5 w-5" />
                                },
                                {
                                    title: t("budgetTracker.wants"),
                                    total: totalWants,
                                    percentage: wantsPercentage,
                                    color: "blue",
                                    tabKey: "wants",
                                    icon: <ShoppingBag className="h-5 w-5" />
                                },
                                {
                                    title: t("budgetTracker.savings"),
                                    total: totalSavings,
                                    percentage: savingsPercentage,
                                    color: "amber",
                                    tabKey: "savings",
                                    icon: <PiggyBank className="h-5 w-5" />
                                }
                            ].map((item) => (
                                <div key={item.tabKey} className="neomorphic p-5 rounded-lg">
                                    <div className="flex justify-between items-start mb-3">
                                        <div className="flex items-center">
                                            <div className={`p-2 rounded-full bg-${item.color}-100 text-${item.color}-600 mr-3`}>
                                                {item.icon}
                                            </div>
                                            <h3 className="text-lg font-bold">{item.title}</h3>
                                        </div>
                                        <div className={`py-1 px-3 rounded-full bg-${item.color}-100 text-${item.color}-600 font-medium text-sm`}>
                                            {item.percentage.toFixed(1)}%
                                        </div>
                                    </div>
                                    <div className="flex justify-between items-center mb-4">
                                        <span className="text-xl font-bold">{formatCurrency(item.total, planDetails?.currency)}</span>
                                    </div>
                                    <div className="w-full bg-gray-200 rounded-full h-2.5">
                                        <div 
                                            className={`bg-${item.color}-500 h-2.5 rounded-full`} 
                                            style={{ width: `${item.percentage}%` }}
                                        ></div>
                                    </div>
                                    <button 
                                        onClick={() => handleTabChange(item.tabKey as TabType)}
                                        className="w-full mt-4 py-2 px-4 neomorphic-button text-primary hover:bg-primary hover:text-white transition-colors flex items-center justify-center rounded-md"
                                    >
                                        {t("budgetTracker.viewDetails")} <ChevronRight className="ml-1 h-4 w-4" />
                                    </button>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            )}

            {activeTab === "needs" && renderItemList("need", needs)}
            {activeTab === "wants" && renderItemList("want", wants)}
            {activeTab === "savings" && renderItemList("saving", savings)}
            {activeTab === "incomes" && renderItemList("income", incomes)}

            {/* Modals */}
            <BudgetItemModal
                isOpen={isAddModalOpen}
                onClose={() => setIsAddModalOpen(false)}
                onSubmit={handleAddItem}
                title={t(`budgetTracker.add${currentItemType.charAt(0).toUpperCase() + currentItemType.slice(1)}`)}
                itemType={currentItemType}
                categories={categories}
            />

            <BudgetItemModal
                isOpen={isEditModalOpen}
                onClose={() => setIsEditModalOpen(false)}
                onSubmit={handleEditItem}
                title={t(`budgetTracker.edit${currentItemType.charAt(0).toUpperCase() + currentItemType.slice(1)}`)}
                initialName={currentItem?.name || ""}
                initialAmount={currentItem?.amount || ""}
                initialCategory={currentItem?.category || ""}
                isEdit={true}
                itemType={currentItemType}
                categories={categories}
            />

            <DeleteConfirmationModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteItem}
                itemName={currentItem?.name || ""}
            />
        </div>
    )
}
