# Setup Instructions

After pulling the latest changes from the `helpstrr_01_12_25` branch, run these commands:

## 1. Install Dependencies
```bash
composer install
```

## 2. Database Setup
```bash
# Fresh migration (this will reset your database)
php artisan migrate:fresh

# Run the comprehensive seeder to populate test data
php artisan db:seed --class=ComprehensiveTestDataSeeder
```

## 3. Start the Development Server
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## 4. Test the APIs

### Customer Login
```bash
curl -X POST "http://localhost:8000/api/v1/customer/login" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "9876543210",
    "password": "password123"
  }'
```

### Browse Services
```bash
curl -X GET "http://localhost:8000/api/v1/services" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "9876543210",
    "token": "YOUR_TOKEN_FROM_LOGIN"
  }'
```

### Create Booking
```bash
curl -X POST "http://localhost:8000/api/v1/unified-booking" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "9876543210",
    "token": "YOUR_TOKEN_FROM_LOGIN",
    "service_id": 1,
    "customer_address_id": 1,
    "pax_count": 4,
    "requested_hours": 3,
    "dates": ["2025-12-02"],
    "start_time": "10:00",
    "end_time": "13:00",
    "recurrence_type": "one_time",
    "special_instructions": "Vegetarian meals preferred",
    "is_instant": false
  }'
```

### View Customer Orders
```bash
curl -X GET "http://localhost:8000/api/v1/customer/orders" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "9876543210",
    "token": "YOUR_TOKEN_FROM_LOGIN"
  }'
```

## Test Data Available

### Customers:
- Phone: 9876543210, Password: password123
- Phone: 9876543211, Password: password123  
- Phone: 9876543212, Password: password123

### Services:
- 12 different services across 4 categories
- Home Services, Food Services, Auto Services, Professional Services

### Addresses:
- 3 customer addresses in Mumbai, Delhi, Bangalore

## Important Notes

1. **Database Reset**: The `migrate:fresh` command will delete all existing data
2. **Sanctum Removed**: Laravel Sanctum has been completely removed
3. **Custom Authentication**: Now using database-based token validation
4. **Unified Booking**: Single API endpoint for booking any service
5. **Test Data**: Comprehensive seeder provides realistic test data

## Documentation Files

- `API_TESTING_SUMMARY.md` - Complete API testing results
- `CATEGORY_SYSTEMS_ANALYSIS.md` - Analysis of dual category systems
- `MOBILE_UI_MOCKUPS.md` - Mobile UI wireframes and API integration

## Troubleshooting

If you encounter any issues:

1. **Composer Issues**: Run `composer update` if install fails
2. **Database Issues**: Ensure SQLite is available or configure MySQL
3. **Permission Issues**: Check file permissions on storage and bootstrap/cache
4. **Port Issues**: Change port if 8000 is occupied: `php artisan serve --port=8001`

## What's New

✅ Sanctum completely removed
✅ Custom token validation implemented
✅ Unified booking API created
✅ Comprehensive test data seeder
✅ All API flows tested and working
✅ Mobile UI mockups created
✅ Category systems documented