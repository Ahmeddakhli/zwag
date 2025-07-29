<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaritalStatus;
use App\Traits\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class MaritalStatusController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $maritalStatuses = MaritalStatus::all()->map(function($status) {
            return [
                'id' => $status->id,
                'title' => $status->title, // Returns all translations
                'current_locale_title' => $status->translate(app()->getLocale())->title,
            ];
        });

        return $this->successResponse(
            'Marital statuses retrieved successfully',
            $maritalStatuses
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|array',
            'title.*' => 'required|string|max:255',
        ]);

        $maritalStatus = new MaritalStatus();
        $maritalStatus->setTranslations('title', $validated['title']);
        $maritalStatus->save();

        return $this->successResponse(
            'Marital status created successfully',
            $maritalStatus,
            Response::HTTP_CREATED
        );
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|array',
            'title.*' => 'required|string|max:255',
        ]);

        $maritalStatus = MaritalStatus::findOrFail($id);
        $maritalStatus->setTranslations('title', $validated['title']);
        $maritalStatus->save();

        return $this->successResponse(
            'Marital status updated successfully',
            $maritalStatus
        );
    }

    public function show($id)
    {
        $maritalStatus = MaritalStatus::findOrFail($id);

        return $this->successResponse(
            'Marital status retrieved successfully',
            [
                'id' => $maritalStatus->id,
                'title' => $maritalStatus->title,
                'current_locale_title' => $maritalStatus->translate(app()->getLocale())->title,
            ]
        );
    }

    public function destroy($id)
    {
        $maritalStatus = MaritalStatus::findOrFail($id);
        $maritalStatus->delete();

        return $this->successResponse(
            'Marital status deleted successfully',
            null,
            Response::HTTP_NO_CONTENT
        );
    }
}