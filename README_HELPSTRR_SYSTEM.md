# Helpstrr Multi-Category On-Demand Service Platform

## Overview

Helpstrr is a comprehensive multi-category on-demand service platform built with Laravel 12 and Filament 3.2. The platform supports three main service categories: **Chef**, **Driver**, and **House Help** services with sophisticated pricing, allocation, and booking systems.

## 🏗️ System Architecture

### Core Components

1. **Database Layer**: 25+ migration files with comprehensive schema
2. **Model Layer**: 15+ Laravel models with proper relationships
3. **Service Layer**: 4 core business services
4. **API Layer**: RESTful APIs for customer, SP, and admin operations
5. **Admin Panel**: Filament-based admin interface
6. **Authentication**: Laravel Sanctum for API security

### Key Features

- ✅ **2-Hour Lead Time Rule**: Enforced booking validation
- ✅ **Complex Pricing Engine**: Night multipliers, surge pricing, premium charges
- ✅ **38-Filter SP Allocation System**: Sophisticated matching algorithm
- ✅ **6-Layer Chef Booking Flow**: Multi-step booking process
- ✅ **Complete Task Lifecycle**: 12-state task management
- ✅ **Real-time Tracking**: Location and status updates
- ✅ **Multi-tier Rating System**: Customer and SP ratings

## 📊 Database Schema

### Core Tables

| Table | Purpose | Key Features |
|-------|---------|--------------|
| `categories` | Service categories | Night multipliers, sorting |
| `subcategories` | Service types | Pricing, requirements, constraints |
| `tasks` | Service bookings | Complete lifecycle, pricing, ratings |
| `service_providers` | SP profiles | Performance metrics, capabilities |
| `customers` | Customer profiles | Addresses, preferences, history |
| `chef_cuisines` | Available cuisines | 24 cuisine types |
| `dietary_preferences` | Diet restrictions | 16 preference types |
| `optional_flags` | Service modifiers | Pricing impacts, hard filters |

### Capability Tables (38 Filters)

- SP capabilities for precise matching
- Hard and soft filters
- Quality scoring system
- Distance-based allocation

## 🔧 Core Services

### 1. PricingEngine (`app/Services/PricingEngine.php`)

**Features:**
- Base pricing calculation
- Night time multipliers (10 PM - 6 AM)
- Surge pricing during peak hours
- Premium SP charges (Gold level +20%)
- Consultation fees
- Subscription discounts
- GST calculations

**Key Methods:**
```php
calculateTaskPricing($taskData)
getCustomerDisplayPrice($pricingData)
validateLeadTime($scheduledAt)
```

### 2. AllocationEngine (`app/Services/AllocationEngine.php`)

**Features:**
- 38 capability filters
- Quality-based sorting
- Distance window allocation
- Broadcast rounds (A/B/new_sp_boost)
- Response handling

**Allocation Process:**
1. Apply capability filters
2. Calculate quality scores
3. Sort by distance and quality
4. Broadcast in rounds
5. Handle responses

### 3. TaskLifecycleService (`app/Services/TaskLifecycleService.php`)

**Task States:**
```
requested → searching → assigned → on_the_way → arrived → 
otp_start_verified → started → paused → resumed → completed → rated
```

**Features:**
- State machine validation
- OTP generation and verification
- Cancellation handling
- SP statistics updates
- Status history tracking

### 4. ChefBookingService (`app/Services/ChefBookingService.php`)

**6-Layer Booking Flow:**
1. **Service Selection**: Category + Subcategory
2. **Cuisine Selection**: Multi-select (1-5 cuisines)
3. **Dietary Preferences**: Single select (optional)
4. **Optional Flags**: Multi-select with pricing
5. **Pax & Time**: Count, hours, date/time
6. **Address Selection**: Customer addresses

## 🎛️ Admin Panel (Filament)

### Resources Created

#### TaskResource
- **Features**: Full CRUD, status management, detailed views
- **Tabs**: All, Pending, In Progress, Completed, Cancelled, Today
- **Actions**: View, Edit, Status updates
- **Filters**: Status, category, date ranges

#### CategoryResource
- **Features**: Category management, pricing configuration
- **Statistics**: Subcategories, tasks, service providers
- **Actions**: Activate/deactivate, bulk operations
- **Reorderable**: Drag-and-drop sorting

### Navigation Structure
```
📋 Tasks (with pending badge)
👥 Service Providers (with KYC pending badge)
🏷️ Categories (with active count badge)
📊 Analytics & Reports
⚙️ System Settings
```

## 🔌 API Endpoints

### Customer APIs (`/api/v1/customer/`)

#### Task Management
```
GET    /tasks                    # List customer tasks
POST   /tasks                    # Create new task
GET    /tasks/{id}               # Get task details
POST   /tasks/{id}/cancel        # Cancel task
POST   /tasks/{id}/rate          # Rate completed task
POST   /tasks/pricing-preview    # Get pricing preview
GET    /tasks/statistics         # Customer statistics
```

