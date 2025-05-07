import React from 'react';
import { View, Text } from 'react-native';
import Svg, { G, Path, Circle, Text as SvgText } from 'react-native-svg';

interface PieChartData {
  name: string;
  value: number;
  color: string;
}

interface BudgetItemPieChartProps {
  data: PieChartData[];
  size?: number;
}

// Custom implementation that works with modern react-native-svg
const BudgetItemPieChart: React.FC<BudgetItemPieChartProps> = ({ data, size = 200 }) => {
  // Filter out zero values
  const filteredData = data.filter(item => item.value > 0);
  
  // Check if there's any data to display
  if (filteredData.length === 0) {
    return (
      <View className="items-center py-4">
        <Text className="text-gray-500">No data to display</Text>
      </View>
    );
  }

  // Calculate total
  const total = filteredData.reduce((sum, item) => sum + item.value, 0);
  
  // Create pie chart paths
  const svgSize = size;
  const centerX = svgSize / 2;
  const centerY = svgSize / 2;
  const radius = svgSize * 0.4; // Outer radius
  const innerRadius = radius * 0.65; // Inner radius for donut style

  // Generate SVG paths for pie slices
  const generatePath = (startAngle: number, endAngle: number): string => {
    const x1 = centerX + radius * Math.cos(startAngle);
    const y1 = centerY + radius * Math.sin(startAngle);
    
    const x2 = centerX + radius * Math.cos(endAngle);
    const y2 = centerY + radius * Math.sin(endAngle);
    
    const x3 = centerX + innerRadius * Math.cos(endAngle);
    const y3 = centerY + innerRadius * Math.sin(endAngle);
    
    const x4 = centerX + innerRadius * Math.cos(startAngle);
    const y4 = centerY + innerRadius * Math.sin(startAngle);

    const largeArcFlag = endAngle - startAngle > Math.PI ? 1 : 0;
    
    // Move to outer edge start, arc to outer edge end, line to inner edge end,
    // arc back to inner edge start, close path
    return `M ${x1} ${y1} A ${radius} ${radius} 0 ${largeArcFlag} 1 ${x2} ${y2} L ${x3} ${y3} A ${innerRadius} ${innerRadius} 0 ${largeArcFlag} 0 ${x4} ${y4} Z`;
  };

  // Calculate positions for each slice
  let startAngle = -Math.PI / 2; // Start from top
  const slices = filteredData.map((item, index) => {
    const angle = (item.value / total) * (2 * Math.PI);
    const endAngle = startAngle + angle;
    
    // Calculate position for label
    const midAngle = startAngle + angle / 2;
    const labelRadius = (radius + innerRadius) / 2;
    const labelX = centerX + labelRadius * Math.cos(midAngle);
    const labelY = centerY + labelRadius * Math.sin(midAngle);
    
    // Create the path for this slice
    const path = generatePath(startAngle, endAngle);
    
    // Save end angle as start for next slice
    const currentStartAngle = startAngle;
    startAngle = endAngle;
    
    return {
      path,
      color: item.color,
      labelX,
      labelY,
      name: item.name,
      value: item.value,
      percent: ((item.value / total) * 100).toFixed(1)
    };
  });

  return (
    <View className="w-full">
      <View className="items-center">
        <Svg width={svgSize} height={svgSize} viewBox={`0 0 ${svgSize} ${svgSize}`}>
          {/* Render pie slices */}
          {slices.map((slice, index) => (
            <G key={index}>
              <Path d={slice.path} fill={slice.color} />
              <SvgText
                fill="#fff"
                fontSize="12"
                fontWeight="bold"
                x={slice.labelX}
                y={slice.labelY}
                textAnchor="middle"
                alignmentBaseline="middle"
              >
                {slice.name.charAt(0)}
              </SvgText>
            </G>
          ))}
          
          {/* Optional: Add a center circle */}
          <Circle cx={centerX} cy={centerY} r={innerRadius * 0.9} fill="#fff" />
        </Svg>
      </View>
      
      {/* Legend */}
      <View className="flex-row justify-center flex-wrap mt-4">
        {filteredData.map((item, index) => (
          <View key={index} className="flex-row items-center mx-3 mb-2">
            <View 
              style={{ backgroundColor: item.color }}
              className="w-3 h-3 rounded-full mr-2"
            />
            <Text className="text-gray-700 text-sm">{item.name} ({((item.value / total) * 100).toFixed(0)}%)</Text>
          </View>
        ))}
      </View>
    </View>
  );
};

export default BudgetItemPieChart;