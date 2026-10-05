<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ImageSearchService
{
    private const MAX_SEARCH_TERMS = 6;

    private const IGNORED_WORDS = [
        'dos', 'das', 'uma', 'uns', 'por', 'para', 'com', 'que', 'nos', 'nas',
        'the', 'and', 'for', 'from', 'with', 'that', 'this', 'about', 'are', 'how',
        'was', 'what', 'when', 'where', 'who', 'will', 'und', 'www',
    ];

    /**
     * Apply search filters to an image query.
     *
     * @param  bool  $sortable  When false, skips ordering entirely (default random order is
     *                          meaningless for callers like the map, and breaks `chunkById`
     *                          / EXISTS subqueries). Grid/list callers keep the default true.
     */
    public function apply(Builder $query, array $filters, bool $sortable = true): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn (Builder $q, string $search) => $this->filterByFullText($q, $search))
            ->when($filters['title'] ?? null, fn (Builder $q, string $title) => $this->filterByTitle($q, $title))
            ->when($filters['contributor'] ?? null, fn (Builder $q, string $contributor) => $this->filterByContributor($q, $contributor))
            ->when($filters['location'] ?? null, fn (Builder $q, string $location) => $this->filterByLocation($q, $location))
            ->when($filters['subject'] ?? null, fn (Builder $q, array $ids) => $this->filterBySubjectIds($q, $ids))
            ->when($filters['subject_term'] ?? null, fn (Builder $q, array $terms) => $this->filterBySubjectTerm($q, $terms))
            ->when($filters['work'] ?? null, fn (Builder $q, array $ids) => $this->filterByWorkIds($q, $ids))
            ->when($filters['technique'] ?? null, fn (Builder $q, array $ids) => $this->filterByTechniqueIds($q, $ids))
            ->when($filters['work_type'] ?? null, fn (Builder $q, array $ids) => $this->filterByWorkTypeIds($q, $ids))
            ->when($filters['material'] ?? null, fn (Builder $q, array $ids) => $this->filterByMaterialIds($q, $ids))
            ->when($filters['style_period'] ?? null, fn (Builder $q, array $ids) => $this->filterByStylePeriodIds($q, $ids))
            ->when($filters['cultural_context'] ?? null, fn (Builder $q, array $ids) => $this->filterByCulturalContextIds($q, $ids))
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $from) => $this->filterByDateFrom($q, $from))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $to) => $this->filterByDateTo($q, $to))
            ->when($filters['work_date_from'] ?? null, fn (Builder $q, string $from) => $this->filterByWorkDateFrom($q, $from))
            ->when($filters['work_date_to'] ?? null, fn (Builder $q, string $to) => $this->filterByWorkDateTo($q, $to))
            ->when($filters['binomial'] ?? null, fn (Builder $q, array $binomials) => $this->filterByBinomials($q, $binomials))
            ->when($filters['license'] ?? null, fn (Builder $q, array $licenses) => $this->filterByLicense($q, $licenses))
            ->when($filters['user_id'] ?? null, fn (Builder $q, string $userId) => $q->where('user_id', $userId))
            ->when(array_key_exists('collective_id', $filters), function (Builder $q) use ($filters) {
                $collectiveId = $filters['collective_id'];

                return $collectiveId === null
                    ? $q->whereNull('collective_id')
                    : $q->where('collective_id', $collectiveId);
            })
            ->when(
                $sortable,
                fn (Builder $q) => $q->when(
                    isset($filters['sort_by']),
                    fn (Builder $q2) => $this->applySorting($q2, $filters),
                    fn (Builder $q2) => $this->applyDefaultOrder($q2, $filters),
                ),
            );
    }

    protected function filterByTitle(Builder $query, string $title): Builder
    {
        return $query->whereHas('titles', function (Builder $q) use ($title) {
            $q->where('label', 'LIKE', '%'.$title.'%');
        });
    }

    protected function filterByContributor(Builder $query, string $contributor): Builder
    {
        return $query->whereHas('agents', function (Builder $q) use ($contributor) {
            $q->whereHas('contributorName', function (Builder $q2) use ($contributor) {
                $q2->where('name', 'LIKE', '%'.$contributor.'%');
            });
        });
    }

    protected function filterByLocation(Builder $query, string $location): Builder
    {
        return $query->whereHas('locations', function (Builder $q) use ($location) {
            $q->where('label', 'LIKE', '%'.$location.'%');
        });
    }

    /**
     * Filters with several values refine the result: the image must match every value
     * (AND). `license` is the exception, since an image has a single license.
     */
    protected function filterBySubjectIds(Builder $query, array $ids): Builder
    {
        foreach ($ids as $id) {
            $query->whereHas('subjects', function (Builder $q) use ($id) {
                $q->where('vrac_subjects.id', $id);
            });
        }

        return $query;
    }

    protected function filterBySubjectTerm(Builder $query, array $terms): Builder
    {
        foreach ($terms as $term) {
            $query->whereHas('subjects', function (Builder $q) use ($term) {
                $q->where('term', 'LIKE', '%'.$term.'%');
            });
        }

        return $query;
    }

    protected function filterByWorkIds(Builder $query, array $ids): Builder
    {
        foreach ($ids as $id) {
            $query->whereHas('works', function (Builder $q) use ($id) {
                $q->where('vrac_works.id', $id);
            });
        }

        return $query;
    }

    protected function filterByTechniqueIds(Builder $query, array $ids): Builder
    {
        foreach ($ids as $id) {
            $query->whereHas('techniques', function (Builder $q) use ($id) {
                $q->where('vrac_techniques.id', $id);
            });
        }

        return $query;
    }

    protected function filterByWorkTypeIds(Builder $query, array $ids): Builder
    {
        return $this->filterByVocabularyIds($query, 'workTypes', 'vrac_work_types', $ids);
    }

    protected function filterByMaterialIds(Builder $query, array $ids): Builder
    {
        return $this->filterByVocabularyIds($query, 'materials', 'vrac_materials', $ids);
    }

    protected function filterByStylePeriodIds(Builder $query, array $ids): Builder
    {
        return $this->filterByVocabularyIds($query, 'stylePeriods', 'vrac_style_periods', $ids);
    }

    protected function filterByCulturalContextIds(Builder $query, array $ids): Builder
    {
        return $this->filterByVocabularyIds($query, 'culturalContexts', 'vrac_cultural_contexts', $ids);
    }

    /**
     * Legacy images carry their VCAA vocabulary only as subjects (tags), never in the
     * `image_*` pivots, so for each selected term an image matches when it is linked to
     * the term directly or has a subject with the same text. Every selected term must
     * match (AND).
     */
    protected function filterByVocabularyIds(Builder $query, string $relation, string $table, array $ids): Builder
    {
        foreach ($ids as $id) {
            $subjectIds = $this->subjectIdsForVocabulary($table, [$id]);

            $query->where(function (Builder $q) use ($relation, $table, $id, $subjectIds) {
                $q->whereHas($relation, function (Builder $r) use ($table, $id) {
                    $r->where("{$table}.id", $id);
                });

                if ($subjectIds !== []) {
                    $q->orWhereHas('subjects', function (Builder $s) use ($subjectIds) {
                        $s->whereIn('vrac_subjects.id', $subjectIds);
                    });
                }
            });
        }

        return $query;
    }

    protected function subjectIdsForVocabulary(string $table, array $ids): array
    {
        $labels = DB::table($table)
            ->whereIn('id', $ids)
            ->whereNotNull('label')
            ->pluck('label')
            ->map(fn (string $label) => mb_strtolower($label))
            ->unique()
            ->values()
            ->all();

        if ($labels === []) {
            return [];
        }

        return DB::table('vrac_subjects')
            ->whereIn(DB::raw('LOWER(term)'), $labels)
            ->pluck('id')
            ->all();
    }

    /**
     * Most used terms of a vocabulary table, ranked by the images that carry a subject
     * with the same text. The vocabulary can repeat a label (a VCAA row and a manual
     * one), so each label is returned once, preferring the VCAA row, and its id is one
     * the vocabulary filters accept.
     */
    public function topVocabularyByTag(string $table, int $limit = 10): Collection
    {
        $terms = DB::table($table)
            ->selectRaw("LOWER(label) as k, MIN(label) as term, COALESCE(MIN(CASE WHEN vocab = 'VCAA' THEN id END), MIN(id)) as id")
            ->whereNotNull('label')
            ->groupByRaw('LOWER(label)');

        $matches = DB::table('vrac_subjects')
            ->joinSub($terms, 't', function ($join) {
                $join->on(DB::raw('LOWER(vrac_subjects.term)'), '=', 't.k');
            })
            ->select('vrac_subjects.id as subject_id', 't.id', 't.term');

        return DB::table('image_subject')
            ->joinSub($matches, 'm', 'm.subject_id', '=', 'image_subject.subject_id')
            ->join('vrac_images', 'vrac_images.id', '=', 'image_subject.image_id')
            ->whereNull('vrac_images.deleted_at')
            ->select('m.id', 'm.term', DB::raw('COUNT(DISTINCT image_subject.image_id) as total'))
            ->groupBy('m.id', 'm.term')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    protected function filterByLicense(Builder $query, array $licenses): Builder
    {
        return $query->whereHas('rights', function (Builder $q) use ($licenses) {
            $q->where(function (Builder $q2) use ($licenses) {
                foreach ($licenses as $license) {
                    $q2->orWhere('href', 'LIKE', '%'.$this->licenseToHrefSegment($license).'%');
                }
            });
        });
    }

    protected function licenseToHrefSegment(string $license): string
    {
        return match (strtoupper($license)) {
            'CC0' => '/publicdomain/zero/',
            default => '/licenses/'.strtolower($license).'/',
        };
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

    protected function filterByBinomials(Builder $query, array $binomials): Builder
    {
        foreach ($binomials as $binomialId => $side) {
            $operator = $side === 'left' ? '<' : '>=';
            $subquery = DB::table('binomial_evaluations')
                ->select('image_id')
                ->where('binomial_id', $binomialId)
                ->groupBy('image_id')
                ->havingRaw("AVG(value) {$operator} 50");

            $query->whereIn('vrac_images.id', $subquery);
        }

        return $query;
    }

    protected function filterByWorkDateFrom(Builder $query, string $from): Builder
    {
        return $query->whereHas('works.dates', function (Builder $q) use ($from) {
            $q->where('earliest_date', '>=', $from);
        });
    }

    protected function filterByWorkDateTo(Builder $query, string $to): Builder
    {
        return $query->whereHas('works.dates', function (Builder $q) use ($to) {
            $q->where('latest_date', '<=', $to);
        });
    }

    /**
     * Every word of the search must match, each one in any of the searched fields.
     * Words are matched as prefixes ("mosaico" finds "mosaicos").
     */
    protected function filterByFullText(Builder $query, string $search): Builder
    {
        $terms = $this->searchTerms($search);

        if ($terms === []) {
            // Every word was too short or ignored: fall back to the whole phrase in the title.
            return $this->filterByTitle($query, trim($search));
        }

        foreach ($terms as $term) {
            $query->whereIn('vrac_images.id', $this->imageIdsMatchingTerm($term));
        }

        return $query;
    }

    /**
     * Splits the search into words, dropping operators and punctuation, words under three
     * letters and common connectors. InnoDB does not index those, so requiring one of them
     * would make the whole search return nothing.
     *
     * @return array<int, string>
     */
    protected function searchTerms(string $search): array
    {
        $text = mb_strtolower((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $search));

        $terms = array_filter(
            preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY),
            fn (string $term) => mb_strlen($term) >= 3 && ! in_array($term, self::IGNORED_WORDS, true),
        );

        return array_slice(array_values(array_unique($terms)), 0, self::MAX_SEARCH_TERMS);
    }

    protected function booleanQuery(array $terms): string
    {
        return implode(' ', array_map(fn (string $term) => '+'.$term.'*', $terms));
    }

    protected function imageIdsMatchingTerm(string $term): \Illuminate\Database\Query\Builder
    {
        $match = $this->booleanQuery([$term]);

        $titleIds = DB::table('image_title')
            ->select('image_title.image_id')
            ->join('vrac_titles', 'image_title.title_id', '=', 'vrac_titles.id')
            ->whereRaw('MATCH(vrac_titles.label) AGAINST(? IN BOOLEAN MODE)', [$match]);

        $subjectIds = DB::table('image_subject')
            ->select('image_subject.image_id')
            ->join('vrac_subjects', 'image_subject.subject_id', '=', 'vrac_subjects.id')
            ->whereRaw('MATCH(vrac_subjects.term) AGAINST(? IN BOOLEAN MODE)', [$match]);

        $descriptionIds = DB::table('description_image')
            ->select('description_image.image_id')
            ->join('vrac_descriptions', 'description_image.description_id', '=', 'vrac_descriptions.id')
            ->whereRaw('MATCH(vrac_descriptions.text) AGAINST(? IN BOOLEAN MODE)', [$match]);

        $contributorIds = DB::table('agent_image')
            ->select('agent_image.image_id')
            ->join('vrac_agents', 'agent_image.agent_id', '=', 'vrac_agents.id')
            ->join('vrac_contributor_names', 'vrac_agents.contributor_name_id', '=', 'vrac_contributor_names.id')
            ->whereRaw('MATCH(vrac_contributor_names.name) AGAINST(? IN BOOLEAN MODE)', [$match]);

        $workTitleIds = DB::table('image_work')
            ->select('image_work.image_id')
            ->join('work_title', 'image_work.work_id', '=', 'work_title.work_id')
            ->join('vrac_titles', 'work_title.title_id', '=', 'vrac_titles.id')
            ->whereRaw('MATCH(vrac_titles.label) AGAINST(? IN BOOLEAN MODE)', [$match]);

        $locationIds = DB::table('image_location')
            ->select('image_location.image_id')
            ->join('locations', 'image_location.location_id', '=', 'locations.id')
            ->where('locations.label', 'LIKE', '%'.$term.'%');

        $matching = $titleIds
            ->union($subjectIds)
            ->union($descriptionIds)
            ->union($contributorIds)
            ->union($workTitleIds)
            ->union($locationIds);

        return DB::query()->fromSub($matching, 'matching')->select('matching.image_id');
    }

    /**
     * With a text search and no explicit sort, images whose title contains every word come
     * first, then the rest, newest first. Without text the order stays random.
     */
    protected function applyDefaultOrder(Builder $query, array $filters): Builder
    {
        $terms = $this->searchTerms((string) ($filters['q'] ?? ''));

        if ($terms === []) {
            return $query->inRandomOrder();
        }

        $titleMatches = DB::table('image_title')
            ->select('image_title.image_id')
            ->join('vrac_titles', 'image_title.title_id', '=', 'vrac_titles.id')
            ->whereRaw('MATCH(vrac_titles.label) AGAINST(? IN BOOLEAN MODE)', [$this->booleanQuery($terms)]);

        return $query
            ->orderByRaw('(vrac_images.id IN ('.$titleMatches->toSql().')) DESC', $titleMatches->getBindings())
            ->orderByDesc('vrac_images.id');
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
