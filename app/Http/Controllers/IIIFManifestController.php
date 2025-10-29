<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\VRACore\VRACImage;

class IIIFManifestController extends Controller
{
    public function getManifest($id)
    {
        $image = $this->getImageWithRelations($id);

        $metadata = $this->getMetadata($image);
        $title = $this->getTitle($image);
        $description = $this->getDescription($image);
        $canvas = $this->getCanvas($image);
        $thumbnail = $this->getThumbnail($image);

    $manifest = $this->createManifest($image, $title, $description, $metadata, $canvas, $thumbnail);

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
                'titles',
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
        return $image->titles->isNotEmpty() ? $image->titles[0]->label : 'Sem título';
    }

    private function getDescription($image)
    {
        return $image->descriptions->isNotEmpty() ? $image->descriptions[0]->text : null;
    }

    private function getCanvas($image)
    {
        $info = Storage::disk('public')->json($image->path('info')); // basePath() . '/info.json'
        $width = $info['width'] ?? 0;
        $height = $info['height'] ?? 0;

        $manifestBase = rtrim(route('iiif.manifest', ['id' => $image->id]), '/manifest');
        $canvasId = $manifestBase . '/canvas/1';
        $annotationPageId = $manifestBase . '/page/1';
        $annotationId = $manifestBase . '/annotation/1';

        $canvas = [
            'id' => $canvasId,
            'type' => 'Canvas',
            'label' => ['none' => ['1']],
            'width'=> (int) $width,
            'height'=> (int) $height,
            'items' => [
                [
                    'id' => $annotationPageId,
                    'type' => 'AnnotationPage',
                    'items' => [
                        [
                            'id' => $annotationId,
                            'type' => 'Annotation',
                            'motivation' => 'painting',
                            'body' => [
                                'id' => $image->path('original', 'url'), // originalURL(),  
                                'type' => 'Image',
                                'format' => 'image/jpeg',
                                'width'=> (int) $width,
                                'height'=> (int) $height,
                                'service' => [
                                    [
                                        'id' => $image->path('base', 'url'), // asset('iiif/'. $image->id),
                                        'type' => 'ImageService3',
                                        'profile' => 'level0'
                                    ]
                                ]
                            ],
                            'target' => $canvasId,
                        ]
                    ]
                ]
            ]
        ];
        return $canvas;
    }

    private function getThumbnail($image) {
        $thumbnail = [
            'id' => $image->thumb_path,
            'type' => 'Image',
            'format' => 'image/jpeg'
        ];
        return $thumbnail;
    }

    private function createManifest($id, $title, $description, $metadata, $canvas, $thumbnail)
    {
        $manifest = [
            '@context' => 'http://iiif.io/api/presentation/3/context.json',
            'id' => route('iiif.manifest', ['id' => $id]),
            'type' => 'Manifest',
            'label' => ['none' => [$title]],
            'description' => $description ? ['none' => [$description]] : null,
            'metadata' => $metadata,
            'items' => [$canvas],
            'thumbnail' => [$thumbnail]
        ];

        return array_filter($manifest, fn($value) => !is_null($value) && $value !== '');
    }
}
