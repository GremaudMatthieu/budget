import React, { useState, useEffect, useRef, useCallback } from 'react';
import { View, Text, TouchableOpacity, ScrollView, TextInput, ActivityIndicator, Switch, Alert } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useRouter, Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useAuth } from '@/contexts/AuthContext';
import { useUser } from '@/contexts/UserContext';
import { useLanguage } from '@/contexts/LanguageContext';
import { useTranslation } from '@/utils/useTranslation';
import { useSocket } from '@/contexts/SocketContext';
import SelectField from '@/components/inputs/SelectField';
import SwipeBackWrapper from '@/components/SwipeBackWrapper';

export default function AccountSettingsScreen() {
  const router = useRouter();
  const { t } = useTranslation();
  const { language, changeLanguage } = useLanguage();
  const { user, logout } = useAuth();
  const { updateFirstName, updateLastName, updateLanguagePreference, deleteAccount } = useUser();
  const { socket, connected, lastMessage } = useSocket();

  // State for form values
  const [formValues, setFormValues] = useState({
    firstName: user?.firstname || '',
    lastName: user?.lastname || '',
    email: user?.email || '',
    language: language
  });

  // State for editing
  const [editing, setEditing] = useState<string | null>(null);

  // Keep track of original values to detect changes
  const [originalValues, setOriginalValues] = useState({
    firstName: user?.firstname || '',
    lastName: user?.lastname || '',
    email: user?.email || '',
    language: language
  });

  // State to track fields that are being updated
  const [pendingUpdates, setPendingUpdates] = useState<Record<string, boolean>>({});

  // State for tracking delete account process
  const [isDeleting, setIsDeleting] = useState(false);

  // Ref to track if the component is mounted
  const isMounted = useRef(true);

  // Safety timeout to clear pending updates
  useEffect(() => {
    const pendingFields = Object.keys(pendingUpdates);
    if (pendingFields.length > 0) {
      console.log('Setting safety timeout for pending updates:', pendingFields);

      const timeoutId = setTimeout(() => {
        console.log('Safety timeout triggered, clearing all pending updates');
        if (isMounted.current) {
          setPendingUpdates({});
        }
      }, 5000); // 5 seconds safety timeout

      return () => clearTimeout(timeoutId);
    }
  }, [pendingUpdates]);

  // Initialize form values when user data is loaded
  useEffect(() => {
    if (user) {
      const newValues = {
        firstName: user.firstname || '',
        lastName: user.lastname || '',
        email: user.email || '',
        language: user.language || language
      };

      setFormValues(newValues);
      setOriginalValues(newValues);
    }

    return () => {
      isMounted.current = false;
    };
  }, [user, language]);

  // Handle WebSocket reconnection
  useEffect(() => {
    if (lastMessage && lastMessage.type === 'connected') {
      console.log('WebSocket reconnected, clearing any pending updates');
      setPendingUpdates({});
      setIsDeleting(false);
    }
  }, [lastMessage]);

  // Set up WebSocket event listeners
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
            delete newState['firstName'];
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
            delete newState['lastName'];
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
      },
      'UserUpdated': (event) => {
        if (event.aggregateId === user?.uuid) {
          // Update was successful, clear pending state for the field
          setPendingUpdates(prev => {
            const updated = { ...prev };

            if (event.payload.firstName !== undefined) {
              delete updated.firstName;
            }

            if (event.payload.lastName !== undefined) {
              delete updated.lastName;
            }

            if (event.payload.language !== undefined) {
              delete updated.language;
            }

            return updated;
          });
          
          if (editing) {
            setEditing(null);
          }
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
  }, [socket, user, editing]);

  // Handle starting to edit a field
  const handleStartEditing = (field: string) => {
    setEditing(field);
  };

  // Handle canceling edits
  const handleCancel = () => {
    setFormValues(originalValues);
    setEditing(null);
  };

  // Generic function to update a field
  const handleUpdate = async (field: string) => {
    const value = formValues[field as keyof typeof formValues];

    // Validate input
    if (field === 'firstName' || field === 'lastName') {
      if (typeof value === 'string' && value.trim().length === 0) {
        // Show error if available
        Alert.alert(t('errors.validationError'), `${field} cannot be empty`);
        return;
      }

      if (typeof value === 'string' && value.trim().length > 50) {
        // Show error if available
        Alert.alert(t('errors.validationError'), `${field} cannot be longer than 50 characters`);
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
      // Call the appropriate update function based on the field
      switch (field) {
        case 'firstName':
          success = await updateFirstName(value as string);
          break;
        case 'lastName':
          success = await updateLastName(value as string);
          break;
        case 'language':
          // Cast to 'en' | 'fr' type
          const newLanguage = value as 'en' | 'fr';
          success = await updateLanguagePreference(newLanguage);
          
          // Apply language change immediately to the app using our language context
          if (success) {
            await changeLanguage(newLanguage);
          }
          break;
      }

      if (success) {
        // Update original values on success
        setOriginalValues(prev => ({
          ...prev,
          [field]: value
        }));

        // If no WebSocket event comes within 3 seconds, clear the loading state as a fallback
        setTimeout(() => {
          if (isMounted.current) {
            setPendingUpdates(prev => {
              if (prev[field]) {
                console.log(`Fallback timeout for ${field}: clearing pending state`);
                const updated = { ...prev };
                delete updated[field];
                return updated;
              }
              return prev;
            });
          }
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

  // Handle language change
  const handleLanguageChange = async (languageValue: string) => {
    // Cast to 'en' | 'fr' type
    const newLanguage = languageValue as 'en' | 'fr';

    // Update local state first
    setFormValues(prev => ({
      ...prev,
      language: newLanguage
    }));

    // Mark as updating immediately
    setPendingUpdates(prev => ({
      ...prev,
      language: true
    }));

    try {
      // Send update to backend
      const success = await updateLanguagePreference(newLanguage);
      
      // Apply language change immediately to the app using our language context
      if (success) {
        // Update the original values so if user cancels future edits,
        // it will use this as the baseline
        setOriginalValues(prev => ({
          ...prev,
          language: newLanguage
        }));

        // Change app language via the language context
        await changeLanguage(newLanguage);
      } else {
        // If not successful, revert form value
        setFormValues(prev => ({
          ...prev,
          language: originalValues.language
        }));
        
        // And clear pending state
        setPendingUpdates(prev => {
          const updated = { ...prev };
          delete updated.language;
          return updated;
        });
      }
    } catch (error) {
      console.error('Error updating language preference:', error);
      
      // On error, revert to original language in the form
      setFormValues(prev => ({
        ...prev,
        language: originalValues.language
      }));
      
      // And clear pending state
      setPendingUpdates(prev => {
        const updated = { ...prev };
        delete updated.language;
        return updated;
      });
    }
  };

  // Handle deleting account
  const handleDeleteAccountPress = () => {
    Alert.alert(
      t('profile.deleteAccount'),
      t('profile.deleteAccountWarning'),
      [
        { text: t('common.cancel'), style: 'cancel' },
        {
          text: t('profile.confirmDeletion'),
          style: 'destructive',
          onPress: confirmDeleteAccount
        }
      ]
    );
  };

  // Handle confirming account deletion
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
  const isFieldUpdating = (field: string) => {
    return pendingUpdates[field] === true;
  };

  // Language options for the dropdown
  const languageOptions = [
    { id: 'en', name: t('profile.english'), icon: 'language', iconColor: '#0284c7' },
    { id: 'fr', name: t('profile.french'), icon: 'language', iconColor: '#0284c7' }
  ];

  // Render different card types
  const renderCard = (type: string, item: any) => {
    switch (type) {
      case 'header':
        return (
            <View className="mb-6">
              <Text className="text-2xl font-bold text-text-primary mb-1">{t('profile.accountSettings')}</Text>
              <Text className="text-text-secondary">{t('profile.accountSettingsSubtitle')}</Text>
            </View>
        );

      case 'section_header':
        return (
            <View className="mb-4 mt-4">
              <Text className="text-lg font-semibold text-text-primary">{item.title}</Text>
            </View>
        );

      case 'text_field':
        return (
            <View className="card mb-4">
              <View className="card-content">
                <View className="flex-row items-center">
                  <View className="w-10 h-10 rounded-full bg-primary-100 items-center justify-center mr-3">
                    <Ionicons name={item.icon} size={20} color="#0284c7" />
                  </View>
                  <View className="flex-1">
                    <Text className="text-sm text-secondary-600 mb-1">{item.label}</Text>
                    <View className="flex-row items-center">
                      {editing === item.field ? (
                          <View className="flex-1 flex-row items-center">
                            <TextInput
                                className="flex-1 border-b border-primary-300 py-1 text-text-primary"
                                value={formValues[item.field as keyof typeof formValues] as string}
                                onChangeText={(text) => setFormValues(prev => ({ ...prev, [item.field]: text }))}
                                autoFocus
                            />
                            <TouchableOpacity
                                className="ml-2 p-2"
                                onPress={() => handleUpdate(item.field)}
                                disabled={isFieldUpdating(item.field)}
                            >
                              {isFieldUpdating(item.field) ? (
                                <ActivityIndicator size="small" color="#16a34a" />
                              ) : (
                                <Ionicons name="checkmark" size={20} color="#16a34a" />
                              )}
                            </TouchableOpacity>
                            <TouchableOpacity
                                className="p-2"
                                onPress={handleCancel}
                                disabled={isFieldUpdating(item.field)}
                            >
                              <Ionicons name="close" size={20} color="#dc2626" />
                            </TouchableOpacity>
                          </View>
                      ) : (
                          <>
                            <Text className="flex-1 text-text-primary font-medium">
                              {formValues[item.field as keyof typeof formValues]}
                            </Text>
                            {item.editable && (
                                <TouchableOpacity
                                    className="p-2"
                                    onPress={() => handleStartEditing(item.field)}
                                    disabled={isFieldUpdating(item.field)}
                                >
                                  {isFieldUpdating(item.field) ? (
                                      <ActivityIndicator size="small" color="#64748b" />
                                  ) : (
                                      <Ionicons name="create-outline" size={20} color="#64748b" />
                                  )}
                                </TouchableOpacity>
                            )}
                          </>
                      )}
                    </View>
                  </View>
                </View>
              </View>
            </View>
        );

      case 'select_field':
        return (
            <View className="card mb-4">
              <View className="card-content">
                <View className="flex-row items-center">
                  <View className="w-10 h-10 rounded-full bg-primary-100 items-center justify-center mr-3">
                    <Ionicons name={item.icon} size={20} color="#0284c7" />
                  </View>
                  <View className="flex-1">
                    <Text className="text-sm text-secondary-600 mb-2">{t('profile.language')}</Text>
                    <SelectField
                        options={languageOptions}
                        value={formValues.language}
                        onChange={handleLanguageChange}
                        placeholder={t('modals.selectOption')}
                        icon={<Ionicons name="language" size={18} color="#0284c7" />}
                        disabled={isFieldUpdating('language')}
                    />
                    {isFieldUpdating('language') && (
                        <View className="mt-2 flex-row items-center">
                          <ActivityIndicator size="small" color="#0284c7" />
                          <Text className="ml-2 text-secondary-600">{t('profile.updatingLanguage')}</Text>
                        </View>
                    )}
                  </View>
                </View>
              </View>
            </View>
        );

      case 'danger_header':
        return (
            <View className="px-4 pt-4 pb-2">
              <Text className="text-lg font-semibold text-danger-600">{t('profile.dangerZone')}</Text>
            </View>
        );

      case 'danger_button':
        return (
            <View className="card border-danger-200 mb-4">
              <View className="card-content">
                <View className="flex-row items-center">
                  <View className="w-10 h-10 rounded-full bg-danger-100 items-center justify-center mr-3">
                    <Ionicons name={item.icon} size={20} color="#dc2626" />
                  </View>
                  <View className="flex-1">
                    <Text className="text-danger-600 font-medium">{item.title}</Text>
                    <Text className="text-xs text-danger-500 mt-1">{item.description}</Text>
                  </View>
                  <TouchableOpacity 
                    className="py-2 px-4 bg-danger-100 rounded-lg" 
                    onPress={item.onPress}
                    disabled={isDeleting}
                  >
                    {isDeleting ? (
                      <ActivityIndicator size="small" color="#dc2626" />
                    ) : (
                      <Text className="text-danger-600 font-medium">{t('profile.deleteAccount')}</Text>
                    )}
                  </TouchableOpacity>
                </View>
              </View>
            </View>
        );

      default:
        return null;
    }
  };

  // Configure all the cards to display
  const cards = [
    { type: 'header' },
    { type: 'section_header', title: t('profile.accountInformation') },
    {
      type: 'text_field',
      field: 'email',
      label: t('profile.email'),
      icon: 'mail-outline',
      editable: false,
      editing: false,
    },
    {
      type: 'text_field',
      field: 'firstName',
      label: t('profile.firstName'),
      icon: 'person-outline',
      editable: true,
    },
    {
      type: 'text_field',
      field: 'lastName',
      label: t('profile.lastName'),
      icon: 'people-outline',
      editable: true,
    },
    { type: 'section_header', title: t('profile.preferences') },
    {
      type: 'select_field',
      field: 'language',
      icon: 'language',
    },
    { type: 'danger_header' },
    {
      type: 'danger_button',
      title: t('profile.deleteAccount'),
      description: t('profile.deleteAccountWarning'),
      icon: 'trash-outline',
      onPress: handleDeleteAccountPress,
    },
  ];

  return (
      <SwipeBackWrapper>
        <View className="flex-1 bg-background-subtle">
          <StatusBar style="dark" />

          <Stack.Screen
              options={{
                headerShown: false
              }}
          />

          {/* Header */}
          <View className="bg-primary-600 px-6 pt-16 pb-12 rounded-b-3xl shadow-lg">
            <View className="flex-row justify-between items-center mb-4">
              <TouchableOpacity
                  onPress={() => router.back()}
                  className="bg-white/20 p-2 rounded-full"
              >
                <Ionicons name="chevron-back" size={24} color="white" />
              </TouchableOpacity>
              <Text className="text-2xl font-bold text-white">{t('profile.accountSettings')}</Text>
              <View style={{ width: 40 }} />
            </View>
          </View>

          <ScrollView className="flex-1 px-4 pt-6">
            {cards.map((card, index) => (
                <React.Fragment key={index}>
                  {renderCard(card.type, card)}
                </React.Fragment>
            ))}

            {/* Empty space at the bottom */}
            <View className="h-16" />
          </ScrollView>
        </View>
      </SwipeBackWrapper>
  );
}