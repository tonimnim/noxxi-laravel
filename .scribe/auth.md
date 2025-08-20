# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_ACCESS_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

To authenticate, obtain an access token by calling the <code>/api/auth/login</code> endpoint with your credentials. Include the token in the Authorization header as: <code>Authorization: Bearer {access_token}</code>
