# Mobile UI Mockups and API Flow

## 1. Customer Login Flow

### Screen 1: Login Screen
```
┌─────────────────────────────────┐
│  🏠 Helpstrr                    │
├─────────────────────────────────┤
│                                 │
│     Welcome Back!               │
│                                 │
│  ┌─────────────────────────────┐ │
│  │ 📱 Phone Number             │ │
│  │ +91 |__________________|    │ │
│  └─────────────────────────────┘ │
│                                 │
│  ┌─────────────────────────────┐ │
│  │ 🔑 Password                 │ │
│  │ |________________________| │ │
│  └─────────────────────────────┘ │
│                                 │
│  ┌─────────────────────────────┐ │
│  │        LOGIN                │ │
│  └─────────────────────────────┘ │
│                                 │
│     Forgot Password?            │
│     Don't have account? Sign Up │
│                                 │
└─────────────────────────────────┘
```

**API Call:**
```
POST /api/v1/customer/login
{
  "phone": "9876543210",
  "password": "password123"
}

Response:
{
  "success": true,
  "message": "Login successful",
  "data": {
    "customer": {...},
    "token": "b2480e404c88908fbcacf77b4bcda86d352835a16642473666e48a0496ca9ee1"
  }
}
```

## 2. Service Selection Flow

### Screen 2: Home/Categories Screen
```
┌─────────────────────────────────┐
│  🏠 Helpstrr        🔔 👤       │
├─────────────────────────────────┤
│                                 │
│  Hi John! What do you need?     │
│                                 │
│  ┌─────────┐ ┌─────────┐        │
│  │ 🏠 Home │ │ 🍽️ Food │        │
│  │Services │ │Services │        │
│  └─────────┘ └─────────┘        │
│                                 │
│  ┌─────────┐ ┌─────────┐        │
│  │ 🚗 Auto │ │ 💼 Prof │        │
│  │Services │ │Services │        │
│  └─────────┘ └─────────┘        │
│                                 │
│  Recent Bookings:               │
│  ┌─────────────────────────────┐ │
│  │ 🍽️ Daily Meal Prep         │ │
│  │ Dec 2, 10:00 AM             │ │
│  │ Status: No Providers        │ │
│  └─────────────────────────────┘ │
│                                 │
└─────────────────────────────────┘
```

**API Call:**
```
GET /api/v1/services
Headers: {
  "phone": "9876543210",
  "token": "b2480e404c88908fbcacf77b4bcda86d352835a16642473666e48a0496ca9ee1"
}

Response: Hierarchical categories/subcategories/services data
```

### Screen 3: Service Details Screen
```
┌─────────────────────────────────┐
│  ← Daily Meal Preparation       │
├─────────────────────────────────┤
│                                 │
│  🍽️ [Service Image]             │
│                                 │
│  Daily Meal Preparation         │
│  ⭐ 4.8 (234 reviews)           │
│                                 │
│  💰 ₹200/hour                   │
│  ⏱️ Min 2 hours                 │
│  👥 1-6 people                  │
│                                 │
│  📝 Description:                │
│  Professional meal preparation  │
│  service for your daily needs.  │
│  Fresh ingredients, customized  │
│  to your preferences.           │
│                                 │
│  ✅ What's included:            │
│  • Meal planning                │
│  • Fresh ingredients           │
│  • Cooking & preparation       │
│  • Kitchen cleanup             │
│                                 │
│  ┌─────────────────────────────┐ │
│  │        BOOK NOW             │ │
│  └─────────────────────────────┘ │
│                                 │
└─────────────────────────────────┘
```

## 3. Booking Flow

