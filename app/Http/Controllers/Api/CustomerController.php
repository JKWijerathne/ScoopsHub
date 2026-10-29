<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers, with optional search.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->get();

        return response()->json([
            'data' => $customers,
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('customers', 'email')],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $customer = Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        return response()->json([
            'message' => 'Customer created successfully',
            'data' => $customer,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified customer.
     */
    public function show(string $id): JsonResponse
    {
        $customer = Customer::find($id);

        if (! $customer) {
            return response()->json([
                'message' => 'Customer not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $customer,
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified customer in storage (PUT for full replace, PATCH for partial).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $customer = Customer::find($id);

        if (! $customer) {
            return response()->json([
                'message' => 'Customer not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $isPatch = $request->isMethod('patch');

        if ($isPatch) {
            $validated = $request->validate([
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'email' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('customers', 'email')->ignore($customer->id),
                ],
                'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            ]);
        } else {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'nullable',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('customers', 'email')->ignore($customer->id),
                ],
                'phone' => ['nullable', 'string', 'max:50'],
            ]);
        }

        $customer->update($validated);

        return response()->json([
            'message' => 'Customer updated successfully',
            'data' => $customer->fresh(),
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $customer = Customer::find($id);

        if (! $customer) {
            return response()->json([
                'message' => 'Customer not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $customer->delete();

        return response()->json([
            'message' => 'Customer deleted successfully',
        ], Response::HTTP_OK);
    }
}
