# Simple Order API System

A standalone order management system with REST API and Filament admin interface.

## Overview

This system provides a simple way to manage orders through API endpoints with a read-only admin interface. Orders are stored in a separate table with no relationships to other models.

## Database Schema

### Table: `simple_orders`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| customer_name | string | Customer's name |
| service_category_booked | string | Service category booked |
| customer_ordered_date_time | string | Order date and time |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

## API Endpoints

### Base URL: `/api/simple-orders`

#### 1. List All Orders
- **Method:** GET
- **URL:** `/api/simple-orders`
- **Response:** Array of all orders

#### 2. Create New Order
- **Method:** POST
- **URL:** `/api/simple-orders`
- **Body:**
```json
{
    "customer_name": "John Doe",
    "service_category_booked": "Home Cleaning",
    "customer_ordered_date_time": "2025-11-28 10:30:00"
}
```

#### 3. Get Specific Order
- **Method:** GET
- **URL:** `/api/simple-orders/{id}`
- **Response:** Single order details

#### 4. Update Order
- **Method:** PUT
- **URL:** `/api/simple-orders/{id}`
- **Body:** Same as create (fields are optional)

#### 5. Delete Order
- **Method:** DELETE
- **URL:** `/api/simple-orders/{id}`
- **Response:** Success message

## API Response Format

### Success Response
```json
{
    "success": true,
    "data": {
        "id": 1,
        "customer_name": "John Doe",
        "service_category_booked": "Home Cleaning",
        "customer_ordered_date_time": "2025-11-28 10:30:00",
        "created_at": "2025-11-28T10:30:00.000000Z",
        "updated_at": "2025-11-28T10:30:00.000000Z"
    },
    "message": "Order created successfully"
}
```

### Error Response
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "customer_name": ["The customer name field is required."]
    }
}
```

## Filament Admin Interface

### Access
- **URL:** `/admin/simple-orders`
- **Features:**
  - Read-only interface
  - Search functionality
  - Sort by columns
  - View order details
  - Bulk delete operations

### Columns Displayed
1. **Customer Name** - Searchable, sortable
2. **Service Category Booked** - Searchable, sortable
3. **Order Date & Time** - Searchable, sortable
4. **Created At** - Sortable, toggleable (hidden by default)

## Installation & Setup

### 1. Run Migration
```bash
php artisan migrate
```

### 2. Test API
```bash
# Create an order
curl -X POST http://your-domain/api/simple-orders \
  -H 'Content-Type: application/json' \
  -d '{
    "customer_name": "Jane Smith",
    "service_category_booked": "Plumbing",
    "customer_ordered_date_time": "2025-11-28 14:00:00"
  }'

# Get all orders
curl -X GET http://your-domain/api/simple-orders
```

### 3. Access Admin Panel
Navigate to `/admin/simple-orders` in your browser.

## Files Created/Modified

### Models
- `app/Models/SimpleOrder.php` - Eloquent model

### Controllers
- `app/Http/Controllers/Api/SimpleOrderController.php` - API controller

### Migrations
- `database/migrations/2025_11_28_090940_create_simple_orders_table.php` - Database schema

### Filament Resources
- `app/Filament/Admin/Resources/SimpleOrderResource.php` - Admin resource
- `app/Filament/Admin/Resources/SimpleOrderResource/Pages/ListSimpleOrders.php` - List page

### Routes
- `routes/api.php` - API routes added

## Features

✅ **Standalone System** - No relationships with other models
✅ **Full REST API** - Complete CRUD operations
✅ **Validation** - Input validation with error messages
✅ **Read-only Admin** - Filament interface for viewing orders
✅ **Search & Sort** - Admin interface with search and sort functionality
✅ **String Fields** - All data fields are string type as requested
✅ **Error Handling** - Comprehensive error handling and responses

## Usage Examples

### Frontend Integration
```javascript
// Create order
const response = await fetch('/api/simple-orders', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        customer_name: 'John Doe',
        service_category_booked: 'Home Cleaning',
        customer_ordered_date_time: '2025-11-28 10:30:00'
    })
});

const result = await response.json();
console.log(result);
```

### PHP Integration
```php
use App\Models\SimpleOrder;

// Create order programmatically
$order = SimpleOrder::create([
    'customer_name' => 'John Doe',
    'service_category_booked' => 'Home Cleaning',
    'customer_ordered_date_time' => '2025-11-28 10:30:00'
]);

// Get all orders
$orders = SimpleOrder::all();
```

## Notes

- All fields are stored as strings as requested
- No authentication required for API endpoints (add if needed)
- Admin interface is read-only to prevent accidental modifications
- Orders are sorted by creation date (newest first) by default
- System is completely independent of other models and tables