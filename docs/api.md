# NYT Article Explorer API Documentation
Author: Ali Salem <admin@alisalem.me>

## Authentication

All API endpoints except authentication endpoints require a JWT token in the Authorization header:
```
Authorization: Bearer <your_jwt_token>
```

## Endpoints

### Authentication

#### Register User
- **URL**: `/api/auth/register`
- **Method**: `POST`
- **Body**:
```json
{
    "username": "string",
    "email": "string",
    "password": "string"
}
```
- **Response**:
```json
{
    "token": "string",
    "user": {
        "id": "integer",
        "email": "string",
        "username": "string"
    }
}
```

#### Login User
- **URL**: `/api/auth/login`
- **Method**: `POST`
- **Body**:
```json
{
    "email": "string",
    "password": "string"
}
```
- **Response**:
```json
{
    "token": "string",
    "user": {
        "id": "integer",
        "email": "string"
    }
}
```

### Articles

#### Search Articles
- **URL**: `/api/articles/search`
- **Method**: `GET`
- **Query Parameters**:
  - `q`: Search query (required)
  - `page`: Page number (optional, default: 0)
- **Response**:
```json
{
    "response": {
        "docs": [
            {
                "_id": "string",
                "headline": {
                    "main": "string"
                },
                "snippet": "string",
                "web_url": "string",
                "pub_date": "string",
                "multimedia": [
                    {
                        "url": "string",
                        "type": "string"
                    }
                ]
            }
        ],
        "meta": {
            "hits": "integer"
        }
    }
}
```

#### Get Article Details
- **URL**: `/api/articles/details`
- **Method**: `GET`
- **Query Parameters**:
  - `url`: Article URL (required)
- **Response**:
```json
{
    "_id": "string",
    "headline": {
        "main": "string"
    },
    "byline": {
        "original": "string"
    },
    "pub_date": "string",
    "web_url": "string",
    "lead_paragraph": "string",
    "snippet": "string",
    "source": "string",
    "keywords": [
        {
            "value": "string"
        }
    ],
    "multimedia": [
        {
            "url": "string",
            "type": "string"
        }
    ]
}
```

### Favorites

#### Get Favorites
- **URL**: `/api/favorites`
- **Method**: `GET`
- **Response**:
```json
[
    {
        "id": "integer",
        "user_id": "integer",
        "article_id": "string",
        "title": "string",
        "url": "string",
        "created_at": "string"
    }
]
```

#### Add Favorite
- **URL**: `/api/favorites`
- **Method**: `POST`
- **Body**:
```json
{
    "article_id": "string",
    "title": "string",
    "url": "string"
}
```
- **Response**:
```json
{
    "message": "Article added to favorites"
}
```

#### Remove Favorite
- **URL**: `/api/favorites`
- **Method**: `DELETE`
- **Query Parameters**:
  - `article_id`: Article ID (required)
- **Response**:
```json
{
    "message": "Article removed from favorites"
}
```

## Error Responses

All endpoints may return the following error responses:

### 400 Bad Request
```json
{
    "error": "Error message"
}
```

### 401 Unauthorized
```json
{
    "error": "Unauthorized"
}
```

### 404 Not Found
```json
{
    "error": "Resource not found"
}
```

### 429 Too Many Requests
```json
{
    "error": "Too many requests",
    "retry_after": "integer"
}
```

### 500 Internal Server Error
```json
{
    "error": "Internal server error"
}
```

## Rate Limiting

The API implements rate limiting with the following rules:
- Maximum 5 requests per 5 minutes per IP address
- When rate limit is exceeded, the API returns a 429 status code with a `retry_after` field indicating the number of seconds to wait before making another request
- Rate limits are tracked separately for authenticated and unauthenticated requests 