@php
	$isArray = is_array($products);
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Low Stock Alert</title>
    <style>
        /* Filament Color System */
        :root {
            --primary: oklch(51.34% 0.1395 264.75);
            --primary-foreground: #ffffff;
            --secondary: oklch(87.73% 0.0136 263.84);
            --background: oklch(97.15% 0.0064 262.51);
            --foreground: oklch(14.08% 0.0044 285.82);
            --muted: oklch(63.39% 0.0234 255.77);
            --muted-foreground: oklch(55.14% 0.0208 257.77);
            --destructive: oklch(51.33% 0.1492 29.69);
            --destructive-foreground: #ffffff;
            --ring: oklch(82.84% 0.0967 238.89);
            --border: oklch(92.57% 0.0056 261.22);
            --input: oklch(95.22% 0.0024 262.72);
        }

        /* Base Container Styles */
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--background);
            color: var(--foreground);
            margin: 0;
            padding: 0;
            width: 100%;
            line-height: 1.6;
        }

        .wrapper-layout {
            background-color: #f8fafc;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            min-width: 360px;
        }

        .main-card {
            background-color: var(--background);
            border-radius: 12px;
            padding: 32px;
            max-width: 560px;
            width: 100%;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.06);
        }

        /* Typography */
        h1 {
            color: var(--primary);
            font-size: 24px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }

        h1 + p {
            margin-top: 0;
        }

        p {
            font-size: 15px;
            line-height: 1.7;
            color: var(--muted);
            margin: 0 0 12px 0;
        }

        .product-name {
            color: var(--foreground);
            font-weight: 600;
            font-size: 16px;
        }

        /* Stock Status Components */
        .stock-status {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
        }

        .stock-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--foreground);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }

        .stock-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--destructive);
            line-height: 1.2;
        }

        .threshold-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted-foreground);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 8px;
        }

        .threshold-value {
            font-size: 14px;
            color: var(--muted-foreground);
        }

        /* Alert Icon */
        .alert-icon {
            display: inline-block;
            width: 20px;
            height: 20px;
            background-color: var(--destructive);
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
        }

        /* Action Button */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary);
            color: var(--primary-foreground) !important;
            text-decoration: none !important;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            transition: background-color 0.2s;
            border: 1px solid transparent;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        /* Footer */
        .footer-section {
            border-top: 1px solid var(--border);
            padding-top: 20px;
            margin-top: 24px;
            font-size: 13px;
            color: var(--muted-foreground);
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="wrapper-layout">
        <main class="main-card">
            <header>
                <h1>⚠️ Low Stock Alert</h1>
            </header>


            <section>
                <p>
                    The stock levels for the following Products are low:
                </p>
                <table>
                    <thead>
                        <tr>
                            <th>
                                Product Name
                            </th>
                            <th>
                                Current Stock
                            </th>
                            <th>
                                Warning Threshold
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($isArray)
                        @foreach($products as $product)
                            <tr>
                                <td class="product-name">{{ $product->name }}</td>
                                <td>{{ number_format($product->stock) }}</td>
                                <td>≤ {{ number_format($product->low_stock_threshold) }}</td>
                            </tr>
                        @endforeach
                        @else
                            <tr>
                                <td class="product-name">{{ $products->name }}</td>
                                <td>{{ number_format($products->stock) }}</td>
                                <td>≤ {{ number_format($products->low_stock_threshold) }}</td>
                            </tr>
                        @endif

                    </tbody>
                </table>
            </section>


            <section>
                <p>
                    <strong>Action Required:</strong>
                    Please review inventory levels and allocate stock replenishment immediately to maintain product availability.
                </p>
                <a href="{{ $url }}" class="btn-primary">
                    View Products Dashboard
                </a>
            </section>

            <footer class="footer-section">
                <p><strong>{{ config('app.name') }}</strong></p>
                <p>&copy; {{ date('Y') }}. All rights reserved.</p>
            </footer>
        </main>
    </div>
</body>
</html>
