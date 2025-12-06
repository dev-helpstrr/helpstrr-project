# Service Provider Search API Documentation

## Overview

The Service Provider (SP) Search API implements the exact same 38 filters used in the allocation engine but allows for standalone SP search without requiring a task. This API enables searching for service providers based on comprehensive filtering criteria including location, capabilities, ratings, and availability.

## Base URL
```
/api/v1/sp-search
```

## Endpoints

### 1. Search Service Providers

**Endpoint:** `POST /api/v1/sp-search/`

**Description:** Search for service providers based on comprehensive filtering criteria using all 38 filters from the allocation engine.

**Request Body:**
```json
{
    "latitude": 22.5726,
    "longitude": 88.3639,
    "category_id": 1,
    "subcategory_id": 1,
    "scheduled_at": "2024-12-07 14:00:00",
    "pax_count": 4,
    "cuisine_ids": [1, 2, 3],
    "dietary_preference_id": 1,
    "addon_flag_ids": [1, 2],
    "optional_flag_ids": [1, 3],
    "radius_km": 10,
    "sort_by": "quality_score",
    "limit": 20,
    "include_new_sps": true,
    "gold_level_only": false
}
```

**Required Parameters:**
- `latitude` (float): Customer latitude (-90 to 90)
- `longitude` (float): Customer longitude (-180 to 180)
- `category_id` (integer): Service category ID
- `subcategory_id` (integer): Service subcategory ID
- `scheduled_at` (datetime): Scheduled service time (must be at least 2 hours from now)

**Optional Parameters:**
- `pax_count` (integer): Number of people (1-50)
- `cuisine_ids` (array): Array of cuisine IDs for chef services
- `dietary_preference_id` (integer): Dietary preference ID
- `addon_flag_ids` (array): Array of addon flag IDs
- `optional_flag_ids` (array): Array of optional flag IDs
- `radius_km` (integer): Search radius in kilometers (1-50, default: 10)
- `sort_by` (string): Sorting criteria (distance|rating|quality_score|price, default: quality_score)
- `limit` (integer): Maximum results to return (1-100, default: 20)
- `include_new_sps` (boolean): Include new SPs with <5 ratings (default: false)
- `gold_level_only` (boolean): Only return gold-level SPs (default: false)

**Response:**
```json
{
    "success": true,
    "message": "Service providers found successfully",
    "data": {
        "service_providers": [
            {
                "id": 1,
                "sp_user_id": 1,
                "name": "Rajesh Kumar",
                "phone": "9876543220",
                "rating": 4.8,
                "total_ratings": 45,
                "is_gold_level": true,
                "acceptance_rate": 95.0,
                "punctuality_score": 92.0,
                "behaviour_score": 96.0,
                "cancellation_score": 5.0,
                "complaint_score": 2.0,
                "tasks_completed": 42,
                "distance_km": 2.5,
                "quality_score": 94.2,
                "is_available": true,
                "is_online": true,
                "last_seen_at": "2024-12-05T10:30:00Z",
                "kyc_verified": true,
                "kyc_status": "approved",
                "location": {
                    "latitude": 22.5726,
                    "longitude": 88.3639,
                    "address": "Salt Lake, Kolkata"
                },
                "capabilities": [
                    {
                        "subcategory_id": 1,
                        "subcategory_name": "Breakfast",
                        "max_pax_capacity": 10,
                        "max_travel_distance_km": 15,
                        "night_shift_available": true,
                        "is_active": true
                    }
                ],
                "cuisine_capabilities": [
                    {
                        "cuisine_id": 1,
                        "cuisine_name": "North Indian"
                    }
                ],
                "dietary_capabilities": [
                    {
                        "dietary_preference_id": 1,
                        "dietary_preference_name": "Vegetarian"
                    }
                ],
                "addon_capabilities": [
                    {
                        "addon_flag_id": 1,
                        "addon_flag_name": "Kids Compatible"
                    }
                ],
                "optional_capabilities": [
                    {
                        "optional_flag_id": 1,
                        "optional_flag_name": "SP must be vegetarian",
                        "is_hard_filter": true
                    }
                ]
            }
        ],
        "total_count": 15,
        "returned_count": 15,
        "search_params": {
            "latitude": 22.5726,
            "longitude": 88.3639,
            "category_id": 1,
            "subcategory_id": 1,
            "scheduled_at": "2024-12-07 14:00:00"
        },
        "filters_applied": {
            "filters": {
                "basic_eligibility": 12,
                "category_subcategory": 2,
                "distance": 1,
                "not_on_task": 1,
                "cuisine_match": 1,
                "dietary_preference": 1,
                "addon_flags": 2,
                "optional_flags": 2,
                "pax_capacity": 1,
                "night_shift": 0,
                "gold_level_only": 0,
                "individual_checks": 6
            },
            "total_filters_applied": 29
        },
        "search_radius_km": 10,
        "search_location": {
            "latitude": 22.5726,
            "longitude": 88.3639
        }
    }
}
```

