<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FaqCategoryRequest;
use App\Models\FaqCategory;
use App\Services\FaqService;
use Illuminate\Http\JsonResponse;

class FaqCategoryController extends Controller
{
    public function __construct(
        protected FaqService $faqService
    ) {
    }

    public function all(): JsonResponse
    {
        $data = $this->faqService->all();

        return $this->success(data: $data['data'], meta: $data['meta']);
    }

    public function create(FaqCategoryRequest $request): JsonResponse
    {
        $request->validate(['name' => 'unique:faq_categories,name']);
        $data = $this->faqService->create($request);

        return $this->success(data: $data);
    }

    public function editCategory(FaqCategory $faqCategory, FaqCategoryRequest $faqCategoryRequest): JsonResponse
    {
        $this->faqService->edit($faqCategoryRequest, $faqCategory);

        return $this->success(message: 'Record updated successfully');
    }

    public function deleteCategory(FaqCategory $faqCategory): JsonResponse
    {
        $this->faqService->destroy($faqCategory);

        return $this->success(message: 'Record deleted successfully');
    }
}
