Assistant patch notes:
- Migration added: database/migrations/2025_11_18_045552_create_service_categories_and_update_sp_users.php
- Model added: app/Models/ServiceCategory.php
- Filament resource added: app/Filament/Admin/Resources/ServiceCategoryResource.php
- SPUser model patched: app/Models/SPUser.php (fillable, casts, category relation)
- Seeder added: database/seeders/ServiceCategorySeeder.php
NEXT: run composer dump-autoload && php artisan migrate && php artisan db:seed --class=ServiceCategorySeeder