#### Chef Booking Flow
```
GET    /chef/services            # Layer 1: Available services
GET    /chef/cuisines            # Layer 2: Available cuisines
GET    /chef/dietary-preferences # Layer 3: Dietary options
GET    /chef/optional-flags      # Layer 4: Optional flags
GET    /chef/pax-time-options    # Layer 5: Pax & time options
GET    /chef/addresses           # Layer 6: Customer addresses
GET    /chef/complete-flow       # Complete flow data
POST   /chef/validate-booking    # Validate booking data
POST   /chef/pricing-preview     # Chef pricing preview
GET    /chef/booking-rules       # Booking rules & constraints
POST   /chef/check-availability  # Check chef availability
```

## 🌱 Database Seeders

### Comprehensive Data Population

1. **CategorySeeder**: 3 main categories with night multipliers
2. **SubcategorySeeder**: 11 subcategories with pricing and constraints
3. **ChefCuisineSeeder**: 24 cuisine types (Indian + International)
4. **DietaryPreferenceSeeder**: 16 dietary preferences
5. **OptionalFlagSeeder**: 18 optional flags with pricing modifiers

### Sample Data
- **Chef Services**: Home Chef, Party Chef, Tiffin Chef, Specialty Chef
- **Driver Services**: Personal, Outstation, Event drivers
- **House Help**: Cleaning, Deep Cleaning, Babysitting, Elder Care

## 🔐 Security & Validation

### Authentication
- Laravel Sanctum for API authentication
- Role-based access control
- Secure token management

### Validation Rules
- 2-hour lead time enforcement
- Cuisine selection limits (1-5)
- Pax count validation
- Service-specific constraints
- Hard filter validation

## 📱 Mobile App Integration

### Customer App Features
- Service browsing and booking
- Real-time task tracking
- Rating and feedback system
- Payment integration ready
- Push notifications support

### Service Provider App Features
- Task acceptance/rejection
- Location tracking
- Status updates
- Earnings tracking
- Performance metrics

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.2+
- Laravel 12
- MySQL 8.0+
- Composer
- Node.js & NPM

### Installation Steps

1. **Clone Repository**
```bash
git clone <repository-url>
cd helpstrr
```

2. **Install Dependencies**
```bash
composer install
npm install
```

3. **Environment Setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Database Setup**
```bash
php artisan migrate
php artisan db:seed
```

5. **Storage & Cache**
```bash
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

6. **Start Development Server**
```bash
php artisan serve
npm run dev
```

## 📊 Performance Considerations

### Database Optimization
- Proper indexing on frequently queried columns
- Relationship eager loading
- Query optimization for allocation engine

### Caching Strategy
- Redis for session management
- Cache frequently accessed data
- Queue jobs for heavy operations

### Scalability
- Horizontal scaling ready
- Microservices architecture support
- Load balancer compatible

## 🧪 Testing

### Test Coverage Areas
- Unit tests for services
- Feature tests for APIs
- Integration tests for booking flow
- Performance tests for allocation engine

### Testing Commands
```bash
php artisan test
php artisan test --coverage
```

## 📈 Monitoring & Analytics

### Key Metrics
- Task completion rates
- SP performance scores
- Customer satisfaction ratings
- Revenue analytics
- System performance metrics

### Logging
- Comprehensive error logging
- Performance monitoring
- User activity tracking
- Business intelligence data

## 🔄 Deployment

### Production Checklist
- [ ] Environment variables configured
- [ ] Database migrations run
- [ ] SSL certificates installed
- [ ] Caching enabled
- [ ] Queue workers running
- [ ] Monitoring tools configured

### CI/CD Pipeline
- Automated testing
- Code quality checks
- Deployment automation
- Rollback capabilities

## 📞 Support & Maintenance

### Regular Maintenance Tasks
- Database optimization
- Log rotation
- Security updates
- Performance monitoring
- Backup verification

### Support Channels
- Technical documentation
- API documentation
- Admin user guides
- Developer resources

## 🎯 Future Enhancements

### Planned Features
- Advanced analytics dashboard
- Machine learning for SP allocation
- Multi-language support
- Advanced payment options
- IoT device integration

### Scalability Roadmap
- Microservices migration
- Multi-tenant architecture
- Global expansion support
- Advanced caching strategies

---

## 📋 Quick Reference

### Important File Locations
```
app/Models/                 # Core models
app/Services/              # Business logic services
app/Http/Controllers/Api/  # API controllers
app/Filament/Admin/        # Admin panel resources
database/migrations/       # Database schema
database/seeders/         # Data population
routes/api.php            # API routes
```

### Key Configuration Files
```
config/database.php       # Database configuration
config/sanctum.php        # API authentication
config/filament.php       # Admin panel settings
```

### Environment Variables
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=helpstrr
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

This comprehensive system provides a solid foundation for a multi-category on-demand service platform with all the specified requirements implemented and ready for production deployment.