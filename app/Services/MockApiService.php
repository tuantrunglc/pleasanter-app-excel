<?php

namespace App\Services;

class MockApiService
{
    /**
     * Generate mock data for testing
     *
     * @param string $category
     * @param int $limit
     * @return array
     */
    public static function getMockData($category = null, $limit = 50)
    {
        $limit = min(max(1, $limit), 100); // Ensure limit is between 1 and 100
        
        $data = [
            'items' => []
        ];
        
        switch ($category) {
            case 'products':
                $data['items'] = self::generateProducts($limit);
                break;
            case 'customers':
                $data['items'] = self::generateCustomers($limit);
                break;
            case 'orders':
                $data['items'] = self::generateOrders($limit);
                break;
            default:
                // Mix of all categories
                $productsCount = ceil($limit / 3);
                $customersCount = ceil($limit / 3);
                $ordersCount = $limit - $productsCount - $customersCount;
                
                $data['items'] = array_merge(
                    self::generateProducts($productsCount),
                    self::generateCustomers($customersCount),
                    self::generateOrders($ordersCount)
                );
                
                // Shuffle the items
                shuffle($data['items']);
                
                // Limit to requested number
                $data['items'] = array_slice($data['items'], 0, $limit);
        }
        
        return $data;
    }
    
    /**
     * Generate mock product data
     *
     * @param int $count
     * @return array
     */
    private static function generateProducts($count)
    {
        $products = [];
        $categories = ['Electronics', 'Clothing', 'Books', 'Home & Kitchen', 'Sports'];
        
        for ($i = 1; $i <= $count; $i++) {
            $price = rand(10, 1000) + (rand(0, 99) / 100);
            $stock = rand(0, 100);
            
            $products[] = [
                'id' => 'P' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'type' => 'product',
                'name' => 'Product ' . $i,
                'category' => $categories[array_rand($categories)],
                'price' => $price,
                'currency' => 'USD',
                'stock' => $stock,
                'status' => $stock > 0 ? 'In Stock' : 'Out of Stock',
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 365) . ' days')),
            ];
        }
        
        return $products;
    }
    
    /**
     * Generate mock customer data
     *
     * @param int $count
     * @return array
     */
    private static function generateCustomers($count)
    {
        $customers = [];
        $countries = ['USA', 'Canada', 'UK', 'Australia', 'Germany', 'France', 'Japan'];
        
        for ($i = 1; $i <= $count; $i++) {
            $country = $countries[array_rand($countries)];
            
            $customers[] = [
                'id' => 'C' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'type' => 'customer',
                'name' => 'Customer ' . $i,
                'email' => 'customer' . $i . '@example.com',
                'country' => $country,
                'city' => 'City ' . rand(1, 50),
                'orders_count' => rand(0, 20),
                'total_spent' => rand(0, 10000) + (rand(0, 99) / 100),
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 730) . ' days')),
            ];
        }
        
        return $customers;
    }
    
    /**
     * Generate mock order data
     *
     * @param int $count
     * @return array
     */
    private static function generateOrders($count)
    {
        $orders = [];
        $statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
        $paymentMethods = ['Credit Card', 'PayPal', 'Bank Transfer', 'Cash on Delivery'];
        
        for ($i = 1; $i <= $count; $i++) {
            $status = $statuses[array_rand($statuses)];
            $items = rand(1, 10);
            $subtotal = rand(10, 1000) + (rand(0, 99) / 100);
            $tax = round($subtotal * 0.1, 2);
            $shipping = rand(5, 50) + (rand(0, 99) / 100);
            $total = $subtotal + $tax + $shipping;
            
            $orders[] = [
                'id' => 'O' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'type' => 'order',
                'customer_id' => 'C' . str_pad(rand(1, 1000), 4, '0', STR_PAD_LEFT),
                'status' => $status,
                'items_count' => $items,
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'total' => $total,
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 365) . ' days')),
            ];
        }
        
        return $orders;
    }
}