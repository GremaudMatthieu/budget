import { NextResponse } from 'next/server';
import { cookies } from 'next/headers';

export async function GET(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const email = searchParams.get('email');

    if (!email) {
      console.error('OAuth API: Missing email parameter');
      return NextResponse.json({ error: 'Missing email parameter' }, { status: 400 });
    }

    console.log(`OAuth API: Processing request for email: ${email}`);

    // Call your backend API to get a JWT token using the email
    const backendUrl = `${process.env.NEXT_PUBLIC_API_URL}/api/users/find-by-email?email=${encodeURIComponent(email)}`;
    console.log(`OAuth API: Calling backend at ${backendUrl}`);
    
    const response = await fetch(backendUrl, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      const errorText = await response.text();
      console.error(`OAuth API: Backend responded with error status ${response.status}: ${errorText}`);
      return NextResponse.json(
        { error: `Failed to authenticate with the server: ${response.status}` },
        { status: response.status }
      );
    }

    const data = await response.json();
    console.log(`OAuth API: Received data from backend, token exists: ${Boolean(data.token)}`);
    
    if (!data.token) {
      console.error('OAuth API: No token in backend response');
      return NextResponse.json(
        { error: 'No authentication token received from server' },
        { status: 500 }
      );
    }
    
    // Set cookies for authentication - using 'jwtToken' to match what authService.js expects
    cookies().set('jwtToken', data.token, {
      httpOnly: true,
      secure: process.env.NODE_ENV !== 'development',
      maxAge: 60 * 60, // 1 hour
      path: '/',
      sameSite: 'lax',
    });
    
    if (data.refresh_token) {
      // Store refresh token in localStorage through client-side code
      // We can't directly access localStorage from this server component
    }

    console.log('OAuth API: Cookies set successfully');
    
    // Include the token in the response body for the client-side code
    return NextResponse.json({
      token: data.token,
      refresh_token: data.refresh_token,
      user: data.user
    });
  } catch (error) {
    console.error('OAuth API error:', error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : 'Internal server error' },
      { status: 500 }
    );
  }
}