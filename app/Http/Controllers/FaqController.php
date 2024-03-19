<?php

namespace App\Http\Controllers;

use App\Http\Requests\FaqRequest;
use App\Models\Faq;
use App\Services\FaqService;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    public function __construct(
        protected FaqService $faqService
    ) {
    }

    public function allFaqs(): JsonResponse
    {
        $data = $this->faqService->allFaqs();

        return $this->success(data: $data['data'], meta: $data['metas']);
    }

    public function createFaq(FaqRequest $faqRequest): JsonResponse
    {
        $data = $this->faqService->createFaq($faqRequest);

        return $this->success(data: $data['data']);
    }

    public function singleFaq(Faq $faq): JsonResponse
    {
        $data = $this->faqService->singleFaq($faq);

        return $this->success(data: $data['data']);
    }

    public function editFaq(Faq $faq, FaqRequest $faqRequest): JsonResponse
    {
        $this->faqService->editFaq($faq, $faqRequest);

        return $this->success(message: 'Record updated');
    }

    public function deleteFaq(Faq $faq): JsonResponse
    {
        $this->faqService->deleteFaq($faq);

        return $this->success(message: 'Record deleted');
    }
}
