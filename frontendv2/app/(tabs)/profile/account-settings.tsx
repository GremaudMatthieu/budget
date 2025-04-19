import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  Alert,
  ScrollView
} from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useUser } from '@/contexts/UserContext';
import { useAuth } from '@/contexts/AuthContext';
import { useErrorContext } from '@/contexts/ErrorContext';
import { useSocket } from '@/contexts/SocketContext';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';
import SelectField from '@/components/inputs/SelectField';
import { SelectOption } from '@/components/modals/SelectModal';

interface FormValues {
  firstname: string;
  lastname: string;
  language: 'en' | 'fr';
}

function AccountSettingsContent() {
  const router = useRouter();
  const { user, logout } = useAuth();
  const { setError } = useErrorContext();
  const { socket, lastMessage } = useSocket();
  const { 
    updateFirstName, 
    updateLastName, 
    updateLanguagePreference, 
    deleteAccount, 
    updating 
  } = useUser();

  const [formValues, setFormValues] = useState<FormValues>({
    firstname: user?.firstname || '',
    lastname: user?.lastname || '',
    language: (user?.language as 'en' | 'fr') || 'en',
  });

  const [editing, setEditing] = useState<keyof FormValues | null>(null);
  const [originalValues, setOriginalValues] = useState<FormValues>({
    firstname: user?.firstname || '',
    lastname: user?.lastname || '',
    language: (user?.language as 'en' | 'fr') || 'en',
  });
  
  // Local state to track pending updates
  const [pendingUpdates, setPendingUpdates] = useState<Record<string, boolean>>({});
  
  // Add a safety timeout to clear pending updates
  useEffect(() => {
    const pendingFields = Object.keys(pendingUpdates);
    if (pendingFields.length > 0) {
      console.log('Setting safety timeout for pending updates:', pendingFields);
      
      const timeoutId = setTimeout(() => {
        console.log('Safety timeout triggered, clearing all pending updates');
        setPendingUpdates({});
      }, 5000); // 5 seconds safety timeout
      
      return () => clearTimeout(timeoutId);
    }
  }, [pendingUpdates]);

  // Add local state to track delete account loading state
  const [isDeleting, setIsDeleting] = useState(false);

  // Language options for the selector
  const languageOptions: SelectOption[] = [
    { id: 'en', name: 'English', icon: 'language', iconColor: '#0284c7' },
    { id: 'fr', name: 'Français', icon: 'language', iconColor: '#0284c7' }
  ];

  // Update form values when user data changes
  useEffect(() => {
    if (user) {
      const newValues = {
        firstname: user.firstname || '',
        lastname: user.lastname || '',
        language: (user?.language as 'en' | 'fr') || 'en',
      };
      setFormValues(newValues);
      setOriginalValues(newValues);
    }
  }, [user]);

  // Listen for WebSocket connection events to refresh UI
  useEffect(() => {
    if (lastMessage && lastMessage.type === 'connected') {
      console.log('WebSocket reconnected, clearing any pending updates');
      setPendingUpdates({});
      setIsDeleting(false);
    }
  }, [lastMessage]);

  // Setup WebSocket event listeners for user-related events
  useEffect(() => {
    if (!socket || !user) return;
    
    console.log('Setting up WebSocket event listeners for user updates in account settings');
    
    // Define event handlers for different user events
    const eventHandlers: Record<string, (event: any) => void> = {
      'UserFirstnameChanged': (event) => {
        console.log('User firstname changed event received in account settings:', event);
        
        // Check if this event is for the current user
        if (event.userId === user.uuid || event.aggregateId === user.uuid) {
          console.log('Firstname event is for the current user, clearing pending update');
          // Force clearing the pending state immediately
          setPendingUpdates(prev => {
            const newState = { ...prev };
            delete newState['firstname'];
            console.log('New pending updates state after firstname change:', newState);
            return newState;
          });
          setEditing(null);
        }
      },
      'UserLastnameChanged': (event) => {
        console.log('User lastname changed event received in account settings:', event);
        
        // Check if this event is for the current user
        if (event.userId === user.uuid || event.aggregateId === user.uuid) {
          console.log('Lastname event is for the current user, clearing pending update');
          // Force clearing the pending state immediately
          setPendingUpdates(prev => {
            const newState = { ...prev };
            delete newState['lastname'];
            console.log('New pending updates state after lastname change:', newState);
            return newState;
          });
          setEditing(null);
        }
      },
      'UserLanguagePreferenceChanged': (event) => {
        console.log('User language preference changed event received in account settings:', event);
        
        // Check if this event is for the current user
        if (event.userId === user.uuid || event.aggregateId === user.uuid) {
          console.log('Language event is for the current user, clearing pending update');
          // Force clearing the pending state immediately
          setPendingUpdates(prev => {
            const newState = { ...prev };
            delete newState['language'];
            console.log('New pending updates state after language change:', newState);
            return newState;
          });
        }
      }
    };
    
    // Register event handlers
    for (const [event, handler] of Object.entries(eventHandlers)) {
      socket.on(event, handler);
    }
    
    // Cleanup function
    return () => {
      console.log('Cleaning up WebSocket event listeners for user updates in account settings');
      // Remove all event handlers
      for (const event of Object.keys(eventHandlers)) {
        socket.off(event);
      }
    };
  }, [socket, user]);

  const handleStartEditing = (field: keyof FormValues) => {
    setEditing(field);
  };

  const handleCancel = () => {
    setFormValues(originalValues);
    setEditing(null);
  };

  const handleUpdate = async (field: keyof FormValues) => {
    const value = formValues[field];
    
    // Validate input
    if (field === 'firstname' || field === 'lastname') {
      if (value.trim().length === 0) {
        setError(`${field} cannot be empty`);
        return;
      }
      
      if (value.trim().length > 50) {
        setError(`${field} cannot be longer than 50 characters`);
        return;
      }
    }
    
    // Mark this field as being updated
    setPendingUpdates(prev => ({
      ...prev,
      [field]: true
    }));
    
    let success = false;
    
    try {
      if (field === 'firstname') {
        success = await updateFirstName(value);
      } else if (field === 'lastname') {
        success = await updateLastName(value);
      } else if (field === 'language') {
        success = await updateLanguagePreference(value as 'en' | 'fr');
      }
      
      if (success) {
        // Update original values on success
        setOriginalValues(prev => ({
          ...prev,
          [field]: value
        }));
        
        // If no WebSocket event comes within 3 seconds, clear the loading state as a fallback
        setTimeout(() => {
          setPendingUpdates(prev => {
            if (prev[field]) {
              console.log(`Fallback timeout for ${field}: clearing pending state`);
              const updated = { ...prev };
              delete updated[field];
              return updated;
            }
            return prev;
          });
        }, 3000);
      }
    } catch (error) {
      console.error(`Error updating ${field}:`, error);
      
      // Clear pending state on error
      setPendingUpdates(prev => {
        const updated = { ...prev };
        delete updated[field];
        return updated;
      });
    }
  };

  const handleLanguageChange = async (languageValue: string) => {
    // Cast to 'en' | 'fr' type
    const newLanguage = languageValue as 'en' | 'fr';
    
    // Update local state
    setFormValues(prev => ({
      ...prev,
      language: newLanguage
    }));
    
    // Mark this field as being updated
    setPendingUpdates(prev => ({
      ...prev,
      language: true
    }));
    
    try {
      const success = await updateLanguagePreference(newLanguage);
      if (success) {
        setOriginalValues(prev => ({
          ...prev,
          language: newLanguage
        }));
        
        // If no WebSocket event comes within 3 seconds, clear the loading state as a fallback
        setTimeout(() => {
          setPendingUpdates(prev => {
            if (prev.language) {
              console.log('Fallback timeout for language: clearing pending state');
              const updated = { ...prev };
              delete updated.language;
              return updated;
            }
            return prev;
          });
        }, 3000);
      }
    } catch (error) {
      // Revert on failure
      setFormValues(prev => ({
        ...prev,
        language: formValues.language
      }));
      console.error('Error updating language preference:', error);
      
      // Clear pending state on error
      setPendingUpdates(prev => {
        const updated = { ...prev };
        delete updated.language;
        return updated;
      });
    }
  };

  const handleDeleteAccountPress = () => {
    Alert.alert(
      'Delete Account',
      'Are you sure you want to delete your account? This action cannot be undone.',
      [
        { text: 'Cancel', style: 'cancel' },
        { 
          text: 'Delete', 
          style: 'destructive',
          onPress: confirmDeleteAccount
        }
      ]
    );
  };

  const confirmDeleteAccount = async () => {
    // Set local deleting state
    setIsDeleting(true);
    
    try {
      const success = await deleteAccount();
      if (success) {
        // The logout will be handled by the socket event
        // But add safety timeout in case the WebSocket event doesn't arrive
        setTimeout(() => {
          console.log('Fallback timeout for account deletion: forcing logout');
          setIsDeleting(false);
          if (logout) logout();
        }, 5000);
      } else {
        // Reset loading state if the operation was not successful
        setIsDeleting(false);
      }
    } catch (error) {
      console.error('Error deleting account:', error);
      setIsDeleting(false);
    }
  };

  // Check if a specific field is currently being updated
  const isFieldUpdating = (field: keyof FormValues) => {
    return pendingUpdates[field] === true;
  };

  return (
    <ScrollView className="flex-1">
      <View className="px-4 py-6">
        {/* Account Information Section */}
        <View className="mb-6">
          <Text className="text-xl font-semibold text-secondary-800 mb-4">Account Information</Text>
          
          {/* Email (read-only) */}
          <View className="card mb-4">
            <View className="card-content">
              <Text className="text-sm text-secondary-600 mb-1">Email</Text>
              <View className="flex-row items-center justify-between">
                <Text className="text-lg text-text-primary">{user?.email}</Text>
                <Ionicons name="lock-closed-outline" size={18} color="#94a3b8" />
              </View>
            </View>
          </View>
          
          {/* First Name */}
          <View className="card mb-4">
            <View className="card-content">
              <Text className="text-sm text-secondary-600 mb-1">First Name</Text>
              {editing === 'firstname' ? (
                <View className="flex-row items-center space-x-2">
                  <TextInput
                    value={formValues.firstname}
                    onChangeText={(text) => setFormValues(prev => ({ ...prev, firstname: text }))}
                    className="flex-1 p-2 border border-surface-border rounded-lg bg-white"
                    autoFocus
                  />
                  <TouchableOpacity
                    onPress={() => handleUpdate('firstname')}
                    disabled={isFieldUpdating('firstname')}
                    className="p-2 bg-success-100 rounded-full"
                  >
                    {isFieldUpdating('firstname') ? (
                      <ActivityIndicator size="small" color="#16a34a" />
                    ) : (
                      <Ionicons name="checkmark" size={18} color="#16a34a" />
                    )}
                  </TouchableOpacity>
                  <TouchableOpacity
                    onPress={handleCancel}
                    disabled={isFieldUpdating('firstname')}
                    className="p-2 bg-danger-100 rounded-full"
                  >
                    <Ionicons name="close" size={18} color="#dc2626" />
                  </TouchableOpacity>
                </View>
              ) : (
                <View className="flex-row items-center justify-between">
                  <Text className="text-lg text-text-primary">{formValues.firstname}</Text>
                  <TouchableOpacity
                    onPress={() => handleStartEditing('firstname')}
                    disabled={isFieldUpdating('firstname')}
                    className="p-2"
                  >
                    {isFieldUpdating('firstname') ? (
                      <ActivityIndicator size="small" color="#64748b" />
                    ) : (
                      <Ionicons name="create-outline" size={18} color="#64748b" />
                    )}
                  </TouchableOpacity>
                </View>
              )}
            </View>
          </View>
          
          {/* Last Name */}
          <View className="card mb-4">
            <View className="card-content">
              <Text className="text-sm text-secondary-600 mb-1">Last Name</Text>
              {editing === 'lastname' ? (
                <View className="flex-row items-center space-x-2">
                  <TextInput
                    value={formValues.lastname}
                    onChangeText={(text) => setFormValues(prev => ({ ...prev, lastname: text }))}
                    className="flex-1 p-2 border border-surface-border rounded-lg bg-white"
                    autoFocus
                  />
                  <TouchableOpacity
                    onPress={() => handleUpdate('lastname')}
                    disabled={isFieldUpdating('lastname')}
                    className="p-2 bg-success-100 rounded-full"
                  >
                    {isFieldUpdating('lastname') ? (
                      <ActivityIndicator size="small" color="#16a34a" />
                    ) : (
                      <Ionicons name="checkmark" size={18} color="#16a34a" />
                    )}
                  </TouchableOpacity>
                  <TouchableOpacity
                    onPress={handleCancel}
                    disabled={isFieldUpdating('lastname')}
                    className="p-2 bg-danger-100 rounded-full"
                  >
                    <Ionicons name="close" size={18} color="#dc2626" />
                  </TouchableOpacity>
                </View>
              ) : (
                <View className="flex-row items-center justify-between">
                  <Text className="text-lg text-text-primary">{formValues.lastname}</Text>
                  <TouchableOpacity
                    onPress={() => handleStartEditing('lastname')}
                    disabled={isFieldUpdating('lastname')}
                    className="p-2"
                  >
                    {isFieldUpdating('lastname') ? (
                      <ActivityIndicator size="small" color="#64748b" />
                    ) : (
                      <Ionicons name="create-outline" size={18} color="#64748b" />
                    )}
                  </TouchableOpacity>
                </View>
              )}
            </View>
          </View>
        </View>
        
        {/* Preferences Section */}
        <View className="mb-6">
          <Text className="text-xl font-semibold text-secondary-800 mb-4">Preferences</Text>
          
          {/* Language Selector */}
          <View className="card mb-4">
            <View className="card-content">
              <Text className="text-sm text-secondary-600 mb-2">Language</Text>
              <SelectField
                options={languageOptions}
                value={formValues.language}
                onChange={handleLanguageChange}
                placeholder="Select language"
                icon={<Ionicons name="language" size={18} color="#0284c7" />}
                disabled={isFieldUpdating('language')}
              />
              {isFieldUpdating('language') && (
                <View className="mt-2 flex-row items-center">
                  <ActivityIndicator size="small" color="#0284c7" />
                  <Text className="ml-2 text-secondary-600">Updating language preference...</Text>
                </View>
              )}
            </View>
          </View>
        </View>
        
        {/* Danger Zone */}
        <View>
          <Text className="text-xl font-semibold text-danger-600 mb-4">Danger Zone</Text>
          
          <View className="card bg-danger-50 mb-4">
            <View className="card-content">
              <View className="flex-row items-center justify-between">
                <View>
                  <Text className="text-lg text-danger-800">Delete Account</Text>
                  <Text className="text-sm text-danger-600">
                    This action cannot be undone. All your data will be permanently deleted.
                  </Text>
                </View>
                <TouchableOpacity
                  onPress={handleDeleteAccountPress}
                  disabled={isDeleting}
                  className="p-3 bg-danger-600 rounded-lg"
                >
                  {isDeleting ? (
                    <ActivityIndicator size="small" color="#ffffff" />
                  ) : (
                    <Text className="text-white font-medium">Delete</Text>
                  )}
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </View>
      </View>
    </ScrollView>
  );
}

export default function AccountSettingsScreen() {
  return (
    <AnimatedHeaderLayout
      title="Account Settings"
      subtitle="Manage your personal information"
      headerHeight={130}
    >
      <AccountSettingsContent />
    </AnimatedHeaderLayout>
  );
}