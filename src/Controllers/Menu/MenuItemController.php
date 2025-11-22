<?php

namespace App\Controllers\Menu;

use App\Core\Request;
use App\Core\Response;
use App\Models\MenuItem;
use App\Validators\Validator;

class MenuItemController
{
    private Response $response;

    public function __construct()
    {
        $this->response = new Response();
    }

    /**
     * List all menu items
     */
    public function index(Request $request)
    {
        $categoryId = $request->query('category_id');
        $search = $request->query('search');
        $isAvailable = $request->query('is_available');

        $conditions = [];

        if ($categoryId) {
            $conditions['category_id'] = $categoryId;
        }

        if ($isAvailable !== null) {
            $conditions['is_available'] = (int) $isAvailable;
        }

        $items = MenuItem::all($conditions);

        // Filter by search if provided
        if ($search) {
            $items = array_filter($items, function($item) use ($search) {
                return stripos($item['name'], $search) !== false ||
                       stripos($item['description'] ?? '', $search) !== false;
            });
        }

        return $this->response->json(array_values($items), 200);
    }

    /**
     * Get single menu item with modifiers
     */
    public function show(Request $request)
    {
        $id = $request->param('id');
        $item = MenuItem::findWithModifiers($id);

        if (!$item) {
            return $this->response->error('Menu item not found', 404);
        }

        return $this->response->json($item, 200);
    }

    /**
     * Create menu item
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'required|min:2|max:255',
            'name_ar' => 'max:255',
            'slug' => 'max:255',
            'description' => 'max:1000',
            'description_ar' => 'max:1000',
            'price' => 'required|numeric',
            'image_url' => 'url',
            'calories' => 'integer',
            'preparation_time' => 'integer',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'is_vegetarian' => 'boolean',
            'is_vegan' => 'boolean',
            'is_gluten_free' => 'boolean',
            'sort_order' => 'integer'
        ]);

        if ($validator->fails()) {
            return $this->response->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->only([
            'category_id', 'name', 'name_ar', 'slug', 'description',
            'description_ar', 'price', 'image_url', 'calories',
            'preparation_time', 'is_available', 'is_featured',
            'is_vegetarian', 'is_vegan', 'is_gluten_free', 'sort_order'
        ]);

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = slugify($data['name']);
        }

        try {
            $id = MenuItem::create($data);
            $item = MenuItem::find($id);

            return $this->response->json($item, 201, 'Menu item created successfully');
        } catch (\Exception $e) {
            logError('Failed to create menu item: ' . $e->getMessage());
            return $this->response->error('Failed to create menu item', 500);
        }
    }

    /**
     * Update menu item
     */
    public function update(Request $request)
    {
        $id = $request->param('id');
        $item = MenuItem::find($id);

        if (!$item) {
            return $this->response->error('Menu item not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'integer|exists:categories,id',
            'name' => 'min:2|max:255',
            'name_ar' => 'max:255',
            'description' => 'max:1000',
            'description_ar' => 'max:1000',
            'price' => 'numeric',
            'image_url' => 'url',
            'calories' => 'integer',
            'preparation_time' => 'integer',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'is_vegetarian' => 'boolean',
            'is_vegan' => 'boolean',
            'is_gluten_free' => 'boolean',
            'sort_order' => 'integer'
        ]);

        if ($validator->fails()) {
            return $this->response->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->only([
            'category_id', 'name', 'name_ar', 'description', 'description_ar',
            'price', 'image_url', 'calories', 'preparation_time',
            'is_available', 'is_featured', 'is_vegetarian', 'is_vegan',
            'is_gluten_free', 'sort_order'
        ]);

        try {
            MenuItem::update($id, $data);
            $item = MenuItem::find($id);

            return $this->response->json($item, 200, 'Menu item updated successfully');
        } catch (\Exception $e) {
            logError('Failed to update menu item: ' . $e->getMessage());
            return $this->response->error('Failed to update menu item', 500);
        }
    }

    /**
     * Delete menu item
     */
    public function destroy(Request $request)
    {
        $id = $request->param('id');
        $item = MenuItem::find($id);

        if (!$item) {
            return $this->response->error('Menu item not found', 404);
        }

        try {
            MenuItem::delete($id);
            return $this->response->json(null, 200, 'Menu item deleted successfully');
        } catch (\Exception $e) {
            logError('Failed to delete menu item: ' . $e->getMessage());
            return $this->response->error('Failed to delete menu item', 500);
        }
    }
}
