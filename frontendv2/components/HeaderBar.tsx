import { View, Text, TouchableOpacity, Platform } from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';

type HeaderBarProps = {
  title: string;
  showBackButton?: boolean;
  rightComponent?: React.ReactNode;
};

export function HeaderBar({ title, showBackButton = false, rightComponent }: HeaderBarProps) {
  const router = useRouter();

  return (
    <View className="bg-primary-600 pt-[50px] ios:pt-[50px] android:pt-5 pb-6 shadow-md rounded-b-[20px]">
      <View className="absolute top-14 right-12 w-16 h-16 bg-primary-500/50 rounded-full" />
      <View className="absolute bottom-0 left-0 w-24 h-24 bg-primary-500 rounded-tr-full" />
      
      <View className="flex-row items-center justify-between px-5 z-10">
        <View className="flex-row items-center">
          {showBackButton && (
            <TouchableOpacity
              className="bg-white/20 p-2 rounded-full mr-3"
              onPress={() => router.back()}
              hitSlop={{ top: 15, right: 15, bottom: 15, left: 15 }}
            >
              <Ionicons name="arrow-back" size={20} color="white" />
            </TouchableOpacity>
          )}
          <Text className="text-2xl font-bold text-white">{title}</Text>
        </View>
        
        <View>
          {rightComponent}
        </View>
      </View>
    </View>
  );
}