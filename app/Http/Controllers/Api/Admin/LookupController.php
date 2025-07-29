<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lookup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Traits\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class LookupController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Lookup::query();
        
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } else {
            $query->whereNull('parent_id');
        }
        
        $lookups = $query->get()->map(function($lookup) {
            return [
                'id' => $lookup->id,
                'slug' => $lookup->slug,
                'parent_id' => $lookup->parent_id,
                'title' => $lookup->title,
                'has_children' => $lookup->children()->exists(),
            ];
        });

        return $this->successResponse('Lookups retrieved successfully', $lookups);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:lookups,id',
            'title' => 'required|array',
            'title.*' => 'required|string|max:255',
        ]);

        // Generate full slug path if parent exists
        $fullSlug = $validated['slug'];
        if (!empty($validated['parent_id'])) {
            $parent = Lookup::find($validated['parent_id']);
            $fullSlug = $parent->slug . '-' . Str::slug($validated['slug']);
        }

        // Ensure slug is unique
        $counter = 1;
        $originalSlug = $fullSlug;
        while (Lookup::where('slug', $fullSlug)->exists()) {
            $fullSlug = $originalSlug . '-' . $counter++;
        }

        $lookup = Lookup::create([
            'slug' => $fullSlug,
            'parent_id' => $validated['parent_id'] ?? null,
            'title' => $validated['title'],
        ]);

        return $this->successResponse(
            'Lookup created successfully',
            $this->formatLookupResponse($lookup),
            Response::HTTP_CREATED
        );
    }

    public function show($id)
    {
        $lookup = Lookup::with('children')->findOrFail($id);
        return $this->successResponse(
            'Lookup retrieved successfully',
            $this->formatLookupResponse($lookup)
        );
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'slug' => 'sometimes|required|string|max:255',
            'parent_id' => 'nullable|exists:lookups,id',
            'title' => 'sometimes|required|array',
            'title.*' => 'required|string|max:255',
        ]);

        $lookup = Lookup::findOrFail($id);
        
        $slugChanged = isset($validated['slug']);
        $parentChanged = array_key_exists('parent_id', $validated) && 
                         $validated['parent_id'] != $lookup->parent_id;

        // Regenerate full slug if slug or parent changed
        if ($slugChanged || $parentChanged) {
            $newSlug = $slugChanged ? $validated['slug'] : $lookup->getOriginal('slug');
            
            if (!empty($validated['parent_id'])) {
                $parent = Lookup::find($validated['parent_id']);
                $newSlug = $parent->slug . '-' . Str::slug($newSlug);
            }

            // Ensure new slug is unique
            $counter = 1;
            $originalSlug = $newSlug;
            while (Lookup::where('slug', $newSlug)
                        ->where('id', '!=', $lookup->id)
                        ->exists()) {
                $newSlug = $originalSlug . '-' . $counter++;
            }

            $lookup->slug = $newSlug;
        }

        if (array_key_exists('parent_id', $validated)) {
            $lookup->parent_id = $validated['parent_id'];
        }

        if (isset($validated['title'])) {
            $lookup->title = $validated['title'];
        }

        $lookup->save();

        return $this->successResponse(
            'Lookup updated successfully',
            $this->formatLookupResponse($lookup)
        );
    }

    public function destroy($id)
    {
        $lookup = Lookup::findOrFail($id);
        
        if ($lookup->children()->exists()) {
            return $this->errorResponse(
                'Cannot delete lookup with children items',
                null,
                Response::HTTP_CONFLICT
            );
        }
        
        $lookup->delete();

        return $this->successResponse(
            'Lookup deleted successfully',
            null,
            Response::HTTP_NO_CONTENT
        );
    }

    protected function formatLookupResponse(Lookup $lookup)
    {
        return [
            'id' => $lookup->id,
            'slug' => $lookup->slug,
            'parent_id' => $lookup->parent_id,
            'title' => $lookup->title,
            'children' => $lookup->children->map(function($child) {
                return $this->formatLookupResponse($child);
            }),
        ];
    }
}