# Service Hierarchy Documentation

## Overview

The Helpstrr platform now implements a comprehensive 3-level service hierarchy system that provides flexible categorization and organization of services. This system replaces the previous simple category-subcategory structure with a more robust many-to-many relationship model.

## Hierarchy Structure

```
Category (Master Table)
    ↓ (Many-to-Many)
SubCategory/Roles (Master Table)
    ↓ (Many-to-Many)
Services (Master Table)
```

### Level 1: Categories
- **Purpose**: Top-level service groupings
- **Examples**: Home Services, Food & Catering, Transport & Delivery
- **Features**: 
  - Icon and color customization
  - Sort ordering
  - Active/inactive status
  - SEO-friendly slugs

### Level 2: SubCategories (Roles)
- **Purpose**: Specific service provider roles or skill sets
- **Examples**: House Keeping, Personal Chef, Baby Sitting, Personal Driver
- **Features**:
  - Pricing configuration (hourly rates, consultation fees)
  - Service settings (pax requirements, recurrence, event categories)
  - Multiple category assignments
  - Detailed service parameters

### Level 3: Services
- **Purpose**: Specific service offerings
- **Examples**: Deep Cleaning, Indian Cuisine Cooking, Airport Transfer
- **Features**:
  - Flexible pricing (base price or hourly rate)
  - Capacity limits (min/max hours, min/max people)
  - Service requirements and features
  - Multiple subcategory assignments
  - Verification requirements

## Database Schema

### Core Tables

#### `new_categories`
```sql
- id (Primary Key)
- name (Service category name)
- slug (URL-friendly identifier)
- description (Category description)
- icon (Icon class name)
- color (Hex color code)
- is_active (Active status)
- sort_order (Display order)
- timestamps
```

#### `new_subcategories`
```sql
- id (Primary Key)
- name (Subcategory/role name)
- slug (URL-friendly identifier)
- description (Subcategory description)
- icon (Icon class name)
- color (Hex color code)
- hourly_rate (Default hourly rate)
- min_hours (Minimum booking hours)
- consultation_fee (Consultation fee amount)
- pax_required (Requires multiple people)
- recurrence_allowed (Allows repeat bookings)
- is_event_category (Event service flag)
- is_takeaway (Takeaway service flag)
- is_active (Active status)
- sort_order (Display order)
- timestamps
```

#### `services`
```sql
- id (Primary Key)
- name (Service name)
- slug (URL-friendly identifier)
- description (Full service description)
- short_description (Brief description)
- icon (Icon class name)
- image (Service image path)
- base_price (Fixed service price)
- hourly_rate (Hourly service rate)
- min_hours/max_hours (Time constraints)
- consultation_fee (Service consultation fee)
- travel_allowance (Travel cost)
- pax_required (Requires multiple people)
- min_pax/max_pax (People constraints)
- recurrence_allowed (Repeat booking allowed)
- is_event_service (Event service flag)
- is_takeaway (Takeaway service flag)
- requires_verification (Admin verification needed)
- is_active (Active status)
- sort_order (Display order)
- requirements (JSON - Service requirements)
- features (JSON - Service features)
- timestamps
```

### Relationship Tables

#### `category_subcategory`
```sql
- id (Primary Key)
- category_id (Foreign Key to new_categories)
- subcategory_id (Foreign Key to new_subcategories)
- is_primary (Primary category flag)
- sort_order (Display order within category)
- timestamps
```

#### `subcategory_service`
```sql
- id (Primary Key)
- subcategory_id (Foreign Key to new_subcategories)
- service_id (Foreign Key to services)
- is_primary (Primary subcategory flag)
- sort_order (Display order within subcategory)
- timestamps
```

## Model Relationships

### NewCategory Model
```php
// Many-to-Many with SubCategories
public function subcategories(): BelongsToMany
public function primarySubcategories(): BelongsToMany
public function secondarySubcategories(): BelongsToMany

// Through relationship to Services
public function services(): BelongsToMany
```

### NewSubcategory Model
```php
// Many-to-Many with Categories
public function categories(): BelongsToMany
public function primaryCategories(): BelongsToMany

// Many-to-Many with Services
public function services(): BelongsToMany
public function primaryServices(): BelongsToMany

// Legacy relationships (backward compatibility)
public function tasks(): HasMany
public function spCapabilities(): HasMany
```

### Service Model
```php
// Many-to-Many with SubCategories
public function subcategories(): BelongsToMany
public function primarySubcategories(): BelongsToMany

// Through relationship to Categories
public function categories(): BelongsToMany

// Legacy relationships
public function tasks(): HasMany
public function spCapabilities(): HasMany
```

## Admin Panel Integration

### Filament Resources

1. **NewCategoryResource**
   - Category management
   - Subcategory assignment
   - Statistics and analytics

2. **NewSubcategoryResource**
   - Subcategory/role management
   - Category and service assignments
   - Pricing configuration

3. **ServiceResource**
   - Service management
   - Subcategory assignments
   - Detailed service configuration

### Navigation Structure
```
Service Hierarchy/
├── Categories (NewCategoryResource)
├── Sub Categories (Roles) (NewSubcategoryResource)
└── Services (ServiceResource)
```

## Migration Strategy

### New Migrations
1. `create_new_categories_table` - Category master table
2. `create_new_subcategories_table` - SubCategory master table
3. `create_services_table` - Service master table
4. `create_category_subcategory_pivot_table` - Category-SubCategory relationships
5. `create_subcategory_service_pivot_table` - SubCategory-Service relationships
6. `add_service_id_to_tasks_table` - Task-Service relationship
7. `add_service_id_to_sp_capabilities_table` - SP capability-Service relationship

### Backward Compatibility
- Existing `tasks` and `sp_capabilities` tables maintain old relationships
- New `service_id` columns added for future use
- Old Category and Subcategory models preserved as backup

## Usage Examples

### Creating a Complete Hierarchy

```php
// Create Category
$homeServices = NewCategory::create([
    'name' => 'Home Services',
    'slug' => 'home-services',
    'description' => 'Professional home services',
    'is_active' => true,
]);

// Create SubCategory
$housekeeping = NewSubcategory::create([
    'name' => 'House Keeping',
    'slug' => 'house-keeping',
    'hourly_rate' => 150.00,
    'min_hours' => 2,
    'is_active' => true,
]);

// Create Service
$deepCleaning = Service::create([
    'name' => 'Deep Cleaning',
    'slug' => 'deep-cleaning',
    'hourly_rate' => 150.00,
    'min_hours' => 3,
    'is_active' => true,
]);

// Create Relationships
$homeServices->subcategories()->attach($housekeeping->id, [
    'is_primary' => true,
    'sort_order' => 1
]);

$housekeeping->services()->attach($deepCleaning->id, [
    'is_primary' => true,
    'sort_order' => 1
]);
```

### Querying the Hierarchy

```php
// Get all services in a category
$category = NewCategory::find(1);
$services = $category->services()->active()->get();

// Get primary subcategory for a service
$service = Service::find(1);
$primarySubcategory = $service->primarySubcategories()->first();

// Get full hierarchy path
$service = Service::with(['subcategories.categories'])->find(1);
$fullPath = $service->full_path; // "Home Services > House Keeping > Deep Cleaning"
```

## Benefits of New Structure

1. **Flexibility**: Many-to-many relationships allow services to belong to multiple subcategories
2. **Scalability**: Easy to add new hierarchy levels or relationships
3. **Maintainability**: Separate master tables for each level
4. **SEO Friendly**: Proper slugs and descriptions at each level
5. **Admin Friendly**: Comprehensive Filament admin interface
6. **Backward Compatible**: Existing functionality preserved
7. **Rich Metadata**: Detailed configuration options at each level

## Future Enhancements

1. **Service Variants**: Add service variants/options
2. **Location-based Services**: Geographic service availability
3. **Dynamic Pricing**: Time-based or demand-based pricing
4. **Service Bundles**: Package multiple services together
5. **Advanced Filtering**: Complex search and filter options
6. **Analytics**: Detailed reporting on service performance