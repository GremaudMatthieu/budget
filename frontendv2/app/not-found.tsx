import React, { useEffect, useState, useRef } from 'react';
import { View, Text, Platform, Animated, Pressable, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { useTranslation } from '@/utils/useTranslation';

export default function NotFound() {
  const router = useRouter();
  const { t } = useTranslation();
  const [mounted, setMounted] = useState(false);

  // Animation values
  const fadeInAnim = useRef(new Animated.Value(0)).current;
  const slideUpAnim = useRef(new Animated.Value(50)).current;
  const scaleAnim = useRef(new Animated.Value(0.8)).current;
  const floatingAnim1 = useRef(new Animated.Value(0)).current;
  const floatingAnim2 = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    setMounted(true);
    const nativeDriver = Platform.OS !== 'web';

    // Start entrance animations
    Animated.parallel([
      Animated.timing(fadeInAnim, {
        toValue: 1,
        duration: 800,
        useNativeDriver: nativeDriver,
      }),
      Animated.timing(slideUpAnim, {
        toValue: 0,
        duration: 600,
        useNativeDriver: nativeDriver,
      }),
      Animated.spring(scaleAnim, {
        toValue: 1,
        tension: 100,
        friction: 8,
        useNativeDriver: nativeDriver,
      }),
    ]).start();

    const createFloatingAnimation = (animValue: Animated.Value, delay: number) => {
      return Animated.loop(
          Animated.sequence([
            Animated.timing(animValue, {
              toValue: 1,
              duration: 2000,
              delay,
              useNativeDriver: nativeDriver,
            }),
            Animated.timing(animValue, {
              toValue: 0,
              duration: 2000,
              useNativeDriver: nativeDriver,
            }),
          ])
      );
    };

    createFloatingAnimation(floatingAnim1, 0).start();
    createFloatingAnimation(floatingAnim2, 1000).start();
  }, []);

  const handleGoHome = () => {
    if (Platform.OS === 'web') {
      router.replace('/(tabs)');
    } else {
      router.back();
    }
  };

  const handleGoBack = () => {
    if (router.canGoBack()) {
      router.back();
    } else {
      router.replace('/(tabs)');
    }
  };

  if (!mounted) {
    return (
        <View style={styles.container}>
          <View style={styles.centered}>
            <Animated.View
                style={[
                  styles.loadingBubble,
                  {
                    opacity: fadeInAnim,
                    transform: [{ scale: scaleAnim }],
                  },
                ]}
            />
          </View>
        </View>
    );
  }

  return (
      <View style={styles.container}>
        {Platform.OS !== 'web' && <View style={{ height: 48 }} />}

        <Animated.View
            style={[
              styles.content,
              {
                opacity: fadeInAnim,
                transform: [{ translateY: slideUpAnim }],
              },
            ]}
        >
          <View style={styles.iconWrapper}>
            <Animated.View
                style={[
                  styles.iconBackground,
                  { transform: [{ scale: scaleAnim }] },
                ]}
            >
              <View style={styles.iconInner}>
                <Ionicons name="help-circle-outline" size={48} color="#0284c7" />
              </View>
            </Animated.View>

            <Animated.View
                style={[
                  styles.floating1,
                  {
                    opacity: floatingAnim1.interpolate({
                      inputRange: [0, 1],
                      outputRange: [0.3, 0.8],
                    }),
                    transform: [
                      {
                        translateY: floatingAnim1.interpolate({
                          inputRange: [0, 1],
                          outputRange: [0, -8],
                        }),
                      },
                    ],
                  },
                ]}
            />

            <Animated.View
                style={[
                  styles.floating2,
                  {
                    opacity: floatingAnim2.interpolate({
                      inputRange: [0, 1],
                      outputRange: [0.2, 0.6],
                    }),
                    transform: [
                      {
                        translateY: floatingAnim2.interpolate({
                          inputRange: [0, 1],
                          outputRange: [0, 6],
                        }),
                      },
                    ],
                  },
                ]}
            />
          </View>

          <Animated.Text style={[styles.errorCode, { transform: [{ scale: scaleAnim }] }]}>
            404
          </Animated.Text>

          <Text style={styles.title}>{t('notFound.title')}</Text>
          <Text style={styles.subtitle}>{t('notFound.subtitle')}</Text>

          <View style={styles.suggestionsBox}>
            <Text style={styles.suggestionsTitle}>{t('notFound.suggestions')}</Text>
            {[
              t('notFound.checkUrl'),
              t('notFound.tryAgain'),
              t('notFound.contactSupport'),
            ].map((text, index) => (
                <View key={index} style={styles.bulletItem}>
                  <View style={styles.bulletDot} />
                  <Text style={styles.bulletText}>{text}</Text>
                </View>
            ))}
          </View>

          <View style={{ width: '100%', gap: 12 }}>
            <Pressable onPress={handleGoHome} style={styles.primaryBtn}>
              <Ionicons name="home" size={20} color="white" />
              <Text style={styles.primaryBtnText}>{t('notFound.goHome')}</Text>
            </Pressable>

            <Pressable onPress={handleGoBack} style={styles.secondaryBtn}>
              <Ionicons name="arrow-back" size={20} color="#475569" />
              <Text style={styles.secondaryBtnText}>{t('notFound.goBack')}</Text>
            </Pressable>
          </View>
        </Animated.View>

        {Platform.OS !== 'web' && <View style={{ height: 32 }} />}
      </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8fafc',
  },
  centered: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  loadingBubble: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#bae6fd',
  },
  content: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
    maxWidth: 600,
    alignSelf: 'center',
  },
  iconWrapper: {
    position: 'relative',
    marginBottom: 32,
  },
  iconBackground: {
    width: 128,
    height: 128,
    borderRadius: 64,
    backgroundColor: '#ecfeff',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 4,
    borderColor: '#cffafe',
  },
  iconInner: {
    width: 96,
    height: 96,
    borderRadius: 48,
    backgroundColor: '#a5f3fc',
    justifyContent: 'center',
    alignItems: 'center',
  },
  floating1: {
    position: 'absolute',
    top: -8,
    right: -8,
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: '#ddd6fe',
  },
  floating2: {
    position: 'absolute',
    bottom: -4,
    left: -12,
    width: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: '#fcd34d',
  },
  errorCode: {
    fontSize: 48,
    fontWeight: 'bold',
    color: '#0284c7',
    marginBottom: 8,
  },
  title: {
    fontSize: 20,
    fontWeight: '600',
    color: '#1e293b',
    marginBottom: 8,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 14,
    color: '#64748b',
    textAlign: 'center',
    marginBottom: 24,
    lineHeight: 20,
  },
  suggestionsBox: {
    width: '100%',
    backgroundColor: '#f1f5f9',
    borderRadius: 12,
    padding: 20,
    marginBottom: 32,
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  suggestionsTitle: {
    fontSize: 13,
    fontWeight: '500',
    color: '#1e293b',
    marginBottom: 12,
  },
  bulletItem: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  bulletDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#0ea5e9',
    marginRight: 12,
  },
  bulletText: {
    fontSize: 13,
    color: '#64748b',
    flex: 1,
  },
  primaryBtn: {
    flexDirection: 'row',
    backgroundColor: '#0284c7',
    borderRadius: 8,
    paddingVertical: 12,
    paddingHorizontal: 16,
    justifyContent: 'center',
    alignItems: 'center',
  },
  primaryBtnText: {
    color: 'white',
    fontWeight: '600',
    marginLeft: 8,
  },
  secondaryBtn: {
    flexDirection: 'row',
    borderColor: '#94a3b8',
    borderWidth: 1,
    borderRadius: 8,
    paddingVertical: 12,
    paddingHorizontal: 16,
    justifyContent: 'center',
    alignItems: 'center',
  },
  secondaryBtnText: {
    color: '#1e293b',
    fontWeight: '600',
    marginLeft: 8,
  },
});
