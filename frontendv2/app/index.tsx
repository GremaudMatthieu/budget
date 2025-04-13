import { View, Text, StyleSheet, TouchableOpacity, ScrollView } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { useRouter } from 'expo-router';
import { useAuth } from '../contexts/AuthContext';
import { useState, useEffect } from 'react';

export default function HomeScreen() {
  const router = useRouter();
  const { user, loading } = useAuth();
  
  useEffect(() => {
    if (!loading && user) {
      // If user is logged in, redirect to envelopes
      router.replace('/envelopes');
    }
  }, [user, loading, router]);

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <Text style={styles.loadingText}>Loading...</Text>
      </View>
    );
  }

  if (user) return null; // Will redirect in useEffect

  return (
    <ScrollView style={styles.container}>
      <StatusBar style="auto" />
      <View style={styles.header}>
        <Text style={styles.title}>GoGoBudgeto</Text>
        <Text style={styles.subtitle}>Manage your budget efficiently</Text>
      </View>
      
      <View style={styles.featureSection}>
        <Text style={styles.sectionTitle}>Smart Budgeting</Text>
        <Text style={styles.sectionContent}>
          Take control of your finances with our envelope-based budgeting system
        </Text>
      </View>
      
      <View style={styles.featureSection}>
        <Text style={styles.sectionTitle}>Visual Tracking</Text>
        <Text style={styles.sectionContent}>
          See where your money goes with intuitive visual reports
        </Text>
      </View>
      
      <View style={styles.featureSection}>
        <Text style={styles.sectionTitle}>Advanced Analytics</Text>
        <Text style={styles.sectionContent}>
          Get insights into your spending patterns and optimize your budget
        </Text>
      </View>
      
      <View style={styles.buttonContainer}>
        <TouchableOpacity 
          style={styles.button}
          onPress={() => router.push('/signup')}
        >
          <Text style={styles.buttonText}>Get Started</Text>
        </TouchableOpacity>
        
        <TouchableOpacity 
          style={[styles.button, styles.secondaryButton]}
          onPress={() => router.push('/signin')}
        >
          <Text style={styles.secondaryButtonText}>Sign In</Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f7fa',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#f5f7fa',
  },
  loadingText: {
    fontSize: 18,
    color: '#4a6fa5',
  },
  header: {
    alignItems: 'center',
    paddingTop: 80,
    paddingBottom: 40,
  },
  title: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#4a6fa5',
    marginBottom: 10,
  },
  subtitle: {
    fontSize: 18,
    color: '#6c757d',
    textAlign: 'center',
    paddingHorizontal: 20,
    marginBottom: 20,
  },
  featureSection: {
    backgroundColor: '#ffffff',
    borderRadius: 10,
    padding: 20,
    marginHorizontal: 20,
    marginBottom: 20,
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 2,
    },
    shadowOpacity: 0.1,
    shadowRadius: 3.84,
    elevation: 5,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#4a6fa5',
    marginBottom: 10,
  },
  sectionContent: {
    fontSize: 16,
    color: '#6c757d',
    lineHeight: 24,
  },
  buttonContainer: {
    marginHorizontal: 20,
    marginVertical: 30,
  },
  button: {
    backgroundColor: '#4a6fa5',
    borderRadius: 10,
    paddingVertical: 15,
    alignItems: 'center',
    marginBottom: 15,
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 2,
    },
    shadowOpacity: 0.1,
    shadowRadius: 3.84,
    elevation: 5,
  },
  buttonText: {
    color: '#ffffff',
    fontSize: 18,
    fontWeight: '600',
  },
  secondaryButton: {
    backgroundColor: 'transparent',
    borderWidth: 2,
    borderColor: '#4a6fa5',
  },
  secondaryButtonText: {
    color: '#4a6fa5',
    fontSize: 18,
    fontWeight: '600',
  },
});