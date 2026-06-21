<?php

namespace App\Modules\Customers\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * GET /api/v1/customers
     * Get list of customers for the tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSorts = ['created_at', 'customer_code', 'first_name', 'last_name', 'company_name', 'email', 'status', 'credit_limit'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = $request->integer('per_page', 10);
        $customers = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $customers,
        ]);
    }

    /**
     * POST /api/v1/customers
     * Create a new customer with optional documents and drivers.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'customer_code'               => [
                'nullable',
                'string',
                Rule::unique('customers')->where('tenant_id', $tenantId)->whereNull('deleted_at'),
            ],
            'type'                        => ['required', 'string', 'in:Individual,Business'],
            'first_name'                  => ['required_if:type,Individual', 'nullable', 'string', 'max:255'],
            'last_name'                   => ['required_if:type,Individual', 'nullable', 'string', 'max:255'],
            'company_name'                => ['required_if:type,Business', 'nullable', 'string', 'max:255'],
            'email'                       => ['required', 'email', 'max:255'],
            'phone'                       => ['required', 'string', 'max:50'],
            'status'                      => ['nullable', 'string', 'in:active,inactive,suspended'],
            'credit_limit'                => ['nullable', 'numeric', 'min:0'],
            
            // Drivers
            'drivers'                     => ['nullable', 'array'],
            'drivers.*.first_name'        => ['required', 'string', 'max:255'],
            'drivers.*.last_name'         => ['required', 'string', 'max:255'],
            'drivers.*.license_number'    => ['required', 'string', 'max:100'],
            'drivers.*.license_expiry'    => ['required', 'date', 'after:today'],

            // Documents
            'documents'                   => ['nullable', 'array'],
            'documents.*.document_type'   => ['required', 'string', 'max:100'],
            'documents.*.document_number' => ['required', 'string', 'max:100'],
            'documents.*.expiry_date'     => ['required', 'date', 'after:today'],
            'documents.*.file_path'       => ['nullable', 'string', 'max:500'],
        ]);

        $customer = DB::transaction(function () use ($validated, $tenantId) {
            $customerData = collect($validated)->except(['drivers', 'documents'])->toArray();

            // Auto-generate customer_code if not provided
            if (empty($customerData['customer_code'])) {
                do {
                    $count = Customer::where('tenant_id', $tenantId)->withTrashed()->count() + 1;
                    $customerData['customer_code'] = 'CUST-' . str_pad($count, 5, '0', STR_PAD_LEFT);
                } while (Customer::where('tenant_id', $tenantId)->where('customer_code', $customerData['customer_code'])->exists());
            }

            // Create customer (tenant_id auto assigned by Trait)
            $customer = Customer::create($customerData);

            // Create drivers
            if (!empty($validated['drivers'])) {
                foreach ($validated['drivers'] as $driverData) {
                    $customer->drivers()->create($driverData);
                }
            }

            // Create documents
            if (!empty($validated['documents'])) {
                foreach ($validated['documents'] as $docData) {
                    $customer->documents()->create($docData);
                }
            }

            return $customer;
        });

        // Load relations for response
        $customer->load(['drivers', 'documents']);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully.',
            'data'    => $customer,
        ], 201);
    }

    /**
     * GET /api/v1/customers/{id}
     * Show a customer profile with documents and drivers.
     */
    public function show(int $id): JsonResponse
    {
        $customer = Customer::with(['drivers', 'documents'])->find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $customer,
        ]);
    }

    /**
     * PUT /api/v1/customers/{id}
     * Update a customer profile and sync documents/drivers.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
            ], 404);
        }

        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'customer_code'               => [
                'required',
                'string',
                Rule::unique('customers')->where('tenant_id', $tenantId)->ignore($id)->whereNull('deleted_at'),
            ],
            'type'                        => ['required', 'string', 'in:Individual,Business'],
            'first_name'                  => ['required_if:type,Individual', 'nullable', 'string', 'max:255'],
            'last_name'                   => ['required_if:type,Individual', 'nullable', 'string', 'max:255'],
            'company_name'                => ['required_if:type,Business', 'nullable', 'string', 'max:255'],
            'email'                       => ['required', 'email', 'max:255'],
            'phone'                       => ['required', 'string', 'max:50'],
            'status'                      => ['nullable', 'string', 'in:active,inactive,suspended'],
            'credit_limit'                => ['nullable', 'numeric', 'min:0'],
            
            // Drivers
            'drivers'                     => ['nullable', 'array'],
            'drivers.*.id'                => ['nullable', 'integer', 'exists:drivers,id'],
            'drivers.*.first_name'        => ['required', 'string', 'max:255'],
            'drivers.*.last_name'         => ['required', 'string', 'max:255'],
            'drivers.*.license_number'    => ['required', 'string', 'max:100'],
            'drivers.*.license_expiry'    => ['required', 'date', 'after:today'],

            // Documents
            'documents'                   => ['nullable', 'array'],
            'documents.*.id'              => ['nullable', 'integer', 'exists:customer_documents,id'],
            'documents.*.document_type'   => ['required', 'string', 'max:100'],
            'documents.*.document_number' => ['required', 'string', 'max:100'],
            'documents.*.expiry_date'     => ['required', 'date', 'after:today'],
            'documents.*.file_path'       => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($customer, $validated) {
            $customerData = collect($validated)->except(['drivers', 'documents'])->toArray();
            $customer->update($customerData);

            // Sync drivers
            $driverIdsToKeep = [];
            if (!empty($validated['drivers'])) {
                foreach ($validated['drivers'] as $driverData) {
                    if (!empty($driverData['id'])) {
                        $driver = $customer->drivers()->find($driverData['id']);
                        if ($driver) {
                            $driver->update($driverData);
                            $driverIdsToKeep[] = $driver->id;
                        }
                    } else {
                        $newDriver = $customer->drivers()->create($driverData);
                        $driverIdsToKeep[] = $newDriver->id;
                    }
                }
            }
            $customer->drivers()->whereNotIn('id', $driverIdsToKeep)->delete();

            // Sync documents
            $docIdsToKeep = [];
            if (!empty($validated['documents'])) {
                foreach ($validated['documents'] as $docData) {
                    if (!empty($docData['id'])) {
                        $doc = $customer->documents()->find($docData['id']);
                        if ($doc) {
                            $doc->update($docData);
                            $docIdsToKeep[] = $doc->id;
                        }
                    } else {
                        $newDoc = $customer->documents()->create($docData);
                        $docIdsToKeep[] = $newDoc->id;
                    }
                }
            }
            $customer->documents()->whereNotIn('id', $docIdsToKeep)->delete();
        });

        $customer->load(['drivers', 'documents']);

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully.',
            'data'    => $customer,
        ]);
    }

    /**
     * DELETE /api/v1/customers/{id}
     * Soft delete a customer.
     */
    public function destroy(int $id): JsonResponse
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
            ], 404);
        }

        $customer->delete(); // Soft delete

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully.',
        ]);
    }
}