### Screen 4: Booking Details Screen
```
┌─────────────────────────────────┐
│  ← Book Service                 │
├─────────────────────────────────┤
│                                 │
│  📍 Service Address             │
│  ┌─────────────────────────────┐ │
│  │ 🏠 123 Main Street          │ │
│  │    Mumbai, 400001           │ │
│  │    Change Address ▼         │ │
│  └─────────────────────────────┘ │
│                                 │
│  📅 When do you need it?        │
│  ┌─────────────────────────────┐ │
│  │ Date: Dec 2, 2025 ▼         │ │
│  └─────────────────────────────┘ │
│                                 │
│  ⏰ Time                        │
│  ┌─────────┐ ┌─────────┐        │
│  │Start    │ │End      │        │
│  │10:00 AM │ │1:00 PM  │        │
│  └─────────┘ └─────────┘        │
│                                 │
│  👥 Number of people            │
│  ┌─────────────────────────────┐ │
│  │ 4 people ▼                  │ │
│  └─────────────────────────────┘ │
│                                 │
│  📝 Special Instructions        │
│  ┌─────────────────────────────┐ │
│  │ Vegetarian meals preferred  │ │
│  └─────────────────────────────┘ │
│                                 │
│  ┌─────────────────────────────┐ │
│  │     CONTINUE                │ │
│  └─────────────────────────────┘ │
│                                 │
└─────────────────────────────────┘
```

### Screen 5: Booking Confirmation Screen
```
┌─────────────────────────────────┐
│  ← Confirm Booking              │
├─────────────────────────────────┤
│                                 │
│  🍽️ Daily Meal Preparation     │
│                                 │
│  📍 123 Main Street, Mumbai     │
│  📅 Dec 2, 2025                │
│  ⏰ 10:00 AM - 1:00 PM (3 hrs)  │
│  👥 4 people                    │
│                                 │
│  💰 Price Breakdown:            │
│  ┌─────────────────────────────┐ │
│  │ Base Amount    ₹1,100.00    │ │
│  │ Weather Surge    ₹110.00    │ │
│  │ Subtotal       ₹1,210.00    │ │
│  │ GST (18%)        ₹217.80    │ │
│  │ ─────────────────────────   │ │
│  │ Total          ₹1,427.80    │ │
│  └─────────────────────────────┘ │
│                                 │
│  ⚡ Estimated assignment:       │
│     5-10 minutes                │
│                                 │
│  ┌─────────────────────────────┐ │
│  │     CONFIRM BOOKING         │ │
│  └─────────────────────────────┘ │
│                                 │
└─────────────────────────────────┘
```

**API Call:**
```
POST /api/v1/unified-booking
{
  "phone": "9876543210",
  "token": "b2480e404c88908fbcacf77b4bcda86d352835a16642473666e48a0496ca9ee1",
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
}
```

## 4. Order History Flow

### Screen 6: My Orders Screen
```
┌─────────────────────────────────┐
│  ← My Orders            🔍      │
├─────────────────────────────────┤
│                                 │
│  📊 Quick Stats                 │
│  ┌─────────┐ ┌─────────┐        │
│  │ Total   │ │ Active  │        │
│  │   2     │ │   0     │        │
│  └─────────┘ └─────────┘        │
│                                 │
│  📋 Recent Orders               │
│                                 │
│  ┌─────────────────────────────┐ │
│  │ 🍽️ Daily Meal Prep         │ │
│  │ TSK202512013039             │ │
│  │ Dec 2, 10:00 AM             │ │
│  │ 🔴 No Providers Available   │ │
│  │ ₹1,427.80                   │ │
│  └─────────────────────────────┘ │
│                                 │
│  ┌─────────────────────────────┐ │
│  │ 🍽️ Daily Meal Prep         │ │
│  │ TSK202512013572             │ │
│  │ Dec 2, 10:00 AM             │ │
│  │ 🔵 Requested                │ │
│  │ ₹1,416.00                   │ │
│  └─────────────────────────────┘ │
│                                 │
│  ┌─────────────────────────────┐ │
│  │        LOAD MORE            │ │
│  └─────────────────────────────┘ │
│                                 │
└─────────────────────────────────┘
```

**API Call:**
```
GET /api/v1/customer/orders
{
  "phone": "9876543210",
  "token": "b2480e404c88908fbcacf77b4bcda86d352835a16642473666e48a0496ca9ee1"
}
```

