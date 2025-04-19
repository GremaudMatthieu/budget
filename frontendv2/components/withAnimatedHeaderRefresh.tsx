import React, { useRef } from 'react';
import { Animated, View, RefreshControl, StyleSheet } from 'react-native';
import AnimatedHeader from './AnimatedHeader';

// Define the props for the withAnimatedHeaderRefresh HOC
export interface WithAnimatedHeaderRefreshProps {
  title: string;
  subtitle?: string;
  showBackButton?: boolean;
  rightComponent?: React.ReactNode;
  headerHeight?: number;
  onRefresh: () => void;
  refreshing: boolean;
}

/**
 * Higher-order component that adds an animated header to a screen component
 * with pull-to-refresh functionality
 * The header will hide when scrolling down and show when scrolling up
 */
export default function withAnimatedHeaderRefresh<P>(
  WrappedComponent: React.ComponentType<P>,
  headerProps: WithAnimatedHeaderRefreshProps
) {
  // Return a new component with the animated header
  return (props: P) => {
    // Create a ref for the scroll position
    const scrollY = useRef(new Animated.Value(0)).current;
    const headerHeight = headerProps.headerHeight || 125; // Adjusted to match the smaller header

    // Handle scroll events to update the animated value
    const handleScroll = Animated.event(
      [{ nativeEvent: { contentOffset: { y: scrollY } } }],
      { useNativeDriver: false }
    );

    // Calculation for the pull-to-refresh behavior
    const refreshOffset = scrollY.interpolate({
      inputRange: [-100, -50, 0],
      outputRange: [130, 120, headerHeight], // Slightly increase offset when pulling down
      extrapolate: 'clamp',
    });

    return (
      <View style={styles.container}>
        {/* Animated Header */}
        <AnimatedHeader
          title={headerProps.title}
          subtitle={headerProps.subtitle}
          showBackButton={headerProps.showBackButton}
          rightComponent={headerProps.rightComponent}
          scrollY={scrollY}
        />

        {/* Content with ScrollView and RefreshControl */}
        <Animated.ScrollView
          contentContainerStyle={[
            styles.scrollContent,
            { paddingTop: headerHeight } // Padding to account for fixed header
          ]}
          scrollEventThrottle={16}
          onScroll={handleScroll}
          refreshControl={
            <RefreshControl
              refreshing={headerProps.refreshing}
              onRefresh={headerProps.onRefresh}
              // When refreshing, make sure the header is visible
              progressViewOffset={headerHeight}
              colors={['#0284c7', '#0ea5e9']} // Match gradient colors
              tintColor="#0284c7" // iOS
              titleColor="#0284c7" // iOS
              progressBackgroundColor="#ffffff" // Android
            />
          }
        >
          <WrappedComponent {...props} />
        </Animated.ScrollView>
      </View>
    );
  };
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC', // background-subtle
  },
  scrollContent: {
    flexGrow: 1,
    paddingBottom: 30,
    paddingHorizontal: 16, // Default horizontal padding
  },
});