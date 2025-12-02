# Helpstrr Service Booking & Task Management API Documentation

## Overview

This API provides comprehensive service booking and task management functionality for the Helpstrr platform. It supports the complete flow from service booking to task completion, including customer order management and service provider task handling.

**Base URL:** `https://your-domain.com/api/v1`

**Authentication:** Bearer Token (Sanctum)

**Content-Type:** `application/json`

---

## 🔗 API Endpoints Summary

### Service Booking APIs
- `POST /bookings` - Create new service booking
- `POST /bookings/pricing-preview` - Get pricing preview
- `GET /bookings/services` - Get available services

### Task Management APIs
- `PUT /tasks/{taskId}/status` - Update task status
- `POST /tasks/{taskId}/cancel` - Cancel task
- `POST /tasks/{taskId}/rate` - Rate task
- `GET /tasks/{taskId}` - Get task details

### Customer Order APIs
- `GET /customer/orders` - Get customer orders
- `GET /customer/orders/{orderId}` - Get order details
- `GET /customer/orders/statistics` - Get order statistics
- `GET /customer/orders/upcoming` - Get upcoming orders

### Service Provider Task APIs
- `GET /sp/dashboard` - Get SP dashboard
- `GET /sp/tasks` - Get assigned tasks
- `POST /sp/task-requests/{broadcastId}/accept` - Accept task request
- `POST /sp/task-requests/{broadcastId}/reject` - Reject task request
- `GET /sp/earnings` - Get earnings summary
- `PUT /sp/availability` - Update availability

---

## 📋 Service Booking APIs

### 1. Create Service Booking

**Endpoint:** `POST /api/v1/bookings`

**Description:** Creates a new service booking with automatic pricing calculation and provider allocation.

**Request Body:**
```json
{
  "customer_id": 123,
  "customer_address_id": 456,
  "category_id": 1,
  "subcategory_id": 5,
  "service_id": 12,
  "pax_count": 4,
  "requested_hours": 3,
  "dates": ["2025-11-25"],
  "start_time": "10:00",
  "end_time": "13:00",
  "recurrence_type": "one_time",
  "dietary_preference_id": 2,
  "special_instructions": "Please bring vegetarian ingredients",
  "selected_cuisines": [1, 3, 5],
  "addon_flags": [2, 4],
  "optional_flags": [1],
  "is_instant": false,
  "scheduled_at": "2025-11-25T10:00:00Z"
}
```

**Required Fields:**
- `customer_id` (integer) - Customer ID
- `customer_address_id` (integer) - Service address ID
- `category_id` (integer) - Service category ID
- `subcategory_id` (integer) - Service subcategory ID
- `service_id` (integer) - Specific service ID
- `requested_hours` (integer, 1-24) - Duration in hours
- `dates` (array) - Service dates
- `start_time` (string, HH:mm) - Start time
- `end_time` (string, HH:mm) - End time
- `recurrence_type` (enum) - one_time, two_days, three_days

**Optional Fields:**
- `pax_count` (integer, 1-50) - Number of people
- `dietary_preference_id` (integer) - Dietary preference
- `special_instructions` (string, max 1000) - Special instructions
- `selected_cuisines` (array) - Cuisine IDs for chef services
- `addon_flags` (array) - Additional service flags
- `optional_flags` (array) - Optional service flags
- `is_instant` (boolean) - Instant booking flag
- `scheduled_at` (datetime) - Scheduled date/time

