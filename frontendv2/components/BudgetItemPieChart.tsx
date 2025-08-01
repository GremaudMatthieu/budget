import React from 'react';
import { View, Text, Platform } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useTranslation } from '@/utils/useTranslation';
import { formatCurrency } from '@/utils/currencyUtils';

interface PieChartData {
    name: string;
    value: number;
    color: string;
}

interface BudgetItemPieChartProps {
    data: PieChartData[];
    size?: number;
    currency?: string;
}

// Modern card-based visualization that matches the app's design language
const BudgetItemPieChart: React.FC<BudgetItemPieChartProps> = ({ data, size = 200, currency = 'USD' }) => {
    const { t } = useTranslation();

    // Filter out zero values
    const filteredData = data.filter(item => item.value > 0);

    // Check if there's any data to display
    if (filteredData.length === 0) {
        return (
            <View className="items-center py-8">
                <View className="w-16 h-16 rounded-full bg-gray-100 items-center justify-center mb-3">
                    <Ionicons name="analytics-outline" size={24} color="#64748b" />
                </View>
                <Text className="text-gray-500 text-center">No data to display</Text>
            </View>
        );
    }

    // Calculate total and percentages
    const total = filteredData.reduce((sum, item) => sum + item.value, 0);

    // Get icon based on category name
    const getIcon = (name: string) => {
        const nameLower = name.toLowerCase();
        if (nameLower.includes('need') || nameLower.includes('besoin')) return 'home-outline';
        if (nameLower.includes('want') || nameLower.includes('envie')) return 'heart-outline';
        if (nameLower.includes('saving') || nameLower.includes('épargne')) return 'wallet-outline';
        if (nameLower.includes('income') || nameLower.includes('revenu')) return 'trending-up-outline';
        return 'pie-chart-outline';
    };

    // Sort by value for better visual hierarchy
    const sortedData = [...filteredData].sort((a, b) => b.value - a.value);

    // Modern stylized pie chart with proper segments
    const createPieChart = () => {
        const size = 220;
        const radius = size / 2;
        const center = radius;
        const innerRadius = radius * 0.6; // For donut effect

        // Calculate angles for each segment
        let currentAngle = -90; // Start from top
        const segments = sortedData.map((item, index) => {
            const percentage = (item.value / total) * 100;
            const angle = (percentage / 100) * 360;
            const startAngle = currentAngle;
            const endAngle = currentAngle + angle;
            currentAngle = endAngle;

            return {
                ...item,
                percentage,
                startAngle,
                endAngle,
                angle
            };
        });

        // Convert angles to radians and create path
        const polarToCartesian = (centerX: number, centerY: number, radius: number, angleInDegrees: number) => {
            const angleInRadians = (angleInDegrees - 90) * Math.PI / 180.0;
            return {
                x: centerX + (radius * Math.cos(angleInRadians)),
                y: centerY + (radius * Math.sin(angleInRadians))
            };
        };

        const createArcPath = (x: number, y: number, radius: number, startAngle: number, endAngle: number, innerRadius: number) => {
            const start = polarToCartesian(x, y, radius, endAngle);
            const end = polarToCartesian(x, y, radius, startAngle);
            const largeArcFlag = endAngle - startAngle <= 180 ? "0" : "1";

            const outerArc = [
                "M", start.x, start.y,
                "A", radius, radius, 0, largeArcFlag, 0, end.x, end.y
            ].join(" ");

            if (innerRadius > 0) {
                const innerStart = polarToCartesian(x, y, innerRadius, endAngle);
                const innerEnd = polarToCartesian(x, y, innerRadius, startAngle);
                return [
                    "M", start.x, start.y,
                    "A", radius, radius, 0, largeArcFlag, 0, end.x, end.y,
                    "L", innerEnd.x, innerEnd.y,
                    "A", innerRadius, innerRadius, 0, largeArcFlag, 1, innerStart.x, innerStart.y,
                    "Z"
                ].join(" ");
            }

            return outerArc + " L " + x + " " + y + " Z";
        };

        return (
            <View className="items-center py-4">
                {/* Neomorphism container */}
                <View
                    className="relative rounded-full"
                    style={{
                        width: size + 40,
                        height: size + 40,
                        backgroundColor: '#f8fafc',
                        shadowColor: '#000',
                        shadowOffset: { width: 8, height: 8 },
                        shadowOpacity: 0.1,
                        shadowRadius: 16,
                        elevation: 8,
                        // Neomorphism inner shadow effect
                        ...(Platform.OS === 'web' ? {
                            boxShadow: `
                8px 8px 16px rgba(0, 0, 0, 0.1),
                -8px -8px 16px rgba(255, 255, 255, 0.8),
                inset 2px 2px 4px rgba(0, 0, 0, 0.05)
              `
                        } : {})
                    }}
                >
                    {/* Inner neomorphism ring */}
                    <View
                        className="absolute rounded-full"
                        style={{
                            width: size + 16,
                            height: size + 16,
                            top: 12,
                            left: 12,
                            backgroundColor: '#f1f5f9',
                            shadowColor: '#000',
                            shadowOffset: { width: -4, height: -4 },
                            shadowOpacity: 0.15,
                            shadowRadius: 8,
                            elevation: 4,
                            ...(Platform.OS === 'web' ? {
                                boxShadow: `
                  inset 4px 4px 8px rgba(0, 0, 0, 0.1),
                  inset -4px -4px 8px rgba(255, 255, 255, 0.9)
                `
                            } : {})
                        }}
                    >
                        {/* Pie chart container with elevated effect */}
                        <View
                            className="rounded-full relative overflow-hidden"
                            style={{
                                width: size,
                                height: size,
                                top: 8,
                                left: 8,
                                backgroundColor: '#ffffff',
                                shadowColor: '#000',
                                shadowOffset: { width: 2, height: 2 },
                                shadowOpacity: 0.2,
                                shadowRadius: 4,
                                elevation: 6,
                                background: Platform.OS === 'web' ?
                                    `conic-gradient(${segments.map((seg, i) =>
                                        `${seg.color} ${i === 0 ? 0 : segments.slice(0, i).reduce((sum, s) => sum + s.percentage, 0)}% ${segments.slice(0, i + 1).reduce((sum, s) => sum + s.percentage, 0)}%`
                                    ).join(', ')})` :
                                    segments[0]?.color || '#e5e7eb',
                                ...(Platform.OS === 'web' ? {
                                    boxShadow: `
                    2px 2px 6px rgba(0, 0, 0, 0.15),
                    -1px -1px 3px rgba(255, 255, 255, 0.7)
                  `
                                } : {})
                            }}
                        >
                            {/* Mobile fallback - create segments with positioned elements */}
                            {Platform.OS !== 'web' && segments.map((segment, index) => {
                                const rotation = segment.startAngle + (segment.angle / 2);

                                return (
                                    <View
                                        key={index}
                                        className="absolute"
                                        style={{
                                            width: size / 2,
                                            height: size / 2,
                                            backgroundColor: segment.color,
                                            top: 0,
                                            left: size / 2,
                                            transformOrigin: '0 100%',
                                            transform: [
                                                { rotate: `${segment.startAngle}deg` },
                                                { scaleY: segment.percentage / 50 }
                                            ],
                                            opacity: 0.95,
                                        }}
                                    />
                                );
                            })}

                            {/* Neomorphism center hole */}
                            <View
                                className="absolute rounded-full items-center justify-center"
                                style={{
                                    width: innerRadius * 2,
                                    height: innerRadius * 2,
                                    top: (size - innerRadius * 2) / 2,
                                    left: (size - innerRadius * 2) / 2,
                                    backgroundColor: '#ffffff',
                                    shadowColor: '#000',
                                    shadowOffset: { width: 0, height: 0 },
                                    shadowOpacity: 0.2,
                                    shadowRadius: 8,
                                    elevation: 8,
                                    ...(Platform.OS === 'web' ? {
                                        boxShadow: `
                      inset 3px 3px 6px rgba(0, 0, 0, 0.1),
                      inset -3px -3px 6px rgba(255, 255, 255, 0.9),
                      0px 2px 4px rgba(0, 0, 0, 0.1)
                    `
                                    } : {})
                                }}
                            >
                                <Text
                                    className="font-bold text-gray-800"
                                    style={{
                                        fontSize: 18,
                                        textShadowColor: 'rgba(255, 255, 255, 0.8)',
                                        textShadowOffset: { width: 1, height: 1 },
                                        textShadowRadius: 2
                                    }}
                                >
                                    {total >= 1000 ? 
                                        formatCurrency(Math.round(total / 1000), currency) + 'K' : 
                                        formatCurrency(total, currency)
                                    }
                                </Text>
                                <Text
                                    className="text-gray-500 mt-1"
                                    style={{
                                        fontSize: 11,
                                        textShadowColor: 'rgba(255, 255, 255, 0.6)',
                                        textShadowOffset: { width: 0.5, height: 0.5 },
                                        textShadowRadius: 1
                                    }}
                                >
                                    Total
                                </Text>
                            </View>
                        </View>
                    </View>
                </View>
            </View>
        );
    };

    return (
        <View className="w-full">
            {createPieChart()}
        </View>
    );
};

export default BudgetItemPieChart;