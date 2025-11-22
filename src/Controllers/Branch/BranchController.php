<?php

namespace App\Controllers\Branch;

use App\Core\Request;
use App\Core\Response;
use App\Models\Branch;
use App\Core\Database;
use App\Validators\Validator;

class BranchController
{
    private Response $response;

    public function __construct()
    {
        $this->response = new Response();
    }

    /**
     * List all branches
     */
    public function index(Request $request)
    {
        try {
            $branches = Branch::all();

            // Add stats for each branch if requested
            if ($request->query('include_stats')) {
                foreach ($branches as &$branch) {
                    $branch['stats'] = Branch::getStats($branch['id']);
                    $branch['is_open'] = Branch::isOpen($branch['id']);
                }
            }

            return $this->response->json($branches, 200);

        } catch (\Exception $e) {
            logError('Failed to fetch branches: ' . $e->getMessage());
            return $this->response->error('Failed to fetch branches', 500);
        }
    }

    /**
     * List active branches (for customer ordering)
     */
    public function active(Request $request)
    {
        try {
            $tenantId = $request->tenantId ?? $_SESSION['tenant_id'] ?? null;
            $branches = Branch::getActiveBranches($tenantId);

            // Add opening status
            foreach ($branches as &$branch) {
                $branch['is_open'] = Branch::isOpen($branch['id']);
            }

            return $this->response->json($branches, 200);

        } catch (\Exception $e) {
            logError('Failed to fetch active branches: ' . $e->getMessage());
            return $this->response->error('Failed to fetch branches', 500);
        }
    }

    /**
     * Get single branch
     */
    public function show(Request $request)
    {
        $id = $request->param('id');

        try {
            $branch = Branch::findWithHours($id);

            if (!$branch) {
                return $this->response->error('Branch not found', 404);
            }

            // Add stats and status
            $branch['stats'] = Branch::getStats($id);
            $branch['is_open'] = Branch::isOpen($id);

            return $this->response->json($branch, 200);

        } catch (\Exception $e) {
            logError('Failed to fetch branch: ' . $e->getMessage());
            return $this->response->error('Failed to fetch branch', 500);
        }
    }

    /**
     * Create branch
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
            'address_line1' => 'max:255',
            'address_line2' => 'max:255',
            'city' => 'max:100',
            'state' => 'max:100',
            'postal_code' => 'max:20',
            'country' => 'max:100',
            'latitude' => 'numeric',
            'longitude' => 'numeric',
            'phone' => 'phone',
            'email' => 'email',
            'is_active' => 'boolean',
            'accepts_online_orders' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->response->error('Validation failed', 422, $validator->errors());
        }

        try {
            $data = $request->only([
                'name', 'address_line1', 'address_line2', 'city', 'state',
                'postal_code', 'country', 'latitude', 'longitude',
                'phone', 'email', 'is_active', 'accepts_online_orders'
            ]);

            $data['tenant_id'] = $request->tenantId;
            $data['is_active'] = $data['is_active'] ?? true;
            $data['accepts_online_orders'] = $data['accepts_online_orders'] ?? true;

            $branchId = Branch::create($data);
            $branch = Branch::find($branchId);

            return $this->response->json($branch, 201, 'Branch created successfully');

        } catch (\Exception $e) {
            logError('Failed to create branch: ' . $e->getMessage());
            return $this->response->error('Failed to create branch', 500);
        }
    }

    /**
     * Update branch
     */
    public function update(Request $request)
    {
        $id = $request->param('id');
        $branch = Branch::find($id);

        if (!$branch) {
            return $this->response->error('Branch not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'min:2|max:255',
            'address_line1' => 'max:255',
            'address_line2' => 'max:255',
            'city' => 'max:100',
            'state' => 'max:100',
            'postal_code' => 'max:20',
            'country' => 'max:100',
            'latitude' => 'numeric',
            'longitude' => 'numeric',
            'phone' => 'phone',
            'email' => 'email',
            'is_active' => 'boolean',
            'accepts_online_orders' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->response->error('Validation failed', 422, $validator->errors());
        }

        try {
            $data = $request->only([
                'name', 'address_line1', 'address_line2', 'city', 'state',
                'postal_code', 'country', 'latitude', 'longitude',
                'phone', 'email', 'is_active', 'accepts_online_orders'
            ]);

            Branch::update($id, $data);
            $branch = Branch::find($id);

            return $this->response->json($branch, 200, 'Branch updated successfully');

        } catch (\Exception $e) {
            logError('Failed to update branch: ' . $e->getMessage());
            return $this->response->error('Failed to update branch', 500);
        }
    }

    /**
     * Delete branch
     */
    public function destroy(Request $request)
    {
        $id = $request->param('id');
        $branch = Branch::find($id);

        if (!$branch) {
            return $this->response->error('Branch not found', 404);
        }

        try {
            Branch::delete($id);
            return $this->response->json(null, 200, 'Branch deleted successfully');

        } catch (\Exception $e) {
            logError('Failed to delete branch: ' . $e->getMessage());
            return $this->response->error('Failed to delete branch', 500);
        }
    }

    /**
     * Set branch opening hours
     */
    public function setHours(Request $request)
    {
        $branchId = $request->param('id');
        $branch = Branch::find($branchId);

        if (!$branch) {
            return $this->response->error('Branch not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'hours' => 'required|array',
            'hours.*.day_of_week' => 'required|integer|min:0|max:6',
            'hours.*.open_time' => 'required',
            'hours.*.close_time' => 'required',
            'hours.*.is_closed' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->response->error('Validation failed', 422, $validator->errors());
        }

        try {
            $hours = $request->input('hours');

            // Delete existing hours
            Database::query(
                "DELETE FROM opening_hours WHERE branch_id = :branch_id",
                ['branch_id' => $branchId]
            );

            // Insert new hours
            foreach ($hours as $hour) {
                Database::insert('opening_hours', [
                    'branch_id' => $branchId,
                    'day_of_week' => $hour['day_of_week'],
                    'open_time' => $hour['open_time'],
                    'close_time' => $hour['close_time'],
                    'is_closed' => $hour['is_closed'] ?? false
                ]);
            }

            $branch = Branch::findWithHours($branchId);

            return $this->response->json($branch, 200, 'Opening hours updated successfully');

        } catch (\Exception $e) {
            logError('Failed to set opening hours: ' . $e->getMessage());
            return $this->response->error('Failed to set opening hours', 500);
        }
    }
}