**Success Response (201):**
```json
{
  "success": true,
  "message": "Booking created successfully",
  "data": {
    "task": {
      "id": 789,
      "task_number": "TSK20251125001",
      "status": "searching",
      "status_badge": {
        "text": "Searching",
        "color": "warning"
      },
      "customer": {
        "id": 123,
        "name": "John Doe",
        "phone": "+91-9876543210"
      },
      "address": {
        "id": 456,
        "address_line_1": "123 Main Street",
        "city": "Kolkata",
        "state": "West Bengal",
        "pincode": "700001",
        "latitude": 22.5726,
        "longitude": 88.3639
      },
      "service": {
        "category": "Chef",
        "subcategory": "Home Chef",
        "service": "Indian Cuisine Chef"
      },
      "details": {
        "pax_count": 4,
        "requested_hours": 3,
        "billable_hours": 3,
        "dates": ["2025-11-25"],
        "start_time": "10:00",
        "end_time": "13:00",
        "recurrence_type": "one_time",
        "special_instructions": "Please bring vegetarian ingredients"
      },
      "pricing": {
        "total_amount": 1500.00,
        "gst_amount": 270.00,
        "final_amount": 1770.00
      },
      "schedule": {
        "scheduled_at": "2025-11-25T10:00:00Z",
        "assigned_at": null,
        "started_at": null,
        "completed_at": null
      },
      "service_provider": null,
      "created_at": "2025-11-21T08:30:00Z",
      "updated_at": "2025-11-21T08:30:00Z"
    },
    "pricing": {
      "base_amount": {
        "label": "Base Amount",
        "value": 1200.00,
        "details": "₹400/hr × 3 hrs"
      },
      "consultation_fee": {
        "label": "Consultation Fee",
        "value": 300.00,
        "details": "One-time consultation charges"
      },
      "subtotal": {
        "label": "Subtotal (Excl. GST)",
        "value": 1500.00,
        "details": "Total before GST"
      },
      "gst": {
        "label": "GST (18%)",
        "value": 270.00,
        "details": "Goods and Services Tax"
      },
      "total": {
        "label": "Total Amount",
        "value": 1770.00,
        "details": "Final amount including all charges"
      }
    },
    "estimated_assignment_time": "5-10 minutes"
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "customer_id": ["The customer id field is required."],
    "scheduled_at": ["Scheduled bookings must be at least 2 hours in advance"]
  }
}
```

---

### 2. Get Pricing Preview

**Endpoint:** `POST /api/v1/bookings/pricing-preview`

**Description:** Calculate pricing for a service without creating a booking.

**Request Body:**
```json
{
  "category_id": 1,
  "subcategory_id": 5,
  "service_id": 12,
  "pax_count": 4,
  "requested_hours": 3,
  "scheduled_at": "2025-11-25T10:00:00Z",
  "customer_latitude": 22.5726,
  "customer_longitude": 88.3639
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Pricing calculated successfully",
  "data": {
    "pricing": {
      "base_amount": 1200.00,
      "hourly_rate": 400.00,
      "billable_hours": 3,
      "night_adjustment": 0,
      "festive_surge_percentage": 0,
      "festive_adjustment": 0,
      "weather_surge_percentage": 15,
      "weather_adjustment": 180.00,
      "consultation_fee": 300.00,
      "total_excl_gst": 1680.00,
      "gst_percentage": 18,
      "gst_amount": 302.40,
      "total_incl_gst": 1982.40
    },
    "breakdown": [
      {
        "label": "Base Amount",
        "amount": 1200.00,
        "type": "base",
        "details": "₹400/hr × 3 hrs"
      },
      {
        "label": "Weather Surge",
        "amount": 180.00,
        "type": "surge",
        "details": "15% weather surcharge"
      }
    ],
    "estimated_duration": "3 hours",
    "surge_info": {
      "is_surge_active": true,
      "surge_factors": [
        {
          "type": "weather",
          "label": "Monsoon Surge",
          "percentage": 15,
          "reason": "Additional charges during monsoon season"
        }
      ],
      "total_surge_percentage": 15
    }
  }
}
```

---

### 3. Get Available Services

**Endpoint:** `GET /api/v1/bookings/services`

**Description:** Get list of available service categories, subcategories, and services.

**Query Parameters:**
- `category_id` (optional) - Filter by category
- `latitude` (optional) - Customer latitude
- `longitude` (optional) - Customer longitude
- `search` (optional) - Search term

**Success Response (200):**
```json
{
  "success": true,
  "message": "Services retrieved successfully",
  "data": {
    "categories": [
      {
        "id": 1,
        "name": "Chef",
        "slug": "chef",
        "description": "Professional cooking services",
        "icon": "chef-icon.png",
        "subcategories": [
          {
            "id": 5,
            "name": "Home Chef",
            "slug": "home-chef",
            "description": "Personal chef for home cooking",
            "icon": "home-chef-icon.png",
            "pax_required": true,
            "is_event_category": false,
            "consultation_fee": 300.00,
            "services": [
              {
                "id": 12,
                "name": "Indian Cuisine Chef",
                "slug": "indian-cuisine-chef",
                "description": "Expert in Indian cooking",
                "base_price": 300.00,
                "hourly_rate": 400.00,
                "min_hours": 2,
                "max_hours": 8
              }
            ]
          }
        ]
      }
    ],
    "total_categories": 3
  }
}
```

---

## 🔧 Task Management APIs

### 1. Update Task Status

**Endpoint:** `PUT /api/v1/tasks/{taskId}/status`

**Description:** Update the status of a task with proper validation and notifications.

