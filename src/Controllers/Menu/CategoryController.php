<?php

namespace App\Controllers\Menu;

use App\Core\Request;
use App\Core\Response;
use App\Models\Category;
use App\Validators\Validator;

class CategoryController
{
    private Response $response;

    public function __construct()
    {
        $this->response = new Response();
    }

    /**
     * List all categories
     */
    public function index(Request $request)
    {
        $categories = Category::allWithItemCount();

        return $this->response->json($categories, 200);
    }

    /**
     * Get single category
     */
    public function show(Request $request)
    {
        $id = $request->param('id');
        $category = Category::find($id);

        if (!$category) {
            return $this->response->error('Category not found', 404);
        }

        return $this->response->json($category, 200);
    }

    /**
     * Create category
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
            'name_ar' => 'max:255',
            'slug' => 'max:255',
            'description' => 'max:1000',
            'description_ar' => 'max:1000',
            'image_url' => 'url',
            'sort_order' => 'integer',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->response->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->only([
            'name', 'name_ar', 'slug', 'description',
            'description_ar', 'image_url', 'sort_order', 'is_active'
        ]);

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = slugify($data['name']);
        }

        try {
            $id = Category::create($data);
            $category = Category::find($id);

            return $this->response->json($category, 201, 'Category created successfully');
        } catch (\Exception $e) {
            logError('Failed to create category: ' . $e->getMessage());
            return $this->response->error('Failed to create category', 500);
        }
    }

    /**
     * Update category
     */
    public function update(Request $request)
    {
        $id = $request->param('id');
        $category = Category::find($id);

        if (!$category) {
            return $this->response->error('Category not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'min:2|max:255',
            'name_ar' => 'max:255',
            'description' => 'max:1000',
            'description_ar' => 'max:1000',
            'image_url' => 'url',
            'sort_order' => 'integer',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->response->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->only([
            'name', 'name_ar', 'description', 'description_ar',
            'image_url', 'sort_order', 'is_active'
        ]);

        try {
            Category::update($id, $data);
            $category = Category::find($id);

            return $this->response->json($category, 200, 'Category updated successfully');
        } catch (\Exception $e) {
            logError('Failed to update category: ' . $e->getMessage());
            return $this->response->error('Failed to update category', 500);
        }
    }

    /**
     * Delete category
     */
    public function destroy(Request $request)
    {
        $id = $request->param('id');
        $category = Category::find($id);

        if (!$category) {
            return $this->response->error('Category not found', 404);
        }

        try {
            Category::delete($id);
            return $this->response->json(null, 200, 'Category deleted successfully');
        } catch (\Exception $e) {
            logError('Failed to delete category: ' . $e->getMessage());
            return $this->response->error('Failed to delete category', 500);
        }
    }
}
