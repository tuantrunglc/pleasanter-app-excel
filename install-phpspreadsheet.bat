@echo off
echo Installing PhpSpreadsheet...
docker exec -it pleasanter-app-excel-laravel.test-1 composer require phpoffice/phpspreadsheet
echo Installation completed.