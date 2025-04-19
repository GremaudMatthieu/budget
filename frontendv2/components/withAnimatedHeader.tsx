import React, { useRef } from 'react';
import { Animated, View, StyleSheet } from 'react-native';
import AnimatedHeader from './AnimatedHeader';

// Define the props for the withAnimatedHeader HOC
export interface WithAnimatedHeaderProps {
  title: string;
  subtitle?: string;
  showBackButton?: boolean;
  rightComponent?: React.ReactNode;
  headerHeight?: number;
}

/**
 * Higher-order component that adds an animated header to a screen component
 * The header will hide when scrolling down and show when scrolling up
 */
export default function withAnimatedHeader<P>(
  WrappedComponent: React.ComponentType<P>,
  headerProps: WithAnimatedHeaderProps
) {
  // Return a new component with the animated header
  return (props: P) => {
    // Create a ref for the scroll position
    const scrollY = useRef(new Animated.Value(0)).current;
    const headerHeight = headerProps.headerHeight || 125; // Adjusted header height

    // Handle scroll events to update the animated value
    const handleScroll = Animated.event(
      [{ nativeEvent: { contentOffset: { y: scrollY } } }],
      { useNativeDriver: false }
    );

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

        {/* Content with ScrollView */}
        <Animated.ScrollView
          contentContainerStyle={[
            styles.scrollContent,
            { paddingTop: headerHeight } // Padding to account for fixed header
          ]}
          scrollEventThrottle={16}
          onScroll={handleScroll}
          showsVerticalScrollIndicator={false} // Hide scrollbar for cleaner look
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