<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Pleasanter Export Excel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        <style>
            body {
                font-family: 'Figtree', sans-serif;
                background-color: #f8fafc;
                color: #1a202c;
                margin: 0;
                padding: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                padding: 2rem;
                background-color: white;
                border-radius: 0.5rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                text-align: center;
            }
            h1 {
                color: #FF2D20;
                font-size: 2.5rem;
                margin-bottom: 1rem;
            }
            p {
                margin-bottom: 2rem;
                font-size: 1.1rem;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                background-color: #FF2D20;
                color: white;
                padding: 0.75rem 1.5rem;
                border-radius: 0.375rem;
                text-decoration: none;
                font-weight: 500;
                transition: background-color 0.3s;
            }
            .btn:hover {
                background-color: #e01e0c;
            }
            .footer {
                margin-top: 3rem;
                font-size: 0.875rem;
                color: #6b7280;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Pleasanter Export Excel</h1>
            <p>
                Welcome to the Pleasanter Export Excel application. This Laravel application demonstrates how to export data to Excel files using the Maatwebsite/Excel package.
            </p>
            
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="{{ route('export') }}" class="btn">
                    Download Simple Excel
                </a>
                
                <a href="{{ route('advanced-export') }}" class="btn" style="background-color: #28a745;">
                    Download Advanced Excel
                </a>
                
                <a href="{{ route('api.form') }}" class="btn" style="background-color: #0d6efd;">
                    Export from API
                </a>
            </div>
            
            <div class="footer">
                Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
            </div>
        </div>
    </body>
</html>