import { Redirect } from "expo-router";

// This file is the main entry point for your app.
// It redirects to the appropriate area based on the app state.
export default function Index() {
  // Redirect to the tabs layout, which will then handle authentication checks
  return <Redirect href="/(tabs)" />;
}
