import { View, Text, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

type ErrorMessageProps = {
  message: string;
  onDismiss?: () => void;
};

export function ErrorMessage({ message, onDismiss }: ErrorMessageProps) {
  return (
    <View className="bg-danger-50 p-4 rounded-xl mb-5 border border-danger-200 flex-row items-center shadow-sm">
      <View className="bg-danger-100 rounded-full p-2 mr-3">
        <Ionicons name="alert-circle" size={20} color="#dc2626" />
      </View>
      <Text className="text-danger-800 flex-1 font-medium">{message}</Text>
      {onDismiss && (
        <TouchableOpacity 
          onPress={onDismiss} 
          className="ml-2 bg-danger-100 w-7 h-7 rounded-full items-center justify-center"
          hitSlop={{ top: 10, right: 10, bottom: 10, left: 10 }}
        >
          <Ionicons name="close" size={16} color="#dc2626" />
        </TouchableOpacity>
      )}
    </View>
  );
}