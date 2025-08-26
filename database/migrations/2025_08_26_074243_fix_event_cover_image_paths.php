<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix cover image paths to include /storage/ prefix
        DB::table('events')
            ->whereNotNull('cover_image_url')
            ->whereRaw("cover_image_url NOT LIKE '/storage/%'")
            ->whereRaw("cover_image_url NOT LIKE 'http%'")
            ->update([
                'cover_image_url' => DB::raw("CONCAT('/storage/', cover_image_url)")
            ]);
            
        // Also fix any images in the images JSONB field
        $events = DB::table('events')
            ->whereNotNull('images')
            ->get();
            
        foreach ($events as $event) {
            $images = json_decode($event->images, true);
            if (is_array($images)) {
                $updated = false;
                foreach ($images as &$image) {
                    if (is_string($image) && !str_starts_with($image, '/storage/') && !str_starts_with($image, 'http')) {
                        $image = '/storage/' . $image;
                        $updated = true;
                    }
                }
                
                if ($updated) {
                    DB::table('events')
                        ->where('id', $event->id)
                        ->update(['images' => json_encode($images)]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert cover image paths (remove /storage/ prefix)
        DB::table('events')
            ->whereNotNull('cover_image_url')
            ->whereRaw("cover_image_url LIKE '/storage/%'")
            ->update([
                'cover_image_url' => DB::raw("SUBSTRING(cover_image_url, 10)")
            ]);
            
        // Revert images in JSONB field
        $events = DB::table('events')
            ->whereNotNull('images')
            ->get();
            
        foreach ($events as $event) {
            $images = json_decode($event->images, true);
            if (is_array($images)) {
                $updated = false;
                foreach ($images as &$image) {
                    if (is_string($image) && str_starts_with($image, '/storage/')) {
                        $image = substr($image, 9);
                        $updated = true;
                    }
                }
                
                if ($updated) {
                    DB::table('events')
                        ->where('id', $event->id)
                        ->update(['images' => json_encode($images)]);
                }
            }
        }
    }
};