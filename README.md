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

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser
- YouTube Data API credentials (for video search)

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

4. Initialize the database by visiting:
   ```
   http://your-domain/php/init_db.php
   ```

5. Set up YouTube Data API:
   - Go to the [Google Cloud Console](https://console.cloud.google.com/)
   - Create a new project
   - Enable the YouTube Data API
   - Create credentials (OAuth 2.0)
   - Add your credentials to the configuration

## Directory Structure

```
streamshare/
├── config/
│   └── database.php
├── css/
│   └── styles.css
├── includes/
│   └── functions.php
├── js/
│   └── main.js
├── php/
│   ├── dashboard.php
│   ├── init_db.php
│   ├── login.php
│   ├── logout.php
│   └── register.php
├── about.html
├── help.html
├── index.html
└── README.md
```

## Usage

1. Visit the website and create an account
2. Log in to your account
3. Create content lists and add YouTube videos
4. Follow other users to discover their public lists
5. Manage your profile and content visibility

## Security Features

- CSRF protection
- Password hashing
- Input sanitization
- Prepared SQL statements
- Session management
- XSS prevention

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- YouTube Data API
- PHP PDO
- Modern CSS features
- JavaScript ES6+ 