import { Redirect } from "expo-router";
import { useAuth } from "@/contexts/AuthContext";

// This file is the main entry point for your app.
// It redirects to the appropriate area based on the app state.
export default function Index() {
  const { user } = useAuth();
  
  // If user is authenticated, redirect to tabs
  // Otherwise, redirect to auth flow
  return user ? <Redirect href="/(tabs)" /> : <Redirect href="/(auth)" />;
}
