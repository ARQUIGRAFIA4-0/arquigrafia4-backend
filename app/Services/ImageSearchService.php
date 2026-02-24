<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ImageSearchService
{
    public function apply(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn (Builder $q, string $search) => $this->filterByFullText($q, $search))
            ->when($filters['title'] ?? null, fn (Builder $q, string $title) => $this->filterByTitle($q, $title))
            ->when($filters['contributor'] ?? null, fn (Builder $q, string $contributor) => $this->filterByContributor($q, $contributor))
            ->when($filters['subject'] ?? null, fn (Builder $q, array $ids) => $this->filterBySubjectIds($q, $ids))
            ->when($filters['subject_term'] ?? null, fn (Builder $q, array $terms) => $this->filterBySubjectTerm($q, $terms))
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $from) => $this->filterByDateFrom($q, $from))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $to) => $this->filterByDateTo($q, $to))
            ->when($filters['user_id'] ?? null, fn (Builder $q, string $userId) => $q->where('user_id', $userId))
            ->when(
                isset($filters['sort_by']),
                fn (Builder $q) => $this->applySorting($q, $filters),
                fn (Builder $q) => $q->inRandomOrder(),
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

    protected function filterBySubjectTerm(Builder $query, array $terms): Builder
    {
        return $query->whereHas('subjects', function (Builder $q) use ($terms) {
            $q->where(function (Builder $q2) use ($terms) {
                foreach ($terms as $term) {
                    $q2->orWhere('term', 'LIKE', '%' . $term . '%');
                }
            });
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

    protected function filterByFullText(Builder $query, string $search): Builder
    {
        $titleIds = DB::table('image_title')
            ->select('image_title.image_id')
            ->join('vrac_titles', 'image_title.title_id', '=', 'vrac_titles.id')
            ->whereRaw('MATCH(vrac_titles.label) AGAINST(? IN BOOLEAN MODE)', [$search]);

        $subjectIds = DB::table('image_subject')
            ->select('image_subject.image_id')
            ->join('vrac_subjects', 'image_subject.subject_id', '=', 'vrac_subjects.id')
            ->whereRaw('MATCH(vrac_subjects.term) AGAINST(? IN BOOLEAN MODE)', [$search]);

        $descriptionIds = DB::table('description_image')
            ->select('description_image.image_id')
            ->join('vrac_descriptions', 'description_image.description_id', '=', 'vrac_descriptions.id')
            ->whereRaw('MATCH(vrac_descriptions.text) AGAINST(? IN BOOLEAN MODE)', [$search]);

        $contributorIds = DB::table('agent_image')
            ->select('agent_image.image_id')
            ->join('vrac_agents', 'agent_image.agent_id', '=', 'vrac_agents.id')
            ->join('vrac_contributor_names', 'vrac_agents.contributor_name_id', '=', 'vrac_contributor_names.id')
            ->whereRaw('MATCH(vrac_contributor_names.name) AGAINST(? IN BOOLEAN MODE)', [$search]);

        $matchingIds = $titleIds
            ->union($subjectIds)
            ->union($descriptionIds)
            ->union($contributorIds)
            ->pluck('image_id');

        return $query->whereIn('vrac_images.id', $matchingIds);
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
