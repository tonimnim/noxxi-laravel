# PowerShell script to download and install Redis PHP extension

$phpVersion = "8.4"
$arch = "x64"
$ts = "nts"  # Non-Thread Safe

# For PHP 8.4, we might need to use 8.3 version as 8.4 might not be available yet
Write-Host "Downloading Redis extension for PHP..."

# Try PHP 8.3 version (usually compatible with 8.4)
$url = "https://github.com/phpredis/phpredis/releases/download/6.0.2/php_redis-6.0.2-8.3-nts-vs16-x64.zip"
$output = "C:\Users\antho\Downloads\php_redis.zip"

try {
    Invoke-WebRequest -Uri $url -OutFile $output
    Write-Host "Download completed: $output"
    
    # Extract the DLL
    Expand-Archive -Path $output -DestinationPath "C:\Users\antho\Downloads\redis_temp" -Force
    
    # Copy the DLL to PHP ext directory
    Copy-Item "C:\Users\antho\Downloads\redis_temp\php_redis.dll" -Destination "C:\tools\php84\ext\" -Force
    
    Write-Host "Redis extension installed to C:\tools\php84\ext\php_redis.dll"
    Write-Host "Now add 'extension=redis' to your php.ini file"
    
    # Clean up
    Remove-Item -Path $output -Force
    Remove-Item -Path "C:\Users\antho\Downloads\redis_temp" -Recurse -Force
    
} catch {
    Write-Host "Error downloading Redis extension: $_"
    Write-Host "You may need to download it manually from https://pecl.php.net/package/redis"
}