**Request Body:**
```json
{
  "status": "started",
  "sp_id": 456,
  "latitude": 22.5726,
  "longitude": 88.3639,
  "notes": "Service started on time",
  "otp": "123456"
}
```

**Required Fields:**
- `status` (enum) - New status: assigned, on_the_way, arrived, otp_start_verified, started, paused, resumed, completed, cancelled

**Conditional Fields:**
- `sp_id` (integer) - Required for 'assigned' status
- `otp` (string, 6 digits) - Required for 'otp_start_verified' and 'completed' status
- `latitude`, `longitude` (float) - Optional location data
- `notes` (string) - Required for 'paused' status
- `cancellation_reason` (string) - Required for 'cancelled' status
- `cancelled_by` (enum) - Required for 'cancelled' status: customer, service_provider, admin

**Success Response (200):**
```json
{
  "success": true,
  "message": "Task status updated successfully",
  "data": {
    "task": {
      "id": 789,
      "task_number": "TSK20251125001",
      "status": "started",
      "status_badge": {
        "text": "In Progress",
        "color": "success"
      },
      "started_at": "2025-11-25T10:15:00Z"
    },
    "next_actions": [
      {
        "action": "update_status",
        "status": "paused",
        "label": "Pause Task"
      },
      {
        "action": "update_status",
        "status": "completed",
        "label": "Complete Task"
      }
    ]
  }
}
```

---

### 2. Cancel Task

**Endpoint:** `POST /api/v1/tasks/{taskId}/cancel`

**Description:** Cancel a task with proper authorization and charge calculation.

**Request Body:**
```json
{
  "reason": "Customer emergency",
  "cancelled_by": "customer",
  "user_id": 123,
  "user_type": "customer"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Task cancelled successfully",
  "data": {
    "task_id": 789,
    "task_number": "TSK20251125001",
    "cancellation_charges": 442.50,
    "refund_amount": 1327.50,
    "cancelled_at": "2025-11-25T09:45:00Z"
  }
}
```

---

### 3. Rate Task

**Endpoint:** `POST /api/v1/tasks/{taskId}/rate`

**Description:** Submit rating and feedback for a completed task.

**Request Body:**
```json
{
  "rating": 5,
  "feedback": "Excellent service! Very professional and punctual.",
  "rated_by": "customer",
  "user_id": 123
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Task rated successfully",
  "data": {
    "task_id": 789,
    "task_number": "TSK20251125001",
    "customer_rating": 5,
    "customer_feedback": "Excellent service! Very professional and punctual.",
    "sp_rating": null,
    "sp_feedback": null,
    "status": "rated"
  }
}
```

---

## 👤 Customer Order APIs

### 1. Get Customer Orders

**Endpoint:** `GET /api/v1/customer/orders`

**Description:** Get paginated list of customer's orders with filtering options.

**Query Parameters:**
- `customer_id` (required) - Customer ID
- `status` (optional) - all, active, completed, cancelled
- `page` (optional) - Page number (default: 1)
- `per_page` (optional) - Items per page (default: 10, max: 50)
- `sort_by` (optional) - created_at, scheduled_at, completed_at
- `sort_order` (optional) - asc, desc
- `date_from` (optional) - Filter from date
- `date_to` (optional) - Filter to date

**Success Response (200):**
```json
{
  "success": true,
  "message": "Orders retrieved successfully",
  "data": {
    "orders": [
      {
        "id": 789,
        "order_number": "TSK20251125001",
        "status": "completed",
        "status_badge": {
          "text": "Completed",
          "color": "success"
        },
        "service": {
          "category": "Chef",
          "subcategory": "Home Chef",
          "service": "Indian Cuisine Chef",
          "category_icon": "chef-icon.png"
        },
        "address": {
          "address_line_1": "123 Main Street",
          "city": "Kolkata",
          "pincode": "700001"
        },
        "schedule": {
          "scheduled_at": "2025-11-25T10:00:00Z",
          "scheduled_date": "2025-11-25",
          "scheduled_time": "10:00",
          "start_time": "10:00",
          "end_time": "13:00",
          "duration_hours": 3
        },
        "pricing": {
          "total_amount": 1500.00,
          "gst_amount": 270.00,
          "final_amount": 1770.00,
          "currency": "INR"
        },
        "service_provider": {
          "id": 456,
          "name": "Chef Ramesh",
          "phone": "+91-9876543210",
          "rating": 4.8,
          "profile_image": "chef-ramesh.jpg"
        },
        "ratings": {
          "customer_rating": 5,
          "customer_feedback": "Excellent service!",
          "can_rate": false
        },
        "created_at": "2025-11-21T08:30:00Z",
        "updated_at": "2025-11-25T13:30:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 25,
      "last_page": 3,
      "from": 1,
      "to": 10
    },
    "statistics": {
      "total_orders": 25,
      "completed_orders": 20,
      "active_orders": 2,
      "cancelled_orders": 3,
      "total_spent": 35400.00,
      "average_rating_given": 4.6
    }
  }
}
```

