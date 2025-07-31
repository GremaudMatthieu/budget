import React, { useState, useEffect, useRef, useLayoutEffect } from 'react';
import { View, Text, Dimensions, Platform } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { formatCurrency } from '@/utils/currencyUtils';
import { useTranslation } from '@/utils/useTranslation';

interface SavingsChartProps {
  savingsData: {
    month: string;
    monthlyAmount: number;
    cumulativeAmount: number;
    currency: string;
  }[];
  currency?: string;
}

export default function SavingsChart({ savingsData, currency = 'USD' }: SavingsChartProps) {
  const { t } = useTranslation();
  
  // Ref for measuring container dimensions
  const containerRef = useRef<HTMLDivElement | View>(null);
  
  // State for responsive container width
  const [containerWidth, setContainerWidth] = useState(400); // Default fallback
  const chartHeight = 200;
  
  // State for tooltips (web only)
  const [hoveredPoint, setHoveredPoint] = useState<number | null>(null);

  // Measure container dimensions
  useLayoutEffect(() => {
    if (Platform.OS === 'web') {
      const measureContainer = () => {
        if (containerRef.current) {
          const rect = (containerRef.current as HTMLDivElement).getBoundingClientRect();
          setContainerWidth(rect.width);
        }
      };

      // Initial measurement
      measureContainer();

      // Set up ResizeObserver for responsive behavior
      const resizeObserver = new ResizeObserver(() => {
        measureContainer();
      });

      if (containerRef.current) {
        resizeObserver.observe(containerRef.current as HTMLDivElement);
      }

      return () => resizeObserver.disconnect();
    } else {
      // For React Native, use onLayout callback instead
      // The container width will be set through onLayout in the View
    }
  }, []);

  // For React Native, handle layout changes
  const handleLayout = (event: any) => {
    if (Platform.OS !== 'web') {
      const { width } = event.nativeEvent.layout;
      setContainerWidth(width);
    }
  };

  if (!savingsData.length) {
    return (
      <View className="items-center justify-center py-8">
        <Text className="text-secondary-600 text-center">
          {t('budgetPlans.noSavingsData')}
        </Text>
      </View>
    );
  }

  const formatWithTwoDecimals = (num: number): number => {
    return Math.round(num * 100) / 100;
  };


  // Always use all the savings data as-is  
  const displayData = savingsData;

  // Calculate max value for scaling
  const maxValue = Math.max(1, ...displayData.map(d => d.cumulativeAmount));
  const minValue = 0; // Always start from 0 for savings
  const valueRange = maxValue - minValue || 1;

  // Generate Y-axis labels with uniform spacing
  const yLabels = [];
  const numLabels = 4; // Always show 4 labels for uniform spacing
  
  for (let i = 0; i < numLabels; i++) {
    const value = minValue + (valueRange * i / (numLabels - 1));
    yLabels.push(value);
  }
  

  const padding = 40;
  const innerWidth = containerWidth - padding * 2;
  const innerHeight = chartHeight - padding * 2;

  if (Platform.OS === 'web') {
    // Generate path data for the cumulative savings line (with padding)
    // Adjust spacing based on container width for optimal use of space
    const isNarrowContainer = containerWidth < 600; // Narrow container (modal/card)
    const maxSpacing = innerWidth * (isNarrowContainer ? 0.95 : 0.85); // Use more width in narrow containers
    const startOffset = innerWidth * (isNarrowContainer ? 0.025 : 0.075); // Less margin in narrow containers
    
    const pathData = displayData.map((data, index) => {
      const x = padding + startOffset + (displayData.length > 1 ? (index / (displayData.length - 1)) * maxSpacing : maxSpacing / 2);
      const y = padding + (innerHeight - ((data.cumulativeAmount - minValue) / valueRange) * innerHeight);
      return { x, y, ...data };
    });


    // Create SVG path string (coordinates already include padding)
    const pathString = pathData.reduce((path, point, index) => {
      if (index === 0) {
        return `M ${point.x},${point.y}`;
      }
      return `${path} L ${point.x},${point.y}`;
    }, '');

    // Create area path string (for gradient fill)
    const areaPathString = pathString + ` L ${pathData[pathData.length - 1].x},${padding + innerHeight} L ${pathData[0].x},${padding + innerHeight} Z`;


    return (
      <div ref={containerRef} className="bg-white rounded-xl p-4 shadow-sm">
        <div className="relative" style={{ width: containerWidth, height: chartHeight }}>
          <svg width={containerWidth} height={chartHeight} className="absolute">
            {/* Grid lines */}
            {yLabels.map((value, index) => {
              const y = padding + innerHeight - (index / (yLabels.length - 1)) * innerHeight;
              return (
                <g key={index}>
                  <line
                    x1={padding}
                    y1={y}
                    x2={containerWidth - padding}
                    y2={y}
                    stroke="#f1f5f9"
                    strokeWidth="1"
                  />
                </g>
              );
            })}
            
            {/* Area gradient */}
            <defs>
              <linearGradient id="savingsGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stopColor="#10b981" stopOpacity="0.3" />
                <stop offset="100%" stopColor="#10b981" stopOpacity="0.05" />
              </linearGradient>
            </defs>
            
            {/* Area fill */}
            {pathString && (
              <path
                d={areaPathString}
                fill="url(#savingsGradient)"
              />
            )}
            
            {/* Line */}
            {pathString && (
              <path
                d={pathString}
                stroke="#10b981"
                strokeWidth="4"
                fill="none"
                strokeLinecap="round"
                strokeLinejoin="round"
              />
            )}
            
            {/* Data points */}
            {pathData.map((point, index) => (
              <g key={index}>
                <circle
                  cx={point.x}
                  cy={point.y}
                  r="6"
                  fill="#10b981"
                  stroke="white"
                  strokeWidth="2"
                  style={{ cursor: 'pointer' }}
                  onMouseEnter={() => setHoveredPoint(index)}
                  onMouseLeave={() => setHoveredPoint(null)}
                />
                {/* Invisible larger circle for easier hover */}
                <circle
                  cx={point.x}
                  cy={point.y}
                  r="12"
                  fill="transparent"
                  style={{ cursor: 'pointer' }}
                  onMouseEnter={() => setHoveredPoint(index)}
                  onMouseLeave={() => setHoveredPoint(null)}
                />
              </g>
            ))}
          </svg>
          
          {/* Y-axis labels */}
          {yLabels.map((value, index) => {
            const y = padding + innerHeight - (index / (yLabels.length - 1)) * innerHeight;
            return (
              <div
                key={index}
                className="absolute text-xs text-secondary-500"
                style={{
                  left: '8px',
                  top: `${y - 8}px`,
                }}
              >
                {formatCurrency(formatWithTwoDecimals(value), currency)}
              </div>
            );
          })}
          
          {/* Tooltips */}
          {hoveredPoint !== null && (
            <div
              className="absolute bg-gray-800 text-white px-3 py-2 rounded-lg shadow-lg text-sm pointer-events-none z-10"
              style={{
                left: `${pathData[hoveredPoint].x - 50}px`,
                top: `${pathData[hoveredPoint].y - 80}px`,
                width: '100px',
              }}
            >
              <div className="text-center">
                <div className="font-semibold">{pathData[hoveredPoint].month}</div>
                <div className="text-green-300">
                  {formatCurrency(formatWithTwoDecimals(pathData[hoveredPoint].monthlyAmount), currency)}
                </div>
                <div className="text-xs text-gray-300">{t('budgetPlans.monthly')}</div>
                <div className="border-t border-gray-600 mt-1 pt-1">
                  <div className="font-semibold text-green-200">
                    {formatCurrency(formatWithTwoDecimals(pathData[hoveredPoint].cumulativeAmount), currency)}
                  </div>
                  <div className="text-xs text-gray-300">{t('budgetPlans.cumulative')}</div>
                </div>
              </div>
              {/* Tooltip arrow */}
              <div 
                className="absolute left-1/2 transform -translate-x-1/2 top-full"
                style={{
                  width: 0,
                  height: 0,
                  borderLeft: '6px solid transparent',
                  borderRight: '6px solid transparent',
                  borderTop: '6px solid #1f2937'
                }}
              />
            </div>
          )}

          {/* X-axis labels - Responsive based on container width */}
          {pathData.map((point, index) => {
            const isMobile = containerWidth < 640;
            const isVerySmall = containerWidth < 480;
            
            // Strategy: On very small containers, show every other month
            // On small containers, use smaller font and tighter spacing
            const shouldShowLabel = !isVerySmall || index % 2 === 0;
            
            if (!shouldShowLabel) return null;
            
            return (
              <div
                key={index}
                className="absolute text-secondary-500 text-center"
                style={{
                  left: `${Math.max(0, Math.min(point.x - (isMobile ? 12 : 15), containerWidth - (isMobile ? 24 : 30)))}px`,
                  top: `${chartHeight - 20}px`,
                  width: isMobile ? '24px' : '30px',
                  fontSize: isVerySmall ? '9px' : isMobile ? '10px' : '12px',
                  transform: isMobile && pathData.length > 4 ? 'rotate(-45deg)' : 'none',
                  transformOrigin: 'center bottom',
                }}
              >
                {point.month}
              </div>
            );
          })}
        </div>
      </div>
    );
  }

  // For mobile, create a line chart with bars and connecting lines
  return (
    <View ref={containerRef} className="bg-white rounded-xl p-4 shadow-sm" onLayout={handleLayout}>
      <View style={{ width: containerWidth, height: chartHeight }}>
        <View className="relative">
          {/* Y-axis labels */}
          <View className="absolute left-0" style={{ height: chartHeight - 40 }}>
            {yLabels.map((value, index) => {
              const y = (index / 3) * (chartHeight - 80) + 20;
              return (
                <View
                  key={index}
                  className="absolute"
                  style={{ top: y - 10 }}
                >
                  <Text className="text-xs text-secondary-500" style={{ fontSize: 10 }}>
                    {formatCurrency(formatWithTwoDecimals(value), currency)}
                  </Text>
                </View>
              );
            })}
          </View>

          {/* Chart area */}
          <View 
            className="ml-10" 
            style={{ 
              width: containerWidth - 60, 
              height: chartHeight - 40,
              position: 'relative'
            }}
          >
            {/* Grid lines */}
            {yLabels.map((_, index) => {
              const y = (index / 3) * (chartHeight - 80) + 20;
              return (
                <View
                  key={index}
                  className="absolute bg-gray-100"
                  style={{
                    top: y,
                    left: 0,
                    right: 0,
                    height: 1,
                  }}
                />
              );
            })}

            {/* Connecting lines between points */}
            <View className="absolute inset-0 pt-5 pb-5">
              {displayData.slice(0, -1).map((_, index) => {
                const currentPoint = displayData[index];
                const nextPoint = displayData[index + 1];
                
                const x1 = (index / (displayData.length - 1 || 1)) * (containerWidth - 80);
                const x2 = ((index + 1) / (displayData.length - 1 || 1)) * (containerWidth - 80);
                const y1 = (chartHeight - 100) - ((currentPoint.cumulativeAmount - minValue) / valueRange) * (chartHeight - 100);
                const y2 = (chartHeight - 100) - ((nextPoint.cumulativeAmount - minValue) / valueRange) * (chartHeight - 100);
                
                const angle = Math.atan2(y2 - y1, x2 - x1) * 180 / Math.PI;
                const length = Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
                
                return (
                  <View
                    key={index}
                    className="absolute bg-green-500"
                    style={{
                      left: x1 + 15, // Center on the point
                      top: y1,
                      width: length,
                      height: 2,
                      transform: [{ rotate: `${angle}deg` }],
                      transformOrigin: '0 0',
                    }}
                  />
                );
              })}
            </View>

            {/* Data points with bars and labels */}
            <View className="flex-row items-end justify-between h-full pt-5 pb-5">
              {displayData.map((point, index) => {
                const barHeight = ((point.cumulativeAmount - minValue) / valueRange) * (chartHeight - 100);
                const hasData = point.cumulativeAmount > 0;
                
                return (
                  <View key={index} className="items-center flex-1" style={{ maxWidth: 40 }}>
                    <View className="items-center mb-2">
                      {hasData && (
                        <>
                          {/* Value labels above points */}
                          <View className="bg-gray-800 px-2 py-1 rounded mb-2 min-w-20">
                            <Text className="text-white text-xs font-semibold text-center">
                              {formatCurrency(formatWithTwoDecimals(point.cumulativeAmount), currency)}
                            </Text>
                            <Text className="text-green-200 text-xs text-center">
                              +{formatCurrency(formatWithTwoDecimals(point.monthlyAmount), currency)}
                            </Text>
                          </View>
                          
                          <LinearGradient
                            colors={['#10b981', '#059669']}
                            style={{
                              width: 6,
                              height: Math.max(4, barHeight),
                              borderRadius: 3,
                            }}
                          />
                          <View className="w-3 h-3 bg-green-500 rounded-full border-2 border-white shadow-sm mt-1" />
                        </>
                      )}
                      {!hasData && (
                        <View className="w-2 h-2 bg-gray-300 rounded-full mt-1" />
                      )}
                    </View>
                  </View>
                );
              })}
            </View>

            {/* X-axis labels */}
            <View className="flex-row justify-between mt-2">
              {displayData.map((point, index) => (
                <View key={index} className="items-center flex-1" style={{ maxWidth: 30 }}>
                  <Text className="text-xs text-secondary-500" style={{ fontSize: 9 }}>
                    {point.month}
                  </Text>
                </View>
              ))}
            </View>
          </View>
        </View>
      </View>
    </View>
  );
}