### 2. Get Search Options

**Endpoint:** `GET /api/v1/sp-search/options`

**Description:** Get available search options including categories, cuisines, dietary preferences, and flags.

**Query Parameters:**
- `category_id` (optional): Filter subcategories by category

**Response:**
```json
{
    "success": true,
    "data": {
        "categories": [
            {
                "id": 1,
                "name": "Chef",
                "slug": "chef"
            }
        ],
        "subcategories": [
            {
                "id": 1,
                "name": "Breakfast",
                "slug": "breakfast",
                "pax_required": true
            }
        ],
        "cuisines": [
            {
                "id": 1,
                "name": "North Indian"
            }
        ],
        "dietary_preferences": [
            {
                "id": 1,
                "name": "Vegetarian"
            }
        ],
        "addon_flags": [
            {
                "id": 1,
                "name": "Kids Compatible",
                "description": "Child-friendly cooking"
            }
        ],
        "optional_flags": [
            {
                "id": 1,
                "name": "SP must be vegetarian",
                "description": "Service provider must be vegetarian",
                "is_hard_filter": true
            }
        ],
        "search_radius_options": [
            {
                "value": 5,
                "label": "5 km"
            },
            {
                "value": 10,
                "label": "10 km"
            }
        ],
        "sort_options": [
            {
                "value": "quality_score",
                "label": "Best Match (Quality Score)"
            },
            {
                "value": "distance",
                "label": "Nearest First"
            }
        ]
    }
}
```

### 3. Get Service Provider Details

**Endpoint:** `GET /api/v1/sp-search/{spId}/details`

**Description:** Get detailed information about a specific service provider.

**Path Parameters:**
- `spId` (integer): Service provider ID

**Query Parameters:**
- `latitude` (optional): Customer latitude for distance calculation
- `longitude` (optional): Customer longitude for distance calculation

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "sp_user_id": 1,
        "name": "Rajesh Kumar",
        "phone": "9876543220",
        "email": "rajesh.chef@test.com",
        "rating": 4.8,
        "total_ratings": 45,
        "is_gold_level": true,
        "acceptance_rate": 95.0,
        "punctuality_score": 92.0,
        "behaviour_score": 96.0,
        "cancellation_score": 5.0,
        "complaint_score": 2.0,
        "tasks_completed": 42,
        "tasks_cancelled": 2,
        "distance_km": 2.5,
        "quality_score": 94.2,
        "is_available": true,
        "is_online": true,
        "last_seen_at": "2024-12-05T10:30:00Z",
        "kyc_verified": true,
        "kyc_status": "approved",
        "location": {
            "latitude": 22.5726,
            "longitude": 88.3639,
            "address": "Salt Lake, Kolkata",
            "city": "Kolkata",
            "state": "West Bengal"
        },
        "capabilities": [
            {
                "subcategory_id": 1,
                "subcategory_name": "Breakfast",
                "max_pax_capacity": 10,
                "max_travel_distance_km": 15,
                "night_shift_available": true,
                "is_active": true
            }
        ],
        "cuisine_capabilities": [
            {
                "cuisine_id": 1,
                "cuisine_name": "North Indian"
            }
        ],
        "dietary_capabilities": [
            {
                "dietary_preference_id": 1,
                "dietary_preference_name": "Vegetarian"
            }
        ],
        "addon_capabilities": [
            {
                "addon_flag_id": 1,
                "addon_flag_name": "Kids Compatible"
            }
        ],
        "optional_capabilities": [
            {
                "optional_flag_id": 1,
                "optional_flag_name": "SP must be vegetarian",
                "is_hard_filter": true
            }
        ],
        "recent_tasks": [
            {
                "id": 1,
                "task_number": "TSK20241205001",
                "category": "Chef",
                "subcategory": "Lunch",
                "completed_at": "2024-12-04T14:00:00Z",
                "customer_rating": 5,
                "customer_feedback": "Excellent service!"
            }
        ]
    }
}
```

## 38 Filters Implementation

The API implements all 38 filters from the allocation engine:

### Basic Eligibility Filters (1-12)
1. **Active Status**: SP must be active (`is_active = true`)
2. **Verified Status**: SP must be verified (`is_verified = true`)
3. **KYC Approved**: SP must have approved KYC (`kyc_status = 'approved'`)
4. **Not Blocked**: SP must not be blocked or block period expired
5. **Not in Cooldown**: SP must not be in cooldown period
6. **Online Status**: SP must be online (`is_online = true`)
7. **Available Now**: SP must be available based on schedule
8. **Not Suspended**: SP must not have active suspensions
9. **Profile Complete**: SP must have complete profile
10. **Background Check**: SP must have cleared background checks
11. **Active Subscription**: SP must have active subscription (if required)
12. **Compliance Status**: SP must be compliant with platform rules

### Capability Filters (13-25)
13. **Category Match**: SP must have capability for the requested category
14. **Subcategory Match**: SP must have capability for the requested subcategory
15. **Not on Task**: SP must not be currently assigned to another task
16. **Cuisine Match**: For chef services, SP must support at least one requested cuisine
17. **Dietary Preference**: SP must support the requested dietary preference
18. **Addon Flags**: SP must support all requested addon flags
19. **Hard Optional Flags**: SP must support all hard filter optional flags
20. **Soft Optional Flags**: SP preference for soft filter optional flags
21. **Pax Capacity**: SP must support the requested number of people
22. **Night Shift**: SP must be available for night shifts if scheduled at night
23. **Travel Distance**: SP must be within their maximum travel distance
24. **Availability Window**: SP must be available during the requested time
25. **Service Area**: SP must serve the requested location

### Quality & Performance Filters (26-32)
26. **Minimum Rating**: SP must meet minimum rating requirements
27. **Rating Count**: SP must have minimum number of ratings (for rated SPs)
28. **Acceptance Rate**: SP must meet minimum acceptance rate
29. **Punctuality Score**: SP must meet minimum punctuality requirements
30. **Behavior Score**: SP must meet minimum behavior standards
31. **Cancellation Rate**: SP must have acceptable cancellation rate
32. **Complaint Score**: SP must have acceptable complaint score

### Advanced Filters (33-38)
33. **Rejection Frequency**: SP must have acceptable rejection frequency
34. **Fairness Rule**: Avoid SP monopoly by considering recent assignments
35. **Distance Priority**: Prioritize closer SPs within the same quality tier
36. **Gold Level Priority**: Prioritize gold-level SPs when requested
37. **New SP Inclusion**: Include/exclude new SPs based on customer preference
38. **Load Balancing**: Consider SP's current workload for fair distribution

## Sorting Options

### Quality Score Sorting (Default)
SPs are sorted by a weighted quality score considering:
1. **Rating** (30% weight)
2. **Acceptance Rate** (20% weight)
3. **Punctuality Score** (20% weight)
4. **Behavior Score** (15% weight)
5. **Gold Level Priority** (10% weight)
6. **Cancellation Score** (5% weight - lower is better)

### Other Sorting Options
- **Distance**: Nearest first
- **Rating**: Highest rated first
- **Price**: Lowest price first (when implemented)

## Error Responses

### Validation Error (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "latitude": ["The latitude field is required."],
        "scheduled_at": ["The scheduled at must be a date after 2024-12-05 12:30:00."]
    }
}
```

### No Results (200)
```json
{
    "success": true,
    "message": "No service providers found matching the criteria",
    "data": {
        "service_providers": [],
        "total_count": 0,
        "search_params": {...},
        "filters_applied": {...}
    }
}
```

### Server Error (500)
```json
{
    "success": false,
    "message": "Service provider search failed",
    "error": "Database connection failed"
}
```

## Testing

### Setup Test Data
```bash
# Run migrations
php artisan migrate

# Seed test data
php artisan db:seed --class=SPSearchTestDataSeeder
```

### Sample cURL Requests

#### Search Service Providers
```bash
curl -X POST http://localhost:8000/api/v1/sp-search/ \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": 22.5726,
    "longitude": 88.3639,
    "category_id": 1,
    "subcategory_id": 1,
    "scheduled_at": "2024-12-07 14:00:00",
    "pax_count": 4,
    "radius_km": 10,
    "sort_by": "quality_score",
    "limit": 10
  }'
```

#### Get Search Options
```bash
curl -X GET http://localhost:8000/api/v1/sp-search/options
```

#### Get SP Details
```bash
curl -X GET "http://localhost:8000/api/v1/sp-search/1/details?latitude=22.5726&longitude=88.3639"
```

## Performance Considerations

1. **Database Indexing**: Ensure proper indexes on frequently queried fields
2. **Caching**: Implement Redis caching for search options and frequent queries
3. **Pagination**: Use limit parameter to control response size
4. **Geographic Queries**: Optimize spatial queries for distance calculations
5. **Query Optimization**: Use eager loading for relationships to avoid N+1 queries

## Security

1. **Rate Limiting**: Implement rate limiting to prevent abuse
2. **Input Validation**: All inputs are validated using Laravel's validation rules
3. **SQL Injection Prevention**: Using Eloquent ORM prevents SQL injection
4. **Data Sanitization**: All output is properly sanitized
5. **Authentication**: Consider adding authentication for production use

## Future Enhancements

1. **Real-time Availability**: WebSocket integration for real-time SP status
2. **Machine Learning**: AI-based SP recommendations
3. **Advanced Filtering**: More granular filtering options
4. **Bulk Operations**: Batch search capabilities
5. **Analytics**: Search analytics and reporting
6. **Mobile Optimization**: Mobile-specific optimizations
7. **Multi-language Support**: Internationalization support