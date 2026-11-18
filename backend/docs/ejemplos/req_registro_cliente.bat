@echo off
cd /d "%~dp0"
curl.exe -s -X POST http://127.0.0.1:8000/api/v1/auth/register -H "Content-Type: application/json" -H "Accept: application/json" --data-binary "@registro_cliente.json"
echo.
