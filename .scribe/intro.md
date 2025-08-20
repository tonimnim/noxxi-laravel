# Introduction

Noxxi is an African-focused event ticketing and management platform supporting multiple countries and currencies across Africa.

<aside>
    <strong>Base URL</strong>: <code>http://localhost</code>
</aside>

    Welcome to the Noxxi API documentation! This API powers our mobile applications and web platform for event management and ticketing across Africa.
    
    ## Authentication
    Most endpoints require authentication using OAuth2 Bearer tokens. Include your token in the `Authorization` header:
    ```
    Authorization: Bearer {your-token}
    ```
    
    ## Base URLs
    - Production: `https://api.noxxi.com/api`
    - Development: `http://localhost:8000/api`
    
    ## Rate Limiting
    API requests are rate-limited to ensure fair usage:
    - Authentication endpoints: 5 requests per minute
    - General endpoints: 60 requests per minute
    - Password reset: 3 requests per hour
    
    ## Response Format
    All responses follow a consistent JSON structure:
    ```json
    {
        "status": "success|error",
        "message": "Description",
        "data": {}
    }
    ```
    
    ## Supported Currencies
    - KES (Kenyan Shilling)
    - NGN (Nigerian Naira)
    - ZAR (South African Rand)
    - GHS (Ghanaian Cedi)
    - UGX (Ugandan Shilling)
    - TZS (Tanzanian Shilling)
    - EGP (Egyptian Pound)
    - USD (US Dollar)