---

### 2. Get Order Details

**Endpoint:** `GET /api/v1/customer/orders/{orderId}`

**Description:** Get detailed information about a specific order.

**Query Parameters:**
- `customer_id` (required) - Customer ID for authorization

**Success Response (200):**
```json
{
  "success": true,
  "message": "Order details retrieved successfully",
  "data": {
    "order": {
      "id": 789,
      "order_number": "TSK20251125001",
      "status": "completed",
      "details": {
        "pax_count": 4,
        "requested_hours": 3,
        "billable_hours": 3,
        "dates": ["2025-11-25"],
        "recurrence_type": "one_time",
        "special_instructions": "Please bring vegetarian ingredients"
      },
      "address_full": {
        "address_line_1": "123 Main Street",
        "address_line_2": "Near City Mall",
        "landmark": "Opposite Metro Station",
        "city": "Kolkata",
        "state": "West Bengal",
        "pincode": "700001",
        "latitude": 22.5726,
        "longitude": 88.3639
      },
      "selected_cuisines": [
        {"id": 1, "name": "North Indian"},
        {"id": 3, "name": "Bengali"}
      ],
      "price_breakdown": [
        {
          "label": "Base Amount",
          "amount": 1200.00,
          "type": "base"
        },
        {
          "label": "Consultation Fee",
          "amount": 300.00,
          "type": "fee"
        }
      ],
      "otp": {
        "start_otp": "123456",
        "end_otp": "789012",
        "otp_start_verified_at": "2025-11-25T10:05:00Z",
        "otp_end_verified_at": "2025-11-25T13:10:00Z"
      }
    },
    "timeline": [
      {
        "status": "requested",
        "label": "Order Placed",
        "description": "Your booking request has been received",
        "timestamp": "2025-11-21T08:30:00Z",
        "completed": true
      },
      {
        "status": "assigned",
        "label": "Service Provider Assigned",
        "description": "A service provider has been assigned to your order",
        "timestamp": "2025-11-21T08:45:00Z",
        "completed": true
      }
    ],
    "actions": [
      {
        "action": "book_again",
        "label": "Book Again",
        "type": "secondary"
      }
    ]
  }
}
```

---

## 🏢 Service Provider Task APIs

### 1. Get SP Dashboard

**Endpoint:** `GET /api/v1/sp/dashboard`

**Description:** Get comprehensive dashboard data for service provider.

**Query Parameters:**
- `sp_id` (required) - Service Provider ID

**Success Response (200):**
```json
{
  "success": true,
  "message": "Dashboard data retrieved successfully",
  "data": {
    "service_provider": {
      "id": 456,
      "name": "Chef Ramesh",
      "phone": "+91-9876543210",
      "rating": 4.8,
      "total_ratings": 150,
      "is_online": true,
      "is_available": true,
      "kyc_verified": true,
      "is_gold_level": true
    },
    "active_task": {
      "id": 789,
      "task_number": "TSK20251125001",
      "status": "started",
      "customer": {
        "id": 123,
        "name": "John Doe",
        "phone": "+91-9876543210"
      },
      "address": {
        "address_line_1": "123 Main Street",
        "city": "Kolkata",
        "latitude": 22.5726,
        "longitude": 88.3639
      },
      "service": {
        "category": "Chef",
        "subcategory": "Home Chef",
        "service": "Indian Cuisine Chef"
      },
      "details": {
        "pax_count": 4,
        "requested_hours": 3,
        "special_instructions": "Please bring vegetarian ingredients"
      },
      "schedule": {
        "scheduled_at": "2025-11-25T10:00:00Z",
        "start_time": "10:00",
        "end_time": "13:00",
        "started_at": "2025-11-25T10:15:00Z"
      },
      "earnings": {
        "total_amount": 1770.00,
        "sp_payout": 1416.00
      },
      "otp": {
        "start_otp": "123456",
        "end_otp": "789012"
      }
    },
    "todays_tasks": [
      {
        "id": 790,
        "task_number": "TSK20251125002",
        "status": "assigned",
        "scheduled_at": "2025-11-25T16:00:00Z"
      }
    ],
    "pending_requests": [
      {
        "broadcast_id": 101,
        "task_id": 791,
        "task_number": "TSK20251125003",
        "customer": {
          "name": "Jane Smith",
          "phone": "987654XXXX"
        },
        "address": {
          "area": "Salt Lake",
          "pincode": "700064",
          "distance_km": 2.5
        },
        "service": {
          "category": "Chef",
          "subcategory": "Event Chef",
          "service": "Party Catering"
        },
        "details": {
          "pax_count": 20,
          "requested_hours": 4
        },
        "earnings": {
          "estimated_payout": 3200.00
        },
        "expires_at": "2025-11-21T09:15:00Z",
        "time_remaining": 180
      }
    ],
    "earnings": {
      "today": 1416.00,
      "this_week": 8500.00,
      "this_month": 35000.00,
      "total": 125000.00
    },
    "metrics": {
      "rating": 4.8,
      "total_ratings": 150,
      "acceptance_rate": 92.5,
      "punctuality_score": 95.0,
      "behaviour_score": 98.0,
      "cancellation_score": 2.1,
      "tasks_completed": 145,
      "tasks_cancelled": 3,
      "quality_score": 94.2,
      "is_gold_level": true
    }
  }
}
```

