<?php

namespace App\Http\Controllers;

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

    public function create(): JsonResponse
    {
        $data = $this->faqService->all();

        return $this->success(data: $data);
    }

    public function edit(FaqCategoryRequest $faqCategoryRequest, FaqCategory $faqCategory): JsonResponse
    {
        $this->faqService->edit($faqCategoryRequest, $faqCategory);

        return $this->success(message: 'Record updated successfully');
    }
}
