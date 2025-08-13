<!DOCTYPE html>
<html>
<head>
    <title>Test Login Flow</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 600px; margin: 0 auto; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Login Flow Test</h1>
    
    <div class="test-section">
        <h2>Test Accounts</h2>
        <p><strong>Admin:</strong> admin@noxxi.com / password123</p>
        <p><strong>Organizer:</strong> organizer@noxxi.com / password123</p>
        <p><strong>User:</strong> user@noxxi.com / password123</p>
    </div>

    <div class="test-section">
        <h2>Test Login</h2>
        <button onclick="testLogin('admin@noxxi.com')">Test Admin Login</button>
        <button onclick="testLogin('organizer@noxxi.com')">Test Organizer Login</button>
        <button onclick="testLogin('user@noxxi.com')">Test User Login</button>
        <div id="result"></div>
    </div>

    <script>
        async function testLogin(email) {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p class="info">Testing login for ' + email + '...</p>';
            
            try {
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        email: email,
                        password: 'password123'
                    })
                });

                const data = await response.json();
                
                if (response.ok) {
                    const user = data.data.user;
                    const token = data.data.token;
                    
                    // Store token
                    localStorage.setItem('auth_token', token);
                    
                    // Determine redirect path
                    let redirectPath = '/';
                    switch(user.role) {
                        case 'admin':
                            redirectPath = '/admin';
                            break;
                        case 'organizer':
                            redirectPath = '/organizer/dashboard';
                            break;
                        case 'user':
                            redirectPath = '/my-account';
                            break;
                    }
                    
                    resultDiv.innerHTML = `
                        <p class="success">✓ Login successful!</p>
                        <p><strong>User:</strong> ${user.full_name}</p>
                        <p><strong>Email:</strong> ${user.email}</p>
                        <p><strong>Role:</strong> ${user.role}</p>
                        <p><strong>Redirect Path:</strong> ${redirectPath}</p>
                        <p><strong>Token stored:</strong> Yes</p>
                        ${user.organizer ? '<p><strong>Business:</strong> ' + user.organizer.business_name + '</p>' : ''}
                        <button onclick="window.location.href='${redirectPath}'">Go to Dashboard</button>
                        <details>
                            <summary>Full Response</summary>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </details>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <p class="error">✗ Login failed!</p>
                        <p>${data.message || 'Unknown error'}</p>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <p class="error">✗ Network error!</p>
                    <p>${error.message}</p>
                `;
            }
        }
    </script>
</body>
</html>