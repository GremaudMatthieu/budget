import { NextResponse } from 'next/server'
import type { NextRequest } from 'next/server'

export function middleware(request: NextRequest) {
  // Check for all possible auth tokens
  const token = request.cookies.get('jwtToken')?.value;
  
  // Debug log for middleware
  console.log(`[Middleware] Path: ${request.nextUrl.pathname}, Token exists: ${!!token}`);
  
  // Skip auth check for OAuth callback paths
  if (request.nextUrl.pathname.startsWith('/oauth/')) {
    console.log('[Middleware] Skipping auth check for OAuth callback path');
    return NextResponse.next();
  }
  
  const isAuthPage = request.nextUrl.pathname.startsWith('/signin') || 
                     request.nextUrl.pathname.startsWith('/signup');
  const isHomePage = request.nextUrl.pathname === '/';
  const isPublicPath = isAuthPage || isHomePage || 
                      request.nextUrl.pathname.startsWith('/forgot-password');

  if (!token && !isPublicPath) {
    // Redirect to login page if there's no token and it's not a public page
    console.log('[Middleware] No token found, redirecting to signin');
    return NextResponse.redirect(new URL('/signin', request.url));
  }

  if (token && isAuthPage) {
    // Redirect to envelopes page if there's a token and user is trying to access an auth page
    console.log('[Middleware] User authenticated but trying to access auth page, redirecting to envelopes');
    return NextResponse.redirect(new URL('/envelopes', request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    // Skip middleware for API routes, static files, etc.
    '/((?!api|_next/static|_next/image|favicon.ico).*)',
  ],
}
