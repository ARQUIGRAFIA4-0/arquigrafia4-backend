<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Models\VRACore\VRACImage;

class IIIFManifestController extends Controller
{
    public function getManifest($id)
    {
        $image = $this->getImageWithRelations($id);

        $metadata = $this->getMetadata($image);
        $title = $this->getTitle($image);
        $description = $this->getDescription($image);

        $manifest = $this->createManifest($id, $title, $description, $metadata);

        return Response::json($manifest, 200, ['Content-Type' => 'application/ld+json']);
    }

    private function getImageWithRelations($id)
    {
        return VRACImage::with(
            [
                'agents',
                'culturalContexts',
                'dates',
                'descriptions',
                'title',
                'techniques',
                'workTypes',
                'materials',
                'stylePeriods',
                'measurements',
                'stateEditions',
                'sources',
                'rights',
                'inscriptions',
                'subjects',
                'locations'
            ]
        )->findOrFail($id);
    }

    private function getMetadata($image)
    {
        $agents = $image->agents;
        $agentString = $agents->isNotEmpty() ? $agents->map(fn($agent) => "{$agent->contributorName->name} ({$agent->role->label})")->implode(', ') : null;

        $dates = $image->dates;
        $dateString = $dates->isNotEmpty() ? $dates->map(fn($date) => $date->formattedDateRange())->implode(', ') : null;

        $subjects = $image->subjects;
        $subjectString = $subjects->isNotEmpty() ? $subjects->map(fn($subject) => $subject->term)->implode(', ') : null;

        $metadata = [
            'Agentes' => $agentString,
            'Data' => $dateString,
            'Assuntos' => $subjectString,
        ];

        $filteredMetadata = array_filter($metadata, fn($value) => !is_null($value) && $value !== '');

        return array_map(fn($label, $value) => [
            'label' => ['none' => [$label]],
            'value' => ['none' => [$value]]
        ], array_keys($filteredMetadata), $filteredMetadata);
    }

    private function getTitle($image)
    {
        return $image->title->isNotEmpty() ? $image->title[0]->label : 'Sem título';
    }

    private function getDescription($image)
    {
        return $image->descriptions->isNotEmpty() ? $image->descriptions[0]->text : null;
    }

    private function createManifest($id, $title, $description, $metadata)
    {
        $manifest = [
            '@context' => 'http://iiif.io/api/presentation/3/context.json',
            'id' => route('iiif.manifest', ['id' => $id]),
            'type' => 'Manifest',
            'label' => ['none' => [$title]],
            'description' => $description ? ['none' => [$description]] : null,
            'metadata' => $metadata
        ];

        return array_filter($manifest, fn($value) => !is_null($value) && $value !== '');
    }
}