### Screen 7: Order Details Screen
```
┌─────────────────────────────────┐
│  ← Order Details                │
├─────────────────────────────────┤
│                                 │
│  Order #TSK202512013039         │
│  🔴 No Providers Available      │
│                                 │
│  🍽️ Daily Meal Preparation     │
│  📍 123 Main Street, Mumbai     │
│  📅 Dec 2, 2025                │
│  ⏰ 10:00 AM - 1:00 PM          │
│  👥 4 people                    │
│                                 │
│  💰 Payment Details:            │
│  ┌─────────────────────────────┐ │
│  │ Total Amount   ₹1,427.80    │ │
│  │ Payment Status: Pending     │ │
│  └─────────────────────────────┘ │
│                                 │
│  📝 Special Instructions:       │
│  Vegetarian meals preferred     │
│                                 │
│  📞 Need Help?                  │
│  ┌─────────────────────────────┐ │
│  │     CONTACT SUPPORT         │ │
│  └─────────────────────────────┘ │
│                                 │
│  ┌─────────────────────────────┐ │
│  │     CANCEL ORDER            │ │
│  └─────────────────────────────┘ │
│                                 │
└─────────────────────────────────┘
```

## 5. Service Provider App Flow

### Screen 8: SP Dashboard
```
┌─────────────────────────────────┐
│  🏠 SP Dashboard        🔔 ⚙️   │
├─────────────────────────────────┤
│                                 │
│  Hi Provider! 👋               │
│                                 │
│  📊 Today's Summary             │
│  ┌─────────┐ ┌─────────┐        │
│  │ Tasks   │ │ Earnings│        │
│  │   0     │ │  ₹0     │        │
│  └─────────┘ └─────────┘        │
│                                 │
│  🟢 Available for work          │
│  ┌─────────────────────────────┐ │
│  │ Toggle Availability         │ │
│  └─────────────────────────────┘ │
│                                 │
│  📋 My Tasks                    │
│  ┌─────────────────────────────┐ │
│  │ No tasks assigned yet       │ │
│  │                             │ │
│  │ You'll receive notifications│ │
│  │ when new tasks are available│ │
│  └─────────────────────────────┘ │
│                                 │
│  ┌─────────────────────────────┐ │
│  │     VIEW ALL TASKS          │ │
│  └─────────────────────────────┘ │
│                                 │
└─────────────────────────────────┘
```

**API Call:**
```
GET /api/v1/sp/tasks
{
  "sp_id": 1
}
```

### Screen 9: SP Task List
```
┌─────────────────────────────────┐
│  ← My Tasks             🔍      │
├─────────────────────────────────┤
│                                 │
│  📊 Filter: All Tasks ▼         │
│                                 │
│  📋 Available Tasks (0)         │
│  ┌─────────────────────────────┐ │
│  │ No tasks available          │ │
│  │                             │ │
│  │ Tasks will appear here when │ │
│  │ customers book services in  │ │
│  │ your area and skill set.    │ │
│  │                             │ │
│  │ Make sure your profile is   │ │
│  │ complete and you're marked  │ │
│  │ as available.               │ │
│  └─────────────────────────────┘ │
│                                 │
│  📱 Task Notifications          │
│  ┌─────────────────────────────┐ │
│  │ 🔔 Enable push notifications│ │
│  │    to get instant alerts   │ │
│  │    for new task requests   │ │
│  └─────────────────────────────┘ │
│                                 │
└─────────────────────────────────┘
```

## API Integration Summary

### Customer App APIs:
1. **Authentication**: `/api/v1/customer/login`
2. **Services**: `/api/v1/services`
3. **Booking**: `/api/v1/unified-booking`
4. **Orders**: `/api/v1/customer/orders`
5. **Order Details**: `/api/v1/customer/orders/{orderId}`

### Service Provider App APIs:
1. **Dashboard**: `/api/v1/sp/dashboard`
2. **Tasks**: `/api/v1/sp/tasks`
3. **Accept Task**: `/api/v1/sp/task-requests/{broadcastId}/accept`
4. **Reject Task**: `/api/v1/sp/task-requests/{broadcastId}/reject`
5. **Availability**: `/api/v1/sp/availability`

### Authentication Flow:
- Customer: Phone + Password → Token
- Service Provider: SP ID based authentication
- All API calls include authentication headers

### Data Flow:
1. Customer logs in and browses services
2. Customer books service through unified booking API
3. System creates task and broadcasts to available SPs
4. SPs receive notifications and can accept/reject
5. Customer tracks order status through orders API

This mobile UI design ensures seamless integration with the backend APIs while providing an intuitive user experience for both customers and service providers.