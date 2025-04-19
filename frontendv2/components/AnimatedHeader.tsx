import React, { useRef, useEffect } from 'react';
import { Animated, View, Text, TouchableOpacity, StyleSheet, Platform } from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { StatusBar } from 'expo-status-bar';
import { LinearGradient } from 'expo-linear-gradient';

interface AnimatedHeaderProps {
  title: React.ReactNode | string;
  subtitle?: string;
  showBackButton?: boolean;
  rightComponent?: React.ReactNode;
  scrollY: Animated.Value;
  headerContent?: React.ReactNode;
  headerButtons?: React.ReactNode;
  collapsePercentage?: number;
}

export default function AnimatedHeader({
  title,
  subtitle,
  showBackButton = false,
  rightComponent,
  scrollY,
  headerContent,
  headerButtons,
  collapsePercentage
}: AnimatedHeaderProps) {
  const router = useRouter();

  // Header animation values
  const headerHeight = useRef(new Animated.Value(0)).current;
  const headerOpacity = useRef(new Animated.Value(1)).current;
  const textScale = useRef(new Animated.Value(1)).current;

  // Set up animations based on scroll position
  useEffect(() => {
    const headerScrollListener = scrollY.addListener(({ value }) => {
      // Get current header state
      const currentHeight = headerHeight.__getValue();

      // Determine if we should show or hide the header
      const shouldShowHeader = value <= 0 || value <= currentHeight;
      const newHeight = shouldShowHeader ? 0 : 1;

      // Only animate if the state is changing
      if (newHeight !== currentHeight) {
        // Animate the header height
        Animated.spring(headerHeight, {
          toValue: newHeight,
          useNativeDriver: false,
          tension: 80,
          friction: 10,
        }).start();

        // Animate header opacity and text scale
        Animated.parallel([
          Animated.timing(headerOpacity, {
            toValue: shouldShowHeader ? 1 : Math.max(0.92, (100 - collapsePercentage) / 100),
            duration: 250,
            useNativeDriver: true,
          }),
          Animated.spring(textScale, {
            toValue: shouldShowHeader ? 1 : Math.max(0.95, (100 - collapsePercentage) / 100),
            useNativeDriver: true,
            tension: 80,
            friction: 10,
          })
        ]).start();
      }
    });

    return () => {
      scrollY.removeListener(headerScrollListener);
    };
  }, [scrollY, headerHeight, headerOpacity, textScale, collapsePercentage]);

  // Calculate header transforms with collapse percentage
  const headerTranslate = headerHeight.interpolate({
    inputRange: [0, 1],
    outputRange: [0, -225 * (collapsePercentage / 100)],
    extrapolate: 'clamp',
  });

  // Additional fancy animations
  const bgOpacity = scrollY.interpolate({
    inputRange: [-50, 0, 50, 100],
    outputRange: [1.2, 1, 0.98, 0.95],
    extrapolate: 'clamp',
  });

  const shadowOpacity = scrollY.interpolate({
    inputRange: [0, 20, 40],
    outputRange: [0.15, 0.2, 0.25],
    extrapolate: 'clamp',
  });

  return (
    <Animated.View
      style={[
        styles.headerContainer,
        {
          transform: [{ translateY: headerTranslate }],
          opacity: headerOpacity,
          shadowOpacity: shadowOpacity,
        }
      ]}
    >
      <StatusBar style="light" />

      {/* Modern gradient background */}
      <Animated.View style={[styles.gradientContainer, { opacity: bgOpacity }]}>
        <LinearGradient
          colors={['#0284c7', '#0ea5e9', '#38bdf8']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.gradient}
        />
      </Animated.View>

      {/* Background design elements */}
      <View style={styles.bgCircle1} />
      <View style={styles.bgCircle2} />
      <View style={styles.bgCircle3} />

      <View style={styles.headerContent}>
        <View style={styles.headerRow}>
          {showBackButton && (
            <TouchableOpacity
              style={styles.backButton}
              onPress={() => router.back()}
              hitSlop={{ top: 15, right: 15, bottom: 15, left: 15 }}
            >
              <Ionicons name="chevron-back" size={26} color="white" />
            </TouchableOpacity>
          )}

          <Animated.View
            style={[
              styles.titleContainer,
              { transform: [{ scale: textScale }] }
            ]}
          >
            {typeof title === 'string' ? (
              <Text style={styles.title}>{title}</Text>
            ) : (
              title
            )}
            {subtitle && <Text style={styles.subtitle}>{subtitle}</Text>}
          </Animated.View>

          {rightComponent && (
            <View style={styles.rightComponent}>
              {rightComponent}
            </View>
          )}
        </View>

        {headerContent && (
          <View style={styles.headerContentContainer}>
            {headerContent}
          </View>
        )}

        {headerButtons && (
          <View style={styles.headerButtonsContainer}>
            {headerButtons}
          </View>
        )}
      </View>
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  headerContainer: {
    paddingTop: Platform.OS === 'ios' ? 50 : 22,
    paddingBottom: 16,
    borderBottomLeftRadius: 28,
    borderBottomRightRadius: 28,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 3 },
    shadowRadius: 8,
    elevation: 10,
    zIndex: 10,
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
  },
  gradientContainer: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    borderBottomLeftRadius: 28,
    borderBottomRightRadius: 28,
  },
  gradient: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    borderBottomLeftRadius: 15,
    borderBottomRightRadius: 15,
  },
  headerContent: {
    paddingHorizontal: 20,
    paddingBottom: 4,
    zIndex: 1,
  },
  headerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 8,
  },
  backButton: {
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    borderRadius: 9999,
    padding: 8,
    marginRight: 15,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
    elevation: 3,
  },
  titleContainer: {
    flex: 1,
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    color: 'white',
    letterSpacing: 0.5,
    textShadowColor: 'rgba(0, 0, 0, 0.1)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 2,
  },
  subtitle: {
    fontSize: 16,
    color: 'rgba(255, 255, 255, 0.9)',
    marginTop: 4,
  },
  rightComponent: {
    marginLeft: 15,
  },
  bgCircle1: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    width: 110,
    height: 110,
    backgroundColor: 'rgba(255, 255, 255, 0.08)',
    borderTopRightRadius: 9999,
  },
  bgCircle2: {
    position: 'absolute',
    top: 48,
    right: 32,
    width: 70,
    height: 70,
    backgroundColor: 'rgba(255, 255, 255, 0.12)',
    borderRadius: 9999,
  },
  bgCircle3: {
    position: 'absolute',
    bottom: 30,
    right: 80,
    width: 40,
    height: 40,
    backgroundColor: 'rgba(255, 255, 255, 0.05)',
    borderRadius: 9999,
  },
  headerContentContainer: {
    marginTop: 10,
  },
  headerButtonsContainer: {
    marginTop: 10,
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
});