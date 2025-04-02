# NYT Article Explorer

A web application that allows users to search and explore New York Times articles, with features for saving favorites and user authentication.

## Features

- Search New York Times articles
- View article details
- User authentication (register/login)
- Save favorite articles
- Rate limiting
- JWT-based authentication
- Responsive web interface

## Requirements

- PHP 8.1 or higher
- MySQL 5.7 or higher
- Docker and Docker Compose
- New York Times API key

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/nyt-article-explorer.git
cd nyt-article-explorer
```

2. Copy the environment file and update the values:
```bash
cp .env.example .env
```

3. Update the following variables in `.env`:
```
DB_HOST=mysql
DB_NAME=nyt_explorer
DB_USER=nyt_user
DB_PASS=your_password
JWT_SECRET=your_jwt_secret
NYT_API_KEY=your_nyt_api_key
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

1. Access the web interface at `http://localhost:8080`
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
│   │   └── Database.php
│   ├── Controllers/
│   │   ├── ApiController.php
│   │   └── AuthController.php
│   ├── Middlewares/
│   │   ├── AuthMiddleware.php
│   │   └── RateLimitMiddleware.php
│   └── Services/
│       ├── JwtService.php
│       ├── NytApiService.php
│       └── RateLimitService.php
├── scripts/
│   └── setup_database.php
├── docs/
│   ├── api.md
│   └── postman_collection.json
├── .env.example
├── .gitignore
├── composer.json
├── docker-compose.yml
└── README.md
```

### Running Tests

```bash
docker-compose exec app vendor/bin/phpunit
```

## Security

- All API endpoints (except authentication) require a valid JWT token
- Passwords are hashed using PHP's password_hash function
- Rate limiting is implemented to prevent abuse
- Input validation and sanitization is performed on all user inputs
- CORS is configured to allow specific origins only

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Author

Ali Salem <admin@alisalem.me>
