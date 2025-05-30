# Pleasanter Export Excel

A Laravel 11 application for exporting data to Excel using Maatwebsite/Excel package.

## Features

- Export data to Excel files (.xlsx format)
- Simple, clean user interface
- Built with Laravel 11 and Maatwebsite/Excel

## Requirements

- PHP 8.2 or higher
- Composer
- Laravel 11
- Maatwebsite/Excel package

## Installation

1. Clone the repository:
   ```
   git clone <repository-url>
   cd pleasanter-export-excel
   ```

2. Install dependencies:
   ```
   composer install
   ```

3. Create and configure the environment file:
   ```
   cp .env.example .env
   php artisan key:generate
   ```

4. Run the application:
   ```
   php artisan serve
   ```

5. Visit `http://localhost:8000` in your browser

## Usage

1. Navigate to the homepage
2. Click on the "Download Sample Excel" button
3. The application will generate and download an Excel file with sample data

## Project Structure

- `app/Exports/PleasanterExport.php` - Contains the Excel export logic
- `app/Http/Controllers/ExportController.php` - Controller for handling export requests
- `resources/views/welcome.blade.php` - Main welcome page with export button

## Customizing Exports

To customize the exported data, modify the `export` method in the `ExportController.php` file:

```php
public function export()
{
    // Customize your data here
    $data = [
        ['ID', 'Name', 'Email', 'Created At'],
        [1, 'John Doe', 'john@example.com', now()->format('Y-m-d')],
        // Add more rows as needed
    ];

    return PleasanterExport::export($data, 'your-custom-filename');
}
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
