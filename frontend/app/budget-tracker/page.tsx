"use client"

import { useEffect, useState } from "react"
import { useRouter } from "next/navigation"
import { useUser } from "../domain/user/userHooks"
import { useTranslation } from "../hooks/useTranslation"
import { useBudgetPlans } from "../domain/budget/budgetHooks"
import BudgetCalendar from "../components/budget/BudgetCalendar"
import BudgetPlanDetails from "../components/budget/BudgetPlanDetails"
import CreateBudgetPlanModal from "../components/budget/CreateBudgetPlanModal"
import CreateFromExistingModal from "../components/budget/CreateFromExistingModal"
import { useError } from "../contexts/ErrorContext"
import { useValidMessage } from "../contexts/ValidContext"
import { Calculator, Calendar, ArrowRight, PieChart as PieChartIcon, DollarSign, BarChart2 } from "lucide-react"
import { PieChart, Pie, Cell, ResponsiveContainer, Legend, Tooltip } from "recharts"
import { formatCurrency } from "../utils/envelope/currencyUtils"

export default function BudgetTrackerPage() {
    const router = useRouter()
    const { user, loading: userLoading } = useUser()
    const { t } = useTranslation()
    const { error, setError } = useError()
    const { setValidMessage } = useValidMessage()
    const {
        budgetPlansCalendar,
        selectedBudgetPlan,
        loading,
        fetchBudgetPlansCalendar,
        fetchBudgetPlan,
        clearSelectedBudgetPlan,
        createBudgetPlan,
        newlyCreatedBudgetPlanId,
        needsCategories,
        wantsCategories,
        savingsCategories,
        incomesCategories,
    } = useBudgetPlans()

    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false)
    const [isCreateFromExistingModalOpen, setIsCreateFromExistingModalOpen] = useState(false)
    const [selectedDate, setSelectedDate] = useState<{ year: number; month: number } | null>(null)
    const [currentYear, setCurrentYear] = useState(() => new Date().getFullYear())

    useEffect(() => {
        if (!userLoading && !user) {
            router.push("/signin")
        } else if (user) {
            fetchBudgetPlansCalendar(currentYear)
        }
    }, [user, userLoading, router, fetchBudgetPlansCalendar, currentYear])

    useEffect(() => {
        if (newlyCreatedBudgetPlanId) {
            router.push(`/budget-tracker/${newlyCreatedBudgetPlanId}`)
        }
    }, [newlyCreatedBudgetPlanId, router])

    const handleMonthClick = async (year: number, month: number) => {
        setSelectedDate({ year, month })

        const budgetPlanData = budgetPlansCalendar?.[year]?.[month]
        const budgetPlanId = budgetPlanData?.uuid

        if (budgetPlanId && budgetPlanId !== null) {
            router.push(`/budget-tracker/${budgetPlanId}`)
        } else {
            clearSelectedBudgetPlan()
            setIsCreateModalOpen(true)
        }
    }

    const handleCreateFromExisting = () => {
        if (!selectedDate) return
        setIsCreateModalOpen(false)
        setIsCreateFromExistingModalOpen(true)
    }

    const handleCloseModals = () => {
        setIsCreateModalOpen(false)
        setIsCreateFromExistingModalOpen(false)
    }

    const handleCreateNewBudgetPlan = async (
        currency: string,
        incomes: { name: string; amount: number; category: string }[],
    ) => {
        if (!selectedDate) return

        await createBudgetPlan(new Date(selectedDate.year, selectedDate.month - 1), currency, incomes)
        handleCloseModals()
    }

    const handleYearChange = (newYear: number) => {
        setCurrentYear(newYear)
        fetchBudgetPlansCalendar(newYear)
    }

    const renderYearlyCharts = () => {
        if (!budgetPlansCalendar) return null

        const chartTypes = [
            {
                title: "Yearly Income",
                data: budgetPlansCalendar.incomeCategoriesTotal,
                ratio: budgetPlansCalendar.incomeCategoriesRatio,
            },
            {
                title: "Yearly Needs",
                data: budgetPlansCalendar.needCategoriesTotal,
                ratio: budgetPlansCalendar.needCategoriesRatio,
            },
            {
                title: "Yearly Wants",
                data: budgetPlansCalendar.wantCategoriesTotal,
                ratio: budgetPlansCalendar.wantCategoriesRatio,
            },
            {
                title: "Yearly Savings",
                data: budgetPlansCalendar.savingCategoriesTotal,
                ratio: budgetPlansCalendar.savingCategoriesRatio,
            },
        ]

        // Define a color palette
        const colorPalette = [
            "#FF6B6B", // Coral
            "#4ECDC4", // Turquoise
            "#45B7D1", // Sky Blue
            "#FFA07A", // Light Salmon
            "#98D8C8", // Mint
            "#F7DC6F", // Pale Yellow
            "#BB8FCE", // Light Purple
            "#82E0AA", // Light Green
        ]

        return (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                {chartTypes.map((chart, index) => {
                    const chartData = Object.entries(chart.data).map(([name, value]) => ({
                        name,
                        value: Number.parseFloat(value),
                    }))

                    const total = chartData.reduce((sum, item) => sum + item.value, 0)

                    return (
                        <div key={index} className="neomorphic p-4 rounded-lg">
                            <h3 className="text-xl font-semibold mb-4">{chart.title}</h3>
                            <div className="h-64">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <defs>
                                            <filter id={`soft-shadow-${index}`}>
                                                <feDropShadow dx="2" dy="2" stdDeviation="3" floodOpacity="0.2" />
                                                <feDropShadow dx="-2" dy="-2" stdDeviation="3" floodColor="white" floodOpacity="0.4" />
                                            </filter>
                                        </defs>
                                        <Pie
                                            data={chartData}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={60}
                                            outerRadius={80}
                                            paddingAngle={2}
                                            dataKey="value"
                                            filter={`url(#soft-shadow-${index})`}
                                            stroke="hsl(var(--background))"
                                            strokeWidth={2}
                                        >
                                            {chartData.map((entry, i) => (
                                                <Cell
                                                    key={`cell-${i}`}
                                                    fill={colorPalette[i % colorPalette.length]}
                                                    className="drop-shadow-sm"
                                                />
                                            ))}
                                        </Pie>
                                        <Tooltip
                                            formatter={(value, name, props) => [
                                                formatCurrency(value as number, selectedBudgetPlan?.budgetPlan.currency || "USD"),
                                                name,
                                            ]}
                                            contentStyle={{
                                                backgroundColor: "hsl(var(--background))",
                                                border: "none",
                                                borderRadius: "0.5rem",
                                                boxShadow: "var(--neomorphic-shadow)",
                                            }}
                                        />
                                        <Legend
                                            formatter={(value, entry, index) => (
                                                <span style={{ color: colorPalette[index % colorPalette.length] }}>{value}</span>
                                            )}
                                        />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                            <div className="mt-4">
                                <h4 className="font-semibold">Category Ratios:</h4>
                                <ul>
                                    {Object.entries(chart.ratio).map(([category, ratio], i) => (
                                        <li key={category} style={{ color: colorPalette[i % colorPalette.length] }}>
                                            {category}: {ratio}
                                        </li>
                                    ))}
                                </ul>
                                <p className="mt-2 font-semibold">
                                    Total: {formatCurrency(total, selectedBudgetPlan?.budgetPlan.currency || "USD")}
                                </p>
                            </div>
                        </div>
                    )
                })}
            </div>
        )
    }

    if (userLoading || loading) {
        return (
            <div className="flex justify-center items-center h-screen">
                <div className="animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-primary"></div>
            </div>
        )
    }

    if (!user) return null

    return (
        <div className="min-h-screen bg-background">
            <main className="container mx-auto px-4 py-8 md:py-16">
                <section className="text-center mb-8 md:mb-16">
                    <h1 className="text-3xl md:text-4xl font-bold mb-4">
                        {t("budgetTracker.title")}
                    </h1>
                    <p className="text-lg md:text-xl text-muted-foreground mb-6">
                        {t("budgetTracker.subtitle")}
                    </p>
                    <div className="flex items-center justify-center space-x-4">
                        <select
                            value={currentYear}
                            onChange={(e) => handleYearChange(Number(e.target.value))}
                            className="neomorphic-inset p-2 rounded-md text-base"
                        >
                            {Array.from({ length: 5 }, (_, i) => currentYear - 2 + i).map((year) => (
                                <option key={year} value={year}>
                                    {year}
                                </option>
                            ))}
                        </select>
                        <button
                            onClick={() => setIsCreateModalOpen(true)}
                            className="inline-flex items-center px-4 py-2 text-base font-semibold text-primary bg-white border-2 border-primary rounded-full neomorphic-button hover:bg-primary hover:text-white transition-colors"
                        >
                            {t("budgetTracker.createNew")} <Calculator className="ml-2 h-4 w-4" />
                        </button>
                    </div>
                </section>

                <section className="grid gap-8 md:grid-cols-12 mb-8 md:mb-16">
                    <div className="md:col-span-5 lg:col-span-4">
                        <div className="neomorphic p-6 rounded-lg">
                            <h2 className="text-xl md:text-2xl font-bold mb-6 flex items-center justify-center">
                                <Calendar className="mr-2 h-6 w-6 text-primary" />
                                {t("budgetTracker.calendar")}
                            </h2>
                            <BudgetCalendar
                                budgetPlansCalendar={budgetPlansCalendar}
                                onMonthClick={handleMonthClick}
                                currentYear={currentYear}
                                onYearChange={handleYearChange}
                            />
                        </div>
                    </div>

                    <div className="md:col-span-7 lg:col-span-8">
                        {selectedBudgetPlan ? (
                            <BudgetPlanDetails
                                budgetPlan={selectedBudgetPlan}
                                categories={{
                                    needs: needsCategories,
                                    wants: wantsCategories,
                                    savings: savingsCategories,
                                    incomes: incomesCategories,
                                }}
                            />
                        ) : (
                            <div className="neomorphic p-8 rounded-lg h-full flex flex-col items-center justify-center text-center">
                                <PieChartIcon className="h-16 w-16 text-primary mb-6" />
                                <h2 className="text-2xl font-bold mb-4">{t("budgetTracker.noBudgetSelected")}</h2>
                                <p className="text-lg text-muted-foreground mb-6">{t("budgetTracker.selectMonthOrCreate")}</p>
                                <button
                                    onClick={() => setIsCreateModalOpen(true)}
                                    className="inline-flex items-center px-6 py-3 text-lg font-semibold text-primary bg-white border-2 border-primary rounded-full neomorphic-button hover:bg-primary hover:text-white transition-colors"
                                >
                                    {t("budgetTracker.createNew")} <ArrowRight className="ml-2 h-5 w-5" />
                                </button>
                            </div>
                        )}
                    </div>
                </section>

                {budgetPlansCalendar && (
                    <section className="mb-8 md:mb-16">
                        <h2 className="text-2xl md:text-3xl font-bold mb-6 text-center">{t("budgetTracker.yearlyOverview")}</h2>
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                            {renderYearlyChartCard("Yearly Income", budgetPlansCalendar.incomeCategoriesTotal, budgetPlansCalendar.incomeCategoriesRatio, "#4CAF50")}
                            {renderYearlyChartCard("Yearly Needs", budgetPlansCalendar.needCategoriesTotal, budgetPlansCalendar.needCategoriesRatio, "#2196F3")}
                            {renderYearlyChartCard("Yearly Wants", budgetPlansCalendar.wantCategoriesTotal, budgetPlansCalendar.wantCategoriesRatio, "#FFC107")}
                            {renderYearlyChartCard("Yearly Savings", budgetPlansCalendar.savingCategoriesTotal, budgetPlansCalendar.savingCategoriesRatio, "#FF5722")}
                        </div>
                    </section>
                )}

                <section className="grid gap-6 mb-8 md:mb-16 md:grid-cols-3">
                    <div className="neomorphic p-6 rounded-lg text-center">
                        <DollarSign className="mx-auto h-12 w-12 text-primary mb-4" />
                        <h2 className="text-xl font-semibold mb-2">{t("budgetTracker.trackBudget")}</h2>
                        <p className="text-base text-muted-foreground">
                            {t("budgetTracker.trackBudgetDesc")}
                        </p>
                    </div>
                    <div className="neomorphic p-6 rounded-lg text-center">
                        <PieChartIcon className="mx-auto h-12 w-12 text-primary mb-4" />
                        <h2 className="text-xl font-semibold mb-2">{t("budgetTracker.visualizeSpending")}</h2>
                        <p className="text-base text-muted-foreground">
                            {t("budgetTracker.visualizeSpendingDesc")}
                        </p>
                    </div>
                    <div className="neomorphic p-6 rounded-lg text-center">
                        <BarChart2 className="mx-auto h-12 w-12 text-primary mb-4" />
                        <h2 className="text-xl font-semibold mb-2">{t("budgetTracker.analyzeCategories")}</h2>
                        <p className="text-base text-muted-foreground">
                            {t("budgetTracker.analyzeCategoriesDesc")}
                        </p>
                    </div>
                </section>

                {isCreateModalOpen && selectedDate && (
                    <CreateBudgetPlanModal
                        isOpen={isCreateModalOpen}
                        onClose={handleCloseModals}
                        onCreateFromExisting={handleCreateFromExisting}
                        selectedDate={selectedDate}
                        categories={{
                            needs: needsCategories,
                            wants: wantsCategories,
                            savings: savingsCategories,
                            incomes: incomesCategories,
                        }}
                    />
                )}

                {isCreateFromExistingModalOpen && selectedDate && (
                    <CreateFromExistingModal
                        isOpen={isCreateFromExistingModalOpen}
                        onClose={handleCloseModals}
                        selectedDate={selectedDate}
                        budgetPlansCalendar={budgetPlansCalendar}
                    />
                )}
            </main>
        </div>
    );

    function renderYearlyChartCard(title, data, ratio, mainColor) {
        const colorPalette = generateColorPalette(mainColor, Object.keys(data).length);
        const chartData = Object.entries(data).map(([name, value], index) => ({
            name,
            value: Number.parseFloat(value),
            color: colorPalette[index % colorPalette.length]
        }));

        const total = chartData.reduce((sum, item) => sum + item.value, 0);

        return (
            <div className="neomorphic p-6 rounded-lg text-center">
                <h3 className="text-xl font-semibold mb-4">{title}</h3>
                <div className="h-48 mb-4">
                    <ResponsiveContainer width="100%" height="100%">
                        <PieChart>
                            <Pie
                                data={chartData}
                                cx="50%"
                                cy="50%"
                                innerRadius={30}
                                outerRadius={60}
                                paddingAngle={3}
                                dataKey="value"
                                stroke="hsl(var(--background))"
                                strokeWidth={2}
                            >
                                {chartData.map((entry, i) => (
                                    <Cell 
                                        key={`cell-${i}`} 
                                        fill={entry.color} 
                                        className="drop-shadow-md" 
                                    />
                                ))}
                            </Pie>
                            <Tooltip
                                formatter={(value) => formatCurrency(
                                    value, 
                                    selectedBudgetPlan?.budgetPlan.currency || "USD"
                                )}
                                contentStyle={{
                                    backgroundColor: "hsl(var(--background))",
                                    border: "none",
                                    borderRadius: "0.5rem",
                                    boxShadow: "var(--neomorphic-shadow)",
                                }}
                            />
                        </PieChart>
                    </ResponsiveContainer>
                </div>
                <div className="neomorphic-inset p-3 rounded-md">
                    <p className="font-semibold">{formatCurrency(total, selectedBudgetPlan?.budgetPlan.currency || "USD")}</p>
                </div>
            </div>
        );
    }
    
    function generateColorPalette(baseColor, count) {
        // This is a simplified version - in a real app, you might want to use a library
        // that can generate proper color schemes based on the base color
        const palette = [
            baseColor,
            "#FF6B6B", 
            "#4ECDC4", 
            "#45B7D1", 
            "#FFA07A", 
            "#98D8C8", 
            "#F7DC6F", 
            "#BB8FCE"
        ];
        
        return palette;
    }
}
