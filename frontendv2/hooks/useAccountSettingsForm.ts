import { useState, useRef, useEffect } from 'react';
import { Alert } from 'react-native';
import { useUser } from '@/contexts/UserContext';
import { useAuth } from '@/contexts/AuthContext';
import { useLanguage } from '@/contexts/LanguageContext';
import { useTranslation } from '@/utils/useTranslation';

/**
 * Custom hook for managing account settings form state and logic.
 */
export function useAccountSettingsForm() {
  const { user, logout } = useAuth();
  const { updateFirstName, updateLastName, updateLanguagePreference, deleteAccount } = useUser();
  const { language, changeLanguage } = useLanguage();
  const { t } = useTranslation();

  const [formValues, setFormValues] = useState({
    firstName: user?.firstname || '',
    lastName: user?.lastname || '',
    email: user?.email || '',
    language: language
  });
  const [editing, setEditing] = useState<string | null>(null);
  const [originalValues, setOriginalValues] = useState(formValues);
  const [pendingUpdates, setPendingUpdates] = useState<Record<string, boolean>>({});
  const [isDeleting, setIsDeleting] = useState(false);
  const isMounted = useRef(true);

  useEffect(() => {
    if (user) {
      const newValues = {
        firstName: user.firstname || '',
        lastName: user.lastname || '',
        email: user.email || '',
        language: language
      };
      setFormValues(newValues);
      setOriginalValues(newValues);
    }
    return () => { isMounted.current = false; };
  }, [user, language]);

  const handleStartEditing = (field: string) => setEditing(field);
  const handleCancel = () => { setFormValues(originalValues); setEditing(null); };

  const handleUpdate = async (field: string) => {
    const value = formValues[field as keyof typeof formValues];
    if (field === 'firstName' || field === 'lastName') {
      if (typeof value === 'string' && value.trim().length === 0) {
        Alert.alert(t('errors.validationError'), `${field} cannot be empty`);
        return;
      }
      if (typeof value === 'string' && value.trim().length > 50) {
        Alert.alert(t('errors.validationError'), `${field} cannot be longer than 50 characters`);
        return;
      }
    }
    setPendingUpdates(prev => ({ ...prev, [field]: true }));
    let success = false;
    try {
      switch (field) {
        case 'firstName':
          success = await updateFirstName(value as string); break;
        case 'lastName':
          success = await updateLastName(value as string); break;
        case 'language':
          const newLanguage = value as 'en' | 'fr';
          success = await updateLanguagePreference(newLanguage);
          if (success) await changeLanguage(newLanguage);
          break;
      }
      if (success) {
        setOriginalValues(prev => ({ ...prev, [field]: value }));
        setTimeout(() => {
          if (isMounted.current) {
            setPendingUpdates(prev => {
              if (prev[field]) {
                const updated = { ...prev }; delete updated[field]; return updated;
              }
              return prev;
            });
          }
        }, 3000);
      }
    } catch (error) {
      setPendingUpdates(prev => { const updated = { ...prev }; delete updated[field]; return updated; });
    }
  };

  const handleLanguageChange = async (languageValue: string) => {
    const newLanguage = languageValue as 'en' | 'fr';
    setFormValues(prev => ({ ...prev, language: newLanguage }));
    setPendingUpdates(prev => ({ ...prev, language: true }));
    try {
      const success = await updateLanguagePreference(newLanguage);
      if (success) {
        setOriginalValues(prev => ({ ...prev, language: newLanguage }));
        await changeLanguage(newLanguage);
      } else {
        setFormValues(prev => ({ ...prev, language: originalValues.language }));
        setPendingUpdates(prev => { const updated = { ...prev }; delete updated.language; return updated; });
      }
    } catch (error) {
      setFormValues(prev => ({ ...prev, language: originalValues.language }));
      setPendingUpdates(prev => { const updated = { ...prev }; delete updated.language; return updated; });
    }
  };

  const handleDeleteAccountPress = () => {
    Alert.alert(
      t('profile.deleteAccount'),
      t('profile.deleteAccountWarning'),
      [
        { text: t('common.cancel'), style: 'cancel' },
        { text: t('profile.confirmDeletion'), style: 'destructive', onPress: confirmDeleteAccount }
      ]
    );
  };

  const confirmDeleteAccount = async () => {
    setIsDeleting(true);
    try {
      const success = await deleteAccount();
      if (success) {
        setTimeout(() => { setIsDeleting(false); if (logout) logout(); }, 5000);
      } else {
        setIsDeleting(false);
      }
    } catch (error) {
      setIsDeleting(false);
    }
  };

  const isFieldUpdating = (field: string) => pendingUpdates[field] === true;

  return {
    formValues,
    setFormValues,
    editing,
    setEditing,
    originalValues,
    setOriginalValues,
    pendingUpdates,
    setPendingUpdates,
    isDeleting,
    handleStartEditing,
    handleCancel,
    handleUpdate,
    handleLanguageChange,
    handleDeleteAccountPress,
    confirmDeleteAccount,
    isFieldUpdating,
  };
} 