# YouTube OAuth Implementation

This document explains how the StreamShare application implements OAuth 2.0 for YouTube API access.

## Overview

The application uses OAuth 2.0 to authenticate users with Google and gain access to the YouTube Data API. This approach provides:

1. Higher API quota limits compared to API key authentication
2. Access to user-specific data (likes, playlists, etc.)
3. Greater security by not exposing API keys in client-side code
4. Compliance with Google's authentication requirements

## Files and Components

### Configuration Files

- `config/youtube_oauth.php`: Core OAuth functionality including authentication URL generation
- `config/youtube.php`: API request handling with OAuth tokens
- `config/api_keys.php`: Stores OAuth client credentials (not tracked in Git)
- `config/api_keys.example.php`: Template for OAuth credentials

### Implementation Files

- `php/oauth_callback.php`: Handles the OAuth callback from Google
- `php/view_list.php`: Initiates OAuth flow for YouTube search and video retrieval

### Database

The application stores OAuth refresh tokens in the database to enable persistent authentication:

```sql
CREATE TABLE oauth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider VARCHAR(50) NOT NULL,
    refresh_token VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY user_provider (user_id, provider),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Authentication Flow

1. **Initial Authentication**:
   - When a user attempts to search or add YouTube videos, the application checks if they're authenticated
   - If not authenticated, the user is redirected to Google's authorization page
   - The app requests `https://www.googleapis.com/auth/youtube.readonly` scope

2. **OAuth Callback**:
   - Google redirects back to our callback URL with an authorization code
   - The application exchanges the code for access and refresh tokens
   - Refresh token is stored in the database for future use
   - Access token is stored in the user's session

3. **Token Management**:
   - Before each API request, the application checks if the access token is valid
   - If expired, it uses the refresh token to obtain a new access token
   - If refresh fails (e.g., user revoked access), the user is prompted to re-authenticate

## Setting Up OAuth Credentials

1. Go to the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project
3. Enable the YouTube Data API v3
4. Create OAuth credentials:
   - Choose "Web application" type
   - Add authorized JavaScript origins (your domain)
   - Add authorized redirect URIs: `http://your-domain.com/php/oauth_callback.php`
5. Copy the client ID and client secret to `config/api_keys.php`

## Security Considerations

- OAuth refresh tokens are stored securely in the database
- Access tokens are only stored in the user's session
- HTTPS is recommended for production to secure the OAuth flow
- CSRF protection is implemented for all form submissions
- No API keys are used in the application

## Troubleshooting

If users encounter authentication issues:
1. Verify OAuth credentials are correctly set in `api_keys.php`
2. Check that the YouTube Data API is enabled in Google Cloud Console
3. Verify the redirect URI matches exactly what's configured in Google Cloud Console
4. Check for SSL/TLS issues if using HTTPS
5. The user may need to re-authenticate if they've revoked access 