<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>API Data Export - Pleasanter Export Excel</title>

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
            }
            h1 {
                color: #FF2D20;
                font-size: 2rem;
                margin-bottom: 1.5rem;
                text-align: center;
            }
            .form-group {
                margin-bottom: 1.5rem;
            }
            label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 500;
            }
            input, select {
                width: 100%;
                padding: 0.75rem;
                border: 1px solid #e2e8f0;
                border-radius: 0.375rem;
                font-size: 1rem;
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
                border: none;
                cursor: pointer;
                font-size: 1rem;
            }
            .btn:hover {
                background-color: #e01e0c;
            }
            .btn-secondary {
                background-color: #6b7280;
            }
            .btn-secondary:hover {
                background-color: #4b5563;
            }
            .actions {
                display: flex;
                justify-content: space-between;
                margin-top: 2rem;
            }
            .alert {
                padding: 1rem;
                border-radius: 0.375rem;
                margin-bottom: 1.5rem;
            }
            .alert-error {
                background-color: #fee2e2;
                color: #b91c1c;
                border: 1px solid #f87171;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Export API Data to Excel</h1>
            
            @if(session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif
            
            <form action="{{ route('api.export') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="api_key">API Key (Required)</label>
                    <input type="text" name="api_key" id="api_key" required placeholder="Enter your API key">
                </div>
                
                <div class="form-group">
                    <label for="id">ID (Required)</label>
                    <input type="text" name="id" id="id" required placeholder="Enter the ID for the API request">
                </div>
                
                <div class="form-group">
                    <label for="filename">Filename (Optional)</label>
                    <input type="text" name="filename" id="filename" placeholder="Name for the exported file (default: api-data-export)">
                </div>
                
                <div class="form-group">
                    <label for="category">Category (Optional)</label>
                    <select name="category" id="category">
                        <option value="">All Categories</option>
                        <option value="products">Products</option>
                        <option value="customers">Customers</option>
                        <option value="orders">Orders</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="limit">Limit (Optional)</label>
                    <input type="number" name="limit" id="limit" min="1" max="100" placeholder="Maximum number of records (default: 50)">
                </div>
                
                <div class="actions">
                    <a href="{{ route('home') }}" class="btn btn-secondary">Back to Home</a>
                    <button type="submit" class="btn">Generate Excel</button>
                </div>
            </form>
        </div>
    </body>
</html>