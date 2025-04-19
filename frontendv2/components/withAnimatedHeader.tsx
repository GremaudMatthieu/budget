import React, { useRef } from 'react';
import { Animated, View, StyleSheet, RefreshControl } from 'react-native';
import AnimatedHeader from './AnimatedHeader';

interface AnimatedHeaderLayoutProps {
  children: React.ReactNode;
  title: string;
  subtitle?: string;
  showBackButton?: boolean;
  rightComponent?: React.ReactNode;
  headerHeight?: number;
  onRefresh?: () => void;
  refreshing?: boolean;
}

const AnimatedHeaderLayout: React.FC<AnimatedHeaderLayoutProps> = ({
  children,
  title,
  subtitle,
  showBackButton,
  rightComponent,
  headerHeight = 125,
  onRefresh,
  refreshing = false,
}) => {
  const scrollY = useRef(new Animated.Value(0)).current;

  const handleScroll = Animated.event(
    [{ nativeEvent: { contentOffset: { y: scrollY } } }],
    { useNativeDriver: false }
  );

  return (
    <View style={styles.container}>
      <AnimatedHeader
        title={title}
        subtitle={subtitle}
        showBackButton={showBackButton}
        rightComponent={rightComponent}
        scrollY={scrollY}
      />

      <Animated.ScrollView
        contentContainerStyle={[
          styles.scrollContent,
          { paddingTop: headerHeight }
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