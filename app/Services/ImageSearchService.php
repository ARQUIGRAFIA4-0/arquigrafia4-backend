<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class ImageSearchService
{
    public function apply(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['title'] ?? null, fn (Builder $q, string $title) => $this->filterByTitle($q, $title))
            ->when($filters['contributor'] ?? null, fn (Builder $q, string $contributor) => $this->filterByContributor($q, $contributor))
            ->when($filters['subject'] ?? null, fn (Builder $q, array $ids) => $this->filterBySubjectIds($q, $ids))
            ->when($filters['subject_term'] ?? null, fn (Builder $q, string $term) => $this->filterBySubjectTerm($q, $term))
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $from) => $this->filterByDateFrom($q, $from))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $to) => $this->filterByDateTo($q, $to))
            ->when($filters['user_id'] ?? null, fn (Builder $q, string $userId) => $q->where('user_id', $userId))
            ->when(
                $filters['random'] ?? false,
                fn (Builder $q) => $q->inRandomOrder(),
                fn (Builder $q) => $this->applySorting($q, $filters),
            );
    }

    protected function filterByTitle(Builder $query, string $title): Builder
    {
        return $query->whereHas('titles', function (Builder $q) use ($title) {
            $q->where('label', 'LIKE', '%' . $title . '%');
        });
    }

    protected function filterByContributor(Builder $query, string $contributor): Builder
    {
        return $query->whereHas('agents', function (Builder $q) use ($contributor) {
            $q->whereHas('contributorName', function (Builder $q2) use ($contributor) {
                $q2->where('name', 'LIKE', '%' . $contributor . '%');
            });
        });
    }

    protected function filterBySubjectIds(Builder $query, array $ids): Builder
    {
        return $query->whereHas('subjects', function (Builder $q) use ($ids) {
            $q->whereIn('vrac_subjects.id', $ids);
        });
    }

    protected function filterBySubjectTerm(Builder $query, string $term): Builder
    {
        return $query->whereHas('subjects', function (Builder $q) use ($term) {
            $q->where('term', 'LIKE', '%' . $term . '%');
        });
    }

    protected function filterByDateFrom(Builder $query, string $from): Builder
    {
        return $query->whereHas('dates', function (Builder $q) use ($from) {
            $q->where('earliest_date', '>=', $from);
        });
    }

    protected function filterByDateTo(Builder $query, string $to): Builder
    {
        return $query->whereHas('dates', function (Builder $q) use ($to) {
            $q->where('latest_date', '<=', $to);
        });
    }

    protected function applySorting(Builder $query, array $filters): Builder
    {
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return match ($sortBy) {
            'title' => $query->orderBy(
                \App\Models\VRACore\VRACTitle::select('label')
                    ->join('image_title', 'vrac_titles.id', '=', 'image_title.title_id')
                    ->whereColumn('image_title.image_id', 'vrac_images.id')
                    ->orderBy('label')
                    ->limit(1),
                $sortOrder,
            ),
            'date' => $query->orderBy(
                \App\Models\VRACore\VRACDate::select('earliest_date')
                    ->join('date_image', 'vrac_dates.id', '=', 'date_image.date_id')
                    ->whereColumn('date_image.image_id', 'vrac_images.id')
                    ->orderBy('earliest_date')
                    ->limit(1),
                $sortOrder,
            ),
            default => $query->orderBy('created_at', $sortOrder),
        };
    }
}