---

### 2. Accept Task Request

**Endpoint:** `POST /api/v1/sp/task-requests/{broadcastId}/accept`

**Description:** Accept a task broadcast request.

**Request Body:**
```json
{
  "sp_id": 456,
  "latitude": 22.5726,
  "longitude": 88.3639
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Task accepted successfully",
  "data": {
    "task": {
      "id": 791,
      "task_number": "TSK20251125003",
      "status": "assigned",
      "assigned_at": "2025-11-21T09:10:00Z"
    },
    "next_actions": [
      {
        "action": "start_journey",
        "label": "Start Journey"
      },
      {
        "action": "cancel_task",
        "label": "Cancel Task"
      }
    ]
  }
}
```

---

### 3. Get Earnings

**Endpoint:** `GET /api/v1/sp/earnings`

**Description:** Get detailed earnings information for service provider.

**Query Parameters:**
- `sp_id` (required) - Service Provider ID
- `period` (optional) - today, week, month, year

**Success Response (200):**
```json
{
  "success": true,
  "message": "Earnings retrieved successfully",
  "data": {
    "period": "month",
    "start_date": "2025-11-01",
    "end_date": "2025-11-30",
    "total_earnings": 35000.00,
    "total_tasks": 25,
    "total_hours": 75,
    "average_per_task": 1400.00,
    "average_per_hour": 466.67,
    "earnings_by_category": [
      {
        "category": "Chef",
        "tasks": 20,
        "earnings": 28000.00
      },
      {
        "category": "House Help",
        "tasks": 5,
        "earnings": 7000.00
      }
    ]
  }
}
```

---

## 📊 Response Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Internal Server Error |

---

## 🔐 Authentication

All API endpoints require authentication using Bearer tokens (Laravel Sanctum).

**Header:**
```
Authorization: Bearer {your-token-here}
```

---

## 📝 Error Response Format

All error responses follow this format:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

---

## 🚀 Rate Limiting

- **General APIs:** 60 requests per minute
- **Booking APIs:** 10 requests per minute
- **Status Update APIs:** 30 requests per minute

---

## 📱 Mobile App Integration Notes

### For Customer App:
1. Use booking APIs to create service requests
2. Use customer order APIs to show order history and status
3. Implement real-time status updates using WebSocket or polling
4. Use rating APIs after service completion

### For Service Provider App:
1. Use SP dashboard API for home screen
2. Implement task request notifications
3. Use task status update APIs for workflow
4. Show earnings and performance metrics

### Real-time Updates:
- Implement WebSocket connections for live status updates
- Use push notifications for critical status changes
- Poll dashboard APIs every 30 seconds when app is active

---

## 🔧 Testing

### Postman Collection
A complete Postman collection is available with all endpoints, sample requests, and environment variables.

### Test Environment
- **Base URL:** `https://test-api.helpstrr.com/api/v1`
- **Test Customer ID:** 1
- **Test SP ID:** 1

---

## 📞 Support

For API support and questions:
- **Email:** api-support@helpstrr.com
- **Documentation:** https://docs.helpstrr.com
- **Status Page:** https://status.helpstrr.com