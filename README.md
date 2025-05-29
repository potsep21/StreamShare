# StreamShare - Content Streaming Platform

StreamShare is a web application that allows users to create and share lists of streaming content, specifically YouTube videos. Users can create private or public lists, follow other users, and discover new content through the community.

## Features

- User registration and authentication
- Create and manage content lists
- Public and private list visibility options
- Follow other users
- View and play YouTube videos directly from lists
- Dark/light theme support
- Responsive design
- Search functionality
- Profile management
- YouTube OAuth integration for video search
- Activity feed showing content from followed users
- Delete list functionality
- Social connections and content discovery
- User privacy controls
- Profile customization
- Data export capabilities

## Recent Updates

### UI Improvements
- **Enhanced List View**: Completely redesigned view_list.php with modern card-based layout
- **Dashboard Enhancement**: Better organization of user content with clearer actions
- **Profile Activity Feed**: Added personalized activity section showing content from followed users
- **Responsive Improvements**: Better mobile support across all pages

### Functional Improvements
- **Activity Feed**: Added social activity tracking for:
  - Public list creation by followed users
  - Videos added to public lists by followed users
  - New followers (visible only to profile owner)
- **List Management**:
  - Added ability to delete lists
  - Improved list editing interface
  - Auto-redirect to edit newly created lists
- **Content Organization**:
  - Moved content lists from profile to dashboard for better organization
  - Added activity section to profile for social engagement
- **User Experience**:
  - Improved navigation between related pages
  - Enhanced form validation and error messaging
  - Better content discovery mechanisms

### Security & Privacy
- **Privacy Controls**:
  - Follower information is only visible to profile owner
  - Enhanced permissions for content access
  - Improved private/public content separation
- **User Data Protection**:
  - Secure handling of OAuth tokens
  - Better input validation and sanitization
  - Protection against common web vulnerabilities

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser
- YouTube OAuth credentials (required for video search)

## Installation

1. Clone the repository to your web server directory:
   ```bash
   git clone https://github.com/yourusername/streamshare.git
   ```

2. Create a MySQL database for the application.

3. Configure the database connection:
   - Open `config/database.php`
   - Update the following constants with your database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     define('DB_NAME', 'streamshare');
     ```

4. Set up YouTube OAuth credentials:
   - Go to the [Google Cloud Console](https://console.cloud.google.com/)
   - Create a new project
   - Enable the YouTube Data API v3
   - Create OAuth 2.0 credentials (Web application type)
   - Add authorized redirect URIs: `http://your-domain.com/php/oauth_callback.php`
   - Copy the OAuth client ID and client secret to your configuration:
     - Copy `config/api_keys.example.php` to `config/api_keys.php`
     - Add your OAuth credentials:
       ```php
       $YOUTUBE_OAUTH_CLIENT_ID = 'your_client_id_here';
       $YOUTUBE_OAUTH_CLIENT_SECRET = 'your_client_secret_here';
       ```

5. Initialize the database by visiting:
   ```
   http://your-domain/php/init_db.php
   ```

## Directory Structure

```
streamshare/
├── config/
│   ├── database.php
│   ├── youtube.php
│   ├── youtube_oauth.php
│   └── api_keys.php
├── css/
│   └── styles.css
├── includes/
│   └── functions.php
├── js/
│   └── main.js
├── php/
│   ├── dashboard.php
│   ├── edit_list.php
│   ├── edit_profile.php
│   ├── export_data.php
│   ├── init_db.php
│   ├── login.php
│   ├── logout.php
│   ├── oauth_callback.php
│   ├── profile.php
│   ├── register.php
│   ├── search.php
│   └── view_list.php
├── about.php
├── help.php
├── index.php
└── README.md
```

## Usage

1. Visit the website and create an account
2. Log in to your account
3. Create content lists from your dashboard
4. When prompted, authenticate with your Google account to use YouTube search
5. Add videos to your lists by searching YouTube directly
6. Follow other users to discover their public content in your activity feed
7. Manage your profile and customize your visibility settings
8. View the activity feed to see content from users you follow

## User Journey

1. **Registration/Login**: Create an account or sign in
2. **Dashboard**: Create new content lists or manage existing ones
3. **Content Creation**: Add YouTube videos to your lists through search
4. **Content Discovery**: Follow other users and explore their public lists
5. **Activity Feed**: Keep up with content from users you follow
6. **Profile Management**: Update your information and privacy settings

## Security Features

- CSRF protection
- Password hashing
- Input sanitization
- Prepared SQL statements
- Session management
- XSS prevention
- OAuth 2.0 authentication for YouTube API
- Privacy controls for user data
- Secure content visibility management

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- YouTube Data API with OAuth 2.0
- PHP PDO
- Modern CSS features
- JavaScript ES6+