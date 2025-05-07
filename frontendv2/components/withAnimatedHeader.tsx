import React, { useRef } from 'react';
import { Animated, View, StyleSheet, RefreshControl } from 'react-native';
import AnimatedHeader from './AnimatedHeader';

interface AnimatedHeaderLayoutProps {
  children: React.ReactNode;
  title: React.ReactNode | string;  // Permettre un titre personnalisé
  subtitle?: string;
  showBackButton?: boolean;
  rightComponent?: React.ReactNode;
  headerHeight?: number;
  collapsePercentage?: number; // Nouveau paramètre
  onRefresh?: () => void;
  refreshing?: boolean;
  headerContent?: React.ReactNode;
  headerButtons?: React.ReactNode;
  onBack?: () => void;
}

const AnimatedHeaderLayout: React.FC<AnimatedHeaderLayoutProps> = ({
  children,
  title,
  subtitle,
  showBackButton,
  rightComponent,
  headerHeight = 125,
  collapsePercentage = 100, // Valeur par défaut à 100%
  onRefresh,
  refreshing = false,
  headerContent,
  headerButtons,
  onBack,
}) => {
  const scrollY = useRef(new Animated.Value(0)).current;

  // Fix: Use useNativeDriver: true consistently for scroll events
  const handleScroll = Animated.event(
    [{ nativeEvent: { contentOffset: { y: scrollY } } }],
    { useNativeDriver: true }
  );

  return (
    <View style={styles.container}>
      <AnimatedHeader
        title={title}
        subtitle={subtitle}
        showBackButton={showBackButton}
        rightComponent={rightComponent}
        scrollY={scrollY}
        collapsePercentage={collapsePercentage}   
        headerContent={headerContent}
        headerButtons={headerButtons}
        onBack={onBack}
      />

      <Animated.ScrollView
        contentContainerStyle={[
          styles.scrollContent,
          { paddingTop: headerHeight + 24 }
        ]}
        scrollEventThrottle={16}
        onScroll={handleScroll}
        showsVerticalScrollIndicator={false}
        refreshControl={onRefresh ? (
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            progressViewOffset={headerHeight}
            colors={['#0284c7', '#0ea5e9']}
            tintColor="#0284c7"
            titleColor="#0284c7"
            progressBackgroundColor="#ffffff"
          />
        ) : undefined}
      >
        {children}
      </Animated.ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  scrollContent: {
    flexGrow: 1,
    paddingBottom: 30,
    paddingHorizontal: 16,
  },
});

export default AnimatedHeaderLayout;