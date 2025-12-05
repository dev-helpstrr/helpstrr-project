# Subcategory Model Consolidation Plan

## Current Problem
We have two subcategory models which creates confusion and maintenance issues:

1. **Old System**: `Subcategory` model → `subcategories` table (one-to-many with categories)
2. **New System**: `NewSubcategory` model → `new_subcategories` table (many-to-many with categories)

## Recommended Solution: Complete Migration to New System

### Phase 1: Data Migration
1. Create migration to copy data from `subcategories` to `new_subcategories`
2. Create pivot table entries for category-subcategory relationships
3. Update all foreign key references in other tables

### Phase 2: Code Updates
1. Update all models that reference `Subcategory` to use `NewSubcategory`
2. Update controllers, services, and API endpoints
3. Rename `NewSubcategory` to `Subcategory` for clarity
4. Update imports and type hints

### Phase 3: Cleanup
1. Remove old `Subcategory` model
2. Drop old `subcategories` table
3. Update tests and documentation

## Benefits of New System
- **Flexibility**: Subcategories can belong to multiple categories
- **Better UX**: Icon and color support for frontend
- **Scalability**: Many-to-many relationships allow for complex hierarchies
- **Modern Design**: Uses pivot tables with additional metadata

## Implementation Steps

### Step 1: Create Data Migration
```php
// Migration to copy data from old to new system
public function up()
{
    // Copy subcategories data
    $oldSubcategories = DB::table('subcategories')->get();
    
    foreach ($oldSubcategories as $old) {
        $newId = DB::table('new_subcategories')->insertGetId([
            'name' => $old->name,
            'slug' => $old->slug,
            'description' => $old->description,
            'hourly_rate' => $old->hourly_rate,
            'min_hours' => $old->min_hours,
            'consultation_fee' => $old->consultation_fee,
            'pax_required' => $old->pax_required,
            'recurrence_allowed' => $old->recurrence_allowed,
            'is_event_category' => $old->is_event_category,
            'is_takeaway' => $old->is_takeaway,
            'is_active' => $old->is_active,
            'sort_order' => $old->sort_order,
            'created_at' => $old->created_at,
            'updated_at' => $old->updated_at,
        ]);
        
        // Create category-subcategory relationship
        DB::table('category_subcategory')->insert([
            'category_id' => $old->category_id,
            'subcategory_id' => $newId,
            'is_primary' => true,
            'sort_order' => $old->sort_order,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Update foreign key references
        DB::table('tasks')->where('subcategory_id', $old->id)->update(['subcategory_id' => $newId]);
        DB::table('sp_capabilities')->where('subcategory_id', $old->id)->update(['subcategory_id' => $newId]);
        // Add other tables as needed
    }
}
```

### Step 2: Update Models and References
1. Update `Task` model to use `NewSubcategory`
2. Update `Service` model relationships
3. Update all controllers and services
4. Update API responses

### Step 3: Rename and Cleanup
1. Rename `NewSubcategory` to `Subcategory`
2. Rename `new_subcategories` table to `subcategories`
3. Remove old model and table