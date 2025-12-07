@echo off
echo Starting PHP FastCGI...
start "" /B "C:\xampp\php\php-cgi.exe" -b 127.0.0.1:9000

echo Starting Caddy web server...
start "" /B "C:\Caddy\caddy.exe" run --config "C:\Caddy\Caddyfile"

timeout /t 5 /nobreak >nul

echo Starting Cloudflare Tunnel...
start "" /B "C:\cloudflared\cloudflared.exe" --config "C:\cloudflared\config.yaml" tunnel run

timeout /t 10 /nobreak >nul

echo Opening website in browser...
start "" https://sbserver123.duckdns.org

echo.
echo ================================================
echo  All services started successfully!
echo  PHP-FPM, Caddy, Cloudflared are running.
echo ================================================
echo.
pause
