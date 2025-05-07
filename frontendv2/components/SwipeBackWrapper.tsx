import React, { useEffect } from 'react';
import { StyleSheet, View, Platform } from 'react-native';
import { Gesture, GestureDetector } from 'react-native-gesture-handler';
import Animated, { 
  useAnimatedStyle, 
  useSharedValue, 
  withTiming,
  runOnJS,
  useAnimatedReaction
} from 'react-native-reanimated';
import { useRouter, useNavigation, usePathname } from 'expo-router';

interface SwipeBackWrapperProps {
  children: React.ReactNode;
  threshold?: number; // Minimum distance to trigger navigation
  edgeWidth?: number; // Width of edge area that activates the gesture
  hasScrollView?: boolean; // Flag to indicate the presence of a ScrollView
}

export default function SwipeBackWrapper({ 
  children, 
  threshold = 60, 
  edgeWidth = 160,
  hasScrollView = false
}: SwipeBackWrapperProps) {
  const router = useRouter();
  const navigation = useNavigation<any>();
  const pathname = usePathname();

  // Only enable swipe back on iOS for best UX
  if (Platform.OS !== 'ios') {
    return <>{children}</>;
  }

  const translateX = useSharedValue(0);
  const startX = useSharedValue(0);
  const isActive = useSharedValue(false);

  // Reset the animated value when the component is mounted
  // This ensures when we navigate back to this screen, it's in the correct position
  useEffect(() => {
    translateX.value = 0;
    isActive.value = false;
  }, [pathname]);

  const navigateBack = () => {
    // Get the current path without the (tabs) part
    const cleanPath = pathname.replace(/^\/(tabs)\//, '/');
    
    console.log("Current path:", cleanPath);

    // Handle specific path patterns
    if (cleanPath.match(/^\/envelopes\/[^\/]+$/)) {
      // If we're on envelopes/[uuid], go back to /envelopes
      console.log("Navigating from envelope detail to envelopes list");
      router.push('/envelopes' as any);
      return;
    } 
    
    if (cleanPath.match(/^\/budget-plans\/[^\/]+$/)) {
      // If we're on budget-plans/[uuid], go back to /budget-plans
      console.log("Navigating from budget plan detail to budget plans list");
      router.push('/budget-plans' as any);
      return;
    }
    
    if (cleanPath.match(/^\/profile\/[^\/]+$/)) {
      // If we're on profile/[screen], go back to /profile
      console.log("Navigating from profile detail screen to profile");
      router.push('/profile' as any);
      return;
    }
    
    // If we're in a nested path inside (tabs)
    const segments = cleanPath.split('/').filter(Boolean);
    
    if (segments.length > 1) {
      // Get parent path - this works for nested routes within tab routes
      const parentPath = '/' + segments.slice(0, segments.length - 1).join('/');
      console.log("Navigating to parent path:", parentPath);
      router.push(parentPath as any);
    } else {
      // If on a top-level page like /envelopes, go to dashboard
      console.log("Navigating to dashboard");
      router.push('/' as any);
    }
  };

  const panGesture = Gesture.Pan()
    .onStart((e) => {
      // Only activate if starting from left edge
      if (e.x < edgeWidth) {
        isActive.value = true;
        startX.value = e.x;
      }
    })
    .onUpdate((e) => {
      if (isActive.value) {
        // Calculate the translate distance
        const distance = Math.max(0, e.translationX);
        translateX.value = distance;
      }
    })
    .onEnd(() => {
      if (isActive.value) {
        if (translateX.value > threshold) {
          // Swipe was far enough, complete the animation and navigate back
          translateX.value = withTiming(300, { duration: 200 }, () => {
            runOnJS(navigateBack)();
          });
        } else {
          // Not far enough, reset position
          translateX.value = withTiming(0, { duration: 200 });
        }
        isActive.value = false;
      }
    });

  // Special configuration for when the component contains a ScrollView
  if (hasScrollView) {
    // Make sure we only activate the gesture from the very edge
    // This helps avoid conflicts with the ScrollView's horizontal scrolling
    panGesture.activeOffsetX([-10, 10]); // Only activate when moving more than 10px horizontally
    panGesture.failOffsetY([-20, 20]); // Fail if moving more than 20px vertically first
  }

  const animatedStyle = useAnimatedStyle(() => {
    return {
      transform: [{ translateX: translateX.value }],
    };
  });

  // Listen for navigation events to reset the animation
  useAnimatedReaction(
    () => isActive.value,
    (currentIsActive, previousIsActive) => {
      // If the gesture was active and now isn't, and we didn't trigger navigation
      // (which would happen if translateX.value > threshold),
      // make sure we reset the position
      if (previousIsActive && !currentIsActive && translateX.value < threshold) {
        translateX.value = withTiming(0, { duration: 200 });
      }
    }
  );

  return (
    <View style={styles.container}>
      <GestureDetector gesture={panGesture}>
        <Animated.View style={[styles.content, animatedStyle]}>
          {children}
        </Animated.View>
      </GestureDetector>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  content: {
    flex: 1,
  },
});
