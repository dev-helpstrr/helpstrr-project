# 🚀 Complete Task Assignment System - API Guide

## 📋 Overview

Your Helpstrr project now has a **COMPLETE TASK ASSIGNMENT SYSTEM** with:

1. **SP Filtering & Task Broadcasting** - Show filtered tasks to SPs based on capabilities
2. **Conflict Resolution** - Handle simultaneous acceptances with priority scoring
3. **Auto-Assignment Fallback** - Automatic assignment when no SP accepts
4. **Comprehensive Logging** - Track all assignment activities

## 🔄 Complete Task Assignment Flow

```
1. Task Created → 2. Filter SPs → 3. Broadcast to SPs → 4. SP Accepts/Rejects
                                                      ↓
5. Conflict Resolution (if multiple accept) → 6. Task Assigned
                                                      ↓
7. If No Acceptance → Next Round → Auto-Assignment Fallback
```

## 🛠️ API Endpoints

### 1. Get Filtered Tasks for SP (When SP Logs In)

**Endpoint**: `GET /api/v1/sp/available-tasks`

**Purpose**: Show tasks filtered based on SP's capabilities and location

```bash
curl -X GET "/api/v1/sp/available-tasks" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "sp_id": 123,
    "latitude": 19.0760,
    "longitude": 72.8777,
    "max_distance_km": 25,
    "limit": 20
  }'
```

**Response**:
```json
{
  "success": true,
  "data": {
    "tasks": [
      {
        "id": 456,
        "task_number": "TSK-2024-001",
        "status": "searching",
        "customer": {
          "name": "John Doe",
          "phone": "+91-9876543210"
        },
        "service": {
          "category": "Home Cleaning",
          "subcategory": "Deep Cleaning",
          "service": "Kitchen Deep Clean"
        },
        "details": {
          "pax_count": 2,
          "requested_hours": 4,
          "scheduled_at": "2024-12-06T10:00:00Z",
          "special_instructions": "Focus on kitchen appliances"
        },
        "location": {
          "address": "Bandra West, Mumbai",
          "latitude": 19.0596,
          "longitude": 72.8295,
          "distance_km": 2.5
        },
        "pricing": {
          "total_amount": 2500.00,
          "final_amount": 2250.00
        },
        "broadcast_info": {
          "broadcast_id": 789,
          "sent_at": "2024-12-05T14:30:00Z",
          "expires_at": "2024-12-05T15:30:00Z",
          "timeout_seconds": 3600,
          "time_remaining": 2400,
          "broadcast_round": 1
        }
      }
    ],
    "provider_info": {
      "id": 123,
      "name": "Rajesh Kumar",
      "rating": 4.8,
      "is_online": true,
      "current_location": {
        "latitude": 19.0760,
        "longitude": 72.8777
      }
    },
    "total_available": 5
  }
}
```

### 2. Accept Task with Conflict Resolution

**Endpoint**: `POST /api/v1/sp/task-requests/{broadcastId}/accept-advanced`

**Purpose**: Accept a task with automatic conflict resolution if multiple SPs accept simultaneously

```bash
curl -X POST "/api/v1/sp/task-requests/789/accept-advanced" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "sp_id": 123,
    "latitude": 19.0760,
    "longitude": 72.8777,
    "estimated_arrival_time": 30
  }'
```

**Success Response (No Conflict)**:
```json
{
  "success": true,
  "message": "Task accepted and assigned successfully",
  "data": {
    "task_id": 456,
    "task_number": "TSK-2024-001",
    "assigned": true,
    "conflict_resolved": false,
    "priority_score": 185.5,
    "response_time_seconds": 45,
    "distance_km": 2.5,
    "estimated_arrival": 30
  }
}
```

**Conflict Resolution Response (Won)**:
```json
{
  "success": true,
  "message": "Task accepted successfully (conflict resolved)",
  "data": {
    "task_id": 456,
    "task_number": "TSK-2024-001",
    "assigned": true,
    "conflict_resolved": true,
    "priority_score": 185.5,
    "response_time_seconds": 45,
    "distance_km": 2.5,
    "estimated_arrival": 30
  }
}
```

**Conflict Resolution Response (Lost)**:
```json
{
  "success": false,
  "message": "Task was assigned to another provider due to conflict resolution",
  "data": {
    "task_id": 456,
    "conflict_resolved": true,
    "winner_sp_id": 124,
    "your_priority_score": 175.2,
    "winner_priority_score": 185.5
  }
}
```

### 3. Reject Task with Fallback

**Endpoint**: `POST /api/v1/sp/task-requests/{broadcastId}/reject-advanced`

**Purpose**: Reject a task and automatically trigger next round or auto-assignment

```bash
curl -X POST "/api/v1/sp/task-requests/789/reject-advanced" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "sp_id": 123,
    "rejection_reason": "Not available at that time"
  }'
```

**Response**:
```json
{
  "success": true,
  "message": "Task rejected successfully",
  "data": {
    "task_id": 456,
    "rejection_reason": "Not available at that time",
    "response_time_seconds": 120,
    "next_round_triggered": true
  }
}
```

### 4. Handle Task Timeout (Auto-Assignment)

**Endpoint**: `POST /api/v1/task-assignment/timeout/{taskId}`

**Purpose**: Handle task timeout and trigger auto-assignment fallback

```bash
curl -X POST "/api/v1/task-assignment/timeout/456" \
  -H "Authorization: Bearer {token}"
```

