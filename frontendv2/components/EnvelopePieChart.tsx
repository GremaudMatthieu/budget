import React from 'react';
import { View } from 'react-native';
import Svg, { Circle, G, Text as SvgText } from 'react-native-svg';

interface EnvelopePieChartProps {
  currentAmount: number;
  targetedAmount: number;
}

const EnvelopePieChart: React.FC<EnvelopePieChartProps> = ({ 
  currentAmount, 
  targetedAmount 
}) => {
  // Calculate the percentage
  const percentage = Math.min(100, Math.round((currentAmount / targetedAmount) * 100));
  
  // SVG parameters
  const size = 60;
  const strokeWidth = 8;
  const radius = (size - strokeWidth) / 2;
  const circumference = radius * 2 * Math.PI;
  const strokeDashoffset = circumference - (percentage / 100) * circumference;
  
  // Determine color based on percentage
  let progressColor = "#64748b"; // default gray (secondary-500)
  
  if (percentage >= 100) {
    progressColor = "#16a34a"; // success-600
  } else if (percentage >= 70) {
    progressColor = "#0284c7"; // primary-600
  } else if (percentage >= 30) {
    progressColor = "#0ea5e9"; // primary-500
  } else if (percentage > 0) {
    progressColor = "#7c3aed"; // accent-600
  }

  return (
    <View className="items-center justify-center">
      <Svg width={size} height={size} viewBox={`0 0 ${size} ${size}`}>
        {/* Background circle */}
        <Circle
          cx={size / 2}
          cy={size / 2}
          r={radius}
          stroke="#e2e8f0" // surface-border
          strokeWidth={strokeWidth}
          fill="transparent"
        />
        
        {/* Progress circle */}
        <Circle
          cx={size / 2}
          cy={size / 2}
          r={radius}
          stroke={progressColor}
          strokeWidth={strokeWidth}
          fill="transparent"
          strokeDasharray={circumference}
          strokeDashoffset={strokeDashoffset}
          strokeLinecap="round"
          transform={`rotate(-90, ${size / 2}, ${size / 2})`}
        />

        {/* Percentage text */}
        <G>
          <SvgText
            x={size / 2}
            y={size / 2 + 4}
            fontSize="12"
            fontWeight="bold"
            fill="#334155" // text-primary
            textAnchor="middle"
          >
            {`${percentage}%`}
          </SvgText>
        </G>
      </Svg>
    </View>
  );
};

export default EnvelopePieChart;