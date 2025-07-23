import React from 'react';
import { View, Text, Modal, TouchableOpacity, ScrollView, KeyboardAvoidingView, Platform, TouchableWithoutFeedback, Dimensions } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

interface ResponsiveModalProps {
  visible: boolean;
  onClose: () => void;
  title: string;
  children: React.ReactNode;
  size?: 'sm' | 'md' | 'lg' | 'full';
  showCloseButton?: boolean;
  scrollable?: boolean;
}

const ResponsiveModal: React.FC<ResponsiveModalProps> = ({
  visible,
  onClose,
  title,
  children,
  size = 'md',
  showCloseButton = true,
  scrollable = true
}) => {
  const screenWidth = Dimensions.get('window').width;
  const isWeb = Platform.OS === 'web';
  const isTablet = screenWidth > 768;

  // Web-specific modal sizes
  const getWebModalWidth = () => {
    switch (size) {
      case 'sm': return 'max-w-md'; // ~448px
      case 'md': return 'max-w-lg'; // ~512px  
      case 'lg': return 'max-w-2xl'; // ~672px
      case 'full': return 'max-w-full';
      default: return 'max-w-lg';
    }
  };

  const getWebModalClass = () => {
    if (!isWeb) return '';
    return `${getWebModalWidth()} mx-auto my-8 rounded-2xl shadow-2xl`;
  };

  const Content = ({ children }: { children: React.ReactNode }) => {
    if (scrollable) {
      return (
        <ScrollView 
          className="flex-1" 
          keyboardShouldPersistTaps="handled" 
          showsVerticalScrollIndicator={false}
          style={isWeb ? { maxHeight: 'calc(100vh - 8rem)' } : {}}
        >
          {children}
        </ScrollView>
      );
    }
    return <View className="flex-1">{children}</View>;
  };

  if (isWeb) {
    return (
      <Modal
        visible={visible}
        transparent
        animationType="fade"
        onRequestClose={onClose}
        accessibilityViewIsModal
      >
        <div 
          className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
          onClick={onClose}
        >
          <div 
            className={`bg-white rounded-2xl shadow-2xl ${getWebModalWidth()} w-full max-h-[90vh] flex flex-col`}
            onClick={(e) => e.stopPropagation()}
          >
            {/* Header */}
            <div className="flex items-center justify-between p-6 border-b border-gray-100 flex-shrink-0">
              <h2 className="text-xl font-semibold text-gray-900">{title}</h2>
              {showCloseButton && (
                <button
                  onClick={onClose}
                  className="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                  aria-label="Close modal"
                >
                  <Ionicons name="close" size={20} color="currentColor" />
                </button>
              )}
            </div>

            {/* Content */}
            <div className="flex-1 overflow-hidden">
              {scrollable ? (
                <div className="p-6 overflow-y-auto max-h-full">
                  {children}
                </div>
              ) : (
                <div className="p-6">
                  {children}
                </div>
              )}
            </div>
          </div>
        </div>
      </Modal>
    );
  }

  // Mobile layout
  return (
    <Modal
      visible={visible}
      transparent
      animationType="slide"
      onRequestClose={onClose}
      accessibilityViewIsModal
    >
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        className="flex-1 justify-end"
      >
        <TouchableWithoutFeedback onPress={onClose} accessible={false}>
          <View className="flex-1 bg-black/50" />
        </TouchableWithoutFeedback>
        
        <View className="bg-white rounded-t-3xl px-6 pt-6 pb-10 max-h-[90%]">
          {/* Header */}
          <View className="flex-row justify-between items-center mb-6">
            <Text className="text-xl font-bold" accessibilityRole="header">
              {title}
            </Text>
            {showCloseButton && (
              <TouchableOpacity onPress={onClose} className="p-2" accessibilityLabel="Close">
                <Ionicons name="close" size={24} color="#666" />
              </TouchableOpacity>
            )}
          </View>

          {/* Content */}
          <Content>
            {children}
          </Content>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
};

export default ResponsiveModal;