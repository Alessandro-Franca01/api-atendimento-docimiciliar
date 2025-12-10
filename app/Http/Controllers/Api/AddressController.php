<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Patient;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        if ($patient->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:Residencial,Trabalho,Outro',
            'street' => 'required|string',
            'number' => 'required|string',
            'complement' => 'nullable|string',
            'neighborhood' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string|size:2',
            'zip_code' => 'required|string',
            'is_primary' => 'nullable|boolean',
        ]);

        if ($request->is_primary) {
            $patient->addresses()->update(['is_primary' => false]);
        }

        $address = $patient->addresses()->create($validated);

        return response()->json($address, 201);
    }

    public function update(Request $request, Address $address)
    {
        if ($address->patient->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $validated = $request->validate([
            'type' => 'sometimes|in:Residencial,Trabalho,Outro',
            'street' => 'sometimes|string',
            'number' => 'sometimes|string',
            'complement' => 'nullable|string',
            'neighborhood' => 'sometimes|string',
            'city' => 'sometimes|string',
            'state' => 'sometimes|string|size:2',
            'zip_code' => 'sometimes|string',
            'is_primary' => 'nullable|boolean',
        ]);

        if ($request->is_primary) {
            $address->patient->addresses()->where('id', '!=', $address->id)->update(['is_primary' => false]);
        }

        $address->update($validated);

        return response()->json($address);
    }

    public function destroy(Request $request, Address $address)
    {
        if ($address->patient->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $address->delete();

        return response()->json(['message' => 'Endereço excluído com sucesso']);
    }
}
