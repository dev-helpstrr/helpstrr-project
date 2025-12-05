<?php

namespace App\Filament\Admin\Resources\NotificationPanelResource\Pages;

use App\Models\SPUser;
use App\Models\SortkarJobRole;
use App\Models\NotificationTemplate;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Admin\Resources\NotificationPanelResource;

class CreateNotificationPanel extends CreateRecord
{
    protected static string $resource = NotificationPanelResource::class;

    protected static string $view = 'filament.pages.notification-panel'; // custom layout

    public static string $role_model = SortkarJobRole::class; // adjust if your model name differs

    protected static ?string $title = 'Bulk Notification';


    public static function getRoleNames($interestedRoleIds): string
    {
        // Decode JSON if it's a string
        if (is_string($interestedRoleIds)) {
            $ids = json_decode($interestedRoleIds, true);
        } elseif (is_array($interestedRoleIds)) {
            $ids = $interestedRoleIds;
        } elseif (is_int($interestedRoleIds)) {
            $ids = [$interestedRoleIds];
        } else {
            $ids = [];
        }

        // Ensure array before querying
        if (empty($ids)) {
            return '-';
        }

        $roles = self::$role_model::whereIn('zoho_job_role_id', (array) $ids)
            ->pluck('role_name')
            ->toArray();

        return implode(', ', $roles);
    }
}
