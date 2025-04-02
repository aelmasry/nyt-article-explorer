# NYT Article Explorer

Use of the following API:
New York Times Developer API “https://developer.nytimes.com/”
to allow users to search, view, and save favorite articles.
This task is designed to simulate a real-world backend project suitable for mobile
integration and web.


## Features

- Search New York Times articles
- View article details
- User authentication (register/login)
- Save favorite articles
- Rate limiting
- JWT-based authentication
- Responsive web interface
- Comprehensive logging system
- Unit tests for core functionalities

## Requirements

- PHP 8.1 or higher
- Docker and Docker Compose
- New York Times API key

## Installation

1. Clone the repository:
```bash
git clone https://github.com/aelmasry/nyt-article-explorer.git
cd nyt-article-explorer
```

2. Copy the environment file and update the values:
```bash
cp .env.example .env
```

3. Update the following variables in `.env`:
```
JWT_SECRET=your_jwt_secret
NYT_API_KEY=your_nyt_api_key
LOG_PATH=/var/www/html/logs/app.log
LOG_LEVEL=info
```

4. Build and start the Docker containers:
```bash
docker-compose up -d --build
```

5. Install dependencies:
```bash
docker-compose exec app composer install
```

6. Create the database tables:
```bash
docker-compose exec app php scripts/setup_database.php
```

## Usage

1. Access the web interface at `http://localhost:8009`
2. Register a new account or login
3. Search for articles using the search bar
4. View article details by clicking on an article
5. Save articles to your favorites
6. View your favorite articles in the favorites section

## API Documentation

The API documentation is available in the `docs/api.md` file. A Postman collection for testing the API is also provided in `docs/postman_collection.json`.

## Development

### Project Structure

```
nyt-article-explorer/
├── docker/
│   ├── apache.conf
│   └── Dockerfile
├── public/
│   ├── index.php
│   ├── index.html
│   ├── js/
│   │   └── app.js
│   └── .htaccess
├── src/
│   ├── Config/
│   │   ├── Database.php
│   │   └── Logger.php
│   ├── Controllers/
│   │   ├── ApiController.php
│   │   └── AuthController.php
│   ├── Middlewares/
│   │   ├── AuthMiddleware.php
│   │   └── RateLimitMiddleware.php
│   ├── Services/
│   │   ├── JwtService.php
│   │   ├── NytApiService.php
│   │   └── RateLimitService.php
│   └── Utils/
│       └── Logger.php
├── tests/
│   └── Services/
│       ├── AuthServiceTest.php
│       ├── JwtServiceTest.php
│       ├── NytApiServiceTest.php
│       └── RateLimitServiceTest.php
├── scripts/
│   └── setup_database.php
├── docs/
│   ├── api.md
│   └── postman_collection.json
├── logs/
│   └── app.log
├── .env.example
├── .gitignore
├── composer.json
├── docker-compose.yml
├── phpunit.xml
└── README.md
```

### Running Tests

The project includes comprehensive unit tests for core services. To run the tests:

```bash
# Run all tests
docker-compose exec app vendor/bin/phpunit

# Run tests with coverage report
docker-compose exec app vendor/bin/phpunit --coverage-html coverage

# Run specific test file
docker-compose exec app vendor/bin/phpunit tests/Services/AuthServiceTest.php
```

### Viewing Logs

The application logs are stored in the `logs/app.log` file. You can view them using:

```bash
# View all logs
docker-compose exec app tail -f logs/app.log

# View last 100 lines
docker-compose exec app tail -n 100 logs/app.log

# View logs with timestamps
docker-compose exec app tail -f logs/app.log | grep -i "error"
```

Log levels available:
- emergency: System is unusable
- alert: Action must be taken immediately
- critical: Critical conditions
- error: Error conditions
- warning: Warning conditions
- notice: Normal but significant conditions
- info: Informational messages
- debug: Debug-level messages

## Security

- All API endpoints (except authentication) require a valid JWT token
- Passwords are hashed using PHP's password_hash function
- Rate limiting is implemented to prevent abuse
- Input validation and sanitization is performed on all user inputs
- CORS is configured to allow specific origins only

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Author

Ali Salem <admin@alisalem.me>

LinkedIn: https://www.linkedin.com/in/alielsayedsalem/

Web: https://alisalem.me/