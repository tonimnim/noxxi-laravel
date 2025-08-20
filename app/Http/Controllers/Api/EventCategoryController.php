<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class EventCategoryController extends Controller
{
    use ApiResponse;

    /**
     * Get all categories with hierarchy
     */
    public function index()
    {
        // Get main categories with children
        $categories = $this->getMainCategories();
        
        // Format response
        $formattedCategories = $this->formatCategories($categories);
        
        return $this->success(['categories' => $formattedCategories]);
    }

    /**
     * Get single category details
     */
    public function show($slug)
    {
        $category = EventCategory::where('slug', $slug)
            ->where('is_active', true)
            ->with(['children', 'parent'])
            ->first();
        
        if (!$category) {
            return $this->notFound('Category not found');
        }
        
        return $this->success(['category' => $this->formatSingleCategory($category)]);
    }

    /**
     * Get main categories with children
     */
    private function getMainCategories()
    {
        return EventCategory::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => function ($query) {
                $query->where('is_active', true)
                    ->select(['id', 'name', 'slug', 'icon_url', 'color_hex', 'parent_id', 'display_order'])
                    ->orderBy('display_order');
            }])
            ->select(['id', 'name', 'slug', 'icon_url', 'color_hex', 'description', 'display_order'])
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Format categories for response
     */
    private function formatCategories($categories)
    {
        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon_url' => $category->icon_url,
                'color_hex' => $category->color_hex,
                'description' => $category->description,
                'subcategories' => $category->children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'slug' => $child->slug,
                        'icon_url' => $child->icon_url,
                        'color_hex' => $child->color_hex,
                    ];
                })
            ];
        });
    }

    /**
     * Format single category
     */
    private function formatSingleCategory($category)
    {
        $data = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'icon_url' => $category->icon_url,
            'color_hex' => $category->color_hex,
            'description' => $category->description,
            'is_parent' => $category->parent_id === null,
        ];
        
        if ($category->parent) {
            $data['parent'] = [
                'id' => $category->parent->id,
                'name' => $category->parent->name,
                'slug' => $category->parent->slug,
            ];
        }
        
        if ($category->children->count() > 0) {
            $data['subcategories'] = $category->children->map(function ($child) {
                return [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'icon_url' => $child->icon_url,
                    'color_hex' => $child->color_hex,
                ];
            });
        }
        
        return $data;
    }
}