@echo off
cd /d "%~dp0"
curl.exe -s "http://127.0.0.1:8000/api/v1/disponibilidad?servicio_id=2&fecha=2026-09-21" -H "Accept: application/json" -H "Authorization: Bearer 10|ef9b2Hpy0YCf3nJ1zcmhiJf1bDBZQLOpxuUaKsEX0e9e0322" -w "\nHTTP %%{http_code}\n"
echo.