@echo off
cd /d "%~dp0"
curl.exe -s -X POST http://127.0.0.1:8000/api/v1/citas -H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: Bearer 10|ef9b2Hpy0YCf3nJ1zcmhiJf1bDBZQLOpxuUaKsEX0e9e0322" -d @cita_solapada.json -w "\nHTTP %%{http_code}\n"
echo.