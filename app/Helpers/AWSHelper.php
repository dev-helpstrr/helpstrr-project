<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;


class AWSHelper
{

    public function get_s3_bucket_file($keyPath)
    {
        if (!Storage::disk('s3')->exists($keyPath)) {
            return null;
        }

        // Get a public URL to the file
        return Storage::disk('s3')->url($keyPath);
    }



    public function upload_file_in_s3($folderPath, $fileName, $fileContent)
    {
        $filePath = $folderPath . $fileName;

        // Upload the file with visibility
        $success = Storage::disk('s3')->put($filePath, $fileContent);

        if ($success) {
            return $filePath; // returns the S3 path
        }

        return false;
    }
}