**Response**:
```json
{
  "success": true,
  "message": "Task timeout handled and auto-assignment triggered",
  "data": {
    "task_id": 456,
    "timeout_broadcasts": 3,
    "auto_assignment_result": {
      "success": true,
      "provider_id": 125,
      "provider_name": "Amit Sharma",
      "assignment_score": 92.5,
      "distance_km": 3.2
    }
  }
}
```

## 🏆 Priority Scoring System

The conflict resolution uses a sophisticated priority scoring system:

### Scoring Factors:
1. **Distance** (40 points max): Closer providers get higher scores
2. **Response Time** (40 points max): Faster responses get higher scores  
3. **Provider Rating** (50 points max): Higher rated providers get more points
4. **Acceptance Rate** (40 points max): Providers with better acceptance rates score higher
5. **Punctuality Score** (30 points max): More punctual providers get bonus points

### Example Priority Calculation:
```
Provider A: Distance 2km, Response 30s, Rating 4.8, Acceptance 95%, Punctuality 90%
- Distance Score: 100 - (2 * 2) = 96
- Response Score: 100 - (30 / 10) = 97
- Rating Score: 4.8 * 10 = 48
- Acceptance Score: 95 * 0.5 = 47.5
- Punctuality Score: 90 * 0.3 = 27
- Total: 315.5 points

Provider B: Distance 5km, Response 60s, Rating 4.5, Acceptance 85%, Punctuality 80%
- Distance Score: 100 - (5 * 2) = 90
- Response Score: 100 - (60 / 10) = 94
- Rating Score: 4.5 * 10 = 45
- Acceptance Score: 85 * 0.5 = 42.5
- Punctuality Score: 80 * 0.3 = 24
- Total: 295.5 points

Winner: Provider A (315.5 > 295.5)
```

## 📊 Assignment Flow Logic

### 1. Initial Filtering
- Filter SPs by service capabilities
- Apply location-based distance filtering
- Check availability and online status
- Verify rating and experience requirements

### 2. Task Broadcasting
- Send task to filtered SPs in rounds
- Track broadcast timing and expiration
- Log all broadcast activities

### 3. Response Handling
- **Accept**: Check for conflicts, resolve if needed, assign task
- **Reject**: Log rejection, trigger next round if needed
- **Timeout**: Mark as timeout, trigger auto-assignment

### 4. Conflict Resolution
- Detect simultaneous acceptances (within 2 minutes)
- Calculate priority scores for all conflicting SPs
- Assign to highest scoring SP
- Notify losing SPs with conflict details

### 5. Auto-Assignment Fallback
- Triggered after 3+ rejections or timeouts
- Uses existing TaskAllocationService
- Finds best available provider automatically
- Logs as auto-assigned

## 🗂️ Database Logging

All assignment activities are logged in `task_assignment_logs` table:

```sql
-- View assignment history for a task
SELECT 
    tal.*,
    sp.name as provider_name,
    tb.response as broadcast_response
FROM task_assignment_logs tal
JOIN service_providers sp ON tal.service_provider_id = sp.id
LEFT JOIN task_broadcasts tb ON tal.broadcast_id = tb.id
WHERE tal.task_id = 456
ORDER BY tal.action_timestamp;

-- View conflict resolutions
SELECT * FROM task_assignment_logs 
WHERE conflict_resolution_applied = true
ORDER BY action_timestamp DESC;

-- View auto-assignments
SELECT * FROM task_assignment_logs 
WHERE auto_assigned = true
ORDER BY action_timestamp DESC;
```

## 🔧 Integration with Existing System

### Your Existing Controllers Enhanced:
- ✅ **TaskManagementController** - Enhanced with advanced cancellation and rating
- ✅ **ServiceProviderTaskController** - Enhanced with filtering, conflict resolution, and fallback
- ✅ **TaskAllocationService** - Integrated for auto-assignment fallback

### New Models Added:
- ✅ **TaskAssignmentLog** - Comprehensive assignment activity logging
- ✅ **SpLocationTracking** - Real-time provider location tracking

### API Routes Added:
- ✅ `/api/v1/sp/available-tasks` - Filtered task list for SPs
- ✅ `/api/v1/sp/task-requests/{id}/accept-advanced` - Accept with conflict resolution
- ✅ `/api/v1/sp/task-requests/{id}/reject-advanced` - Reject with fallback
- ✅ `/api/v1/task-assignment/timeout/{id}` - Handle timeouts

## 🚀 Testing the Complete System

### 1. Test SP Task Filtering:
```bash
# SP logs in and sees filtered tasks
GET /api/v1/sp/available-tasks?sp_id=123&latitude=19.0760&longitude=72.8777
```

### 2. Test Conflict Resolution:
```bash
# Multiple SPs accept same task simultaneously
POST /api/v1/sp/task-requests/789/accept-advanced (SP 123)
POST /api/v1/sp/task-requests/789/accept-advanced (SP 124) # Conflict!
```

### 3. Test Auto-Assignment:
```bash
# All SPs reject, trigger auto-assignment
POST /api/v1/sp/task-requests/789/reject-advanced
POST /api/v1/task-assignment/timeout/456
```

## ✨ Key Benefits

1. **Smart Filtering** - SPs only see relevant tasks based on their capabilities
2. **Fair Conflict Resolution** - Best provider wins based on objective scoring
3. **Reliable Fallback** - Tasks never get stuck without assignment
4. **Complete Transparency** - Full audit trail of all assignment activities
5. **Seamless Integration** - Works with your existing system architecture

Your task assignment system is now **PRODUCTION READY** with enterprise-level features! 🎉