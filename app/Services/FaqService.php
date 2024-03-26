<?php

namespace App\Services;

use App\Http\Requests\FaqCategoryRequest;
use App\Http\Requests\FaqRequest;
use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class FaqService extends BaseService
{
    public function __construct(
        protected Faq $faq,
        protected FaqCategory $faqCategory
    ) {
    }

    /**
     * @return \App\Models\FaqCategory
     */
    public function create(FaqCategoryRequest $request): Model
    {
        return $this->faqCategory->create($request->validated());
    }

    public function edit(FaqCategoryRequest $request, FaqCategory $faqCategory): void
    {
        $data = $request->validated();
        $faqCategory->update($data);
    }

    public function destroy(FaqCategory $faqCategory): void
    {
        $faqCategory->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): Collection|array
    {
        $data = $this->paginate($this->faqCategory);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function allFaqs(): array
    {
        $data = $this->paginate($this->faq->with('faqCategory'));

        return $data;
    }

    public function singleFaq(Faq $faq): mixed
    {
        $data = $this->faq->where('id', $faq->id)->with(['faqCategory'])->get();

        return $data;
    }

    public function editFaq(Faq $faq, FaqRequest $request): void
    {
        $data = $request->validated();
        $faq->update($data);
    }

    public function deleteFaq(Faq $faq): void
    {
        $faq->delete();
    }

    /**
     * @return \App\Models\Faq
     */
    public function createFaq(FaqRequest $request): Model
    {
        $data = $this->faq->create($request->validated());

        return $data;
    }
}
