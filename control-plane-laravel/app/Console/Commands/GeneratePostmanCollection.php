<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class GeneratePostmanCollection extends Command
{
    protected $signature = 'generate:postman';
    protected $description = 'Generate Postman Collection from API Routes';

    public function handle()
    {
        $routes = Route::getRoutes();
        $collection = [
            'info' => [
                'name' => 'RBDB Control Plane API',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
            ],
            'item' => []
        ];

        foreach ($routes as $route) {
            if (!Str::startsWith($route->uri(), 'api/v1') && !Str::startsWith($route->uri(), 'health')) {
                continue;
            }
            if (Str::contains($route->uri(), 'l5-swagger') || Str::contains($route->uri(), 'sanctum')) {
                continue;
            }

            $method = $route->methods()[0];
            $uri = $route->uri();
            $name = $route->getName() ?? $uri;
            
            // Group by resource
            $parts = explode('/', str_replace('api/v1/', '', $uri));
            $resourceName = ucfirst($parts[0] ?? 'General');

            // Find or create folder
            $folderIndex = -1;
            foreach ($collection['item'] as $index => $item) {
                if (($item['name'] ?? '') === $resourceName) {
                    $folderIndex = $index;
                    break;
                }
            }
            if ($folderIndex === -1) {
                $collection['item'][] = [
                    'name' => $resourceName,
                    'item' => []
                ];
                $folderIndex = count($collection['item']) - 1;
            }

            $requestItem = [
                'name' => $name,
                'request' => [
                    'method' => $method,
                    'header' => [
                        [
                            'key' => 'Accept',
                            'value' => 'application/json',
                            'type' => 'text'
                        ],
                        [
                            'key' => 'Authorization',
                            'value' => 'Bearer {{token}}', // variable
                            'type' => 'text'
                        ]
                    ],
                    'url' => [
                        'raw' => '{{base_url}}/' . $uri,
                        'host' => ['{{base_url}}'],
                        'path' => explode('/', $uri)
                    ]
                ]
            ];

            // Add simple body for POST/PUT
            if (in_array($method, ['POST', 'PUT'])) {
                $requestItem['request']['body'] = [
                    'mode' => 'raw',
                    'raw' => "{\n    \n}",
                    'options' => [
                        'raw' => [
                            'language' => 'json'
                        ]
                    ]
                ];
            }

            $collection['item'][$folderIndex]['item'][] = $requestItem;
        }

        $outputDir = base_path('postman');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        $outputPath = $outputDir . '/RBDB_Control_Plane_Collection.json';
        file_put_contents($outputPath, json_encode($collection, JSON_PRETTY_PRINT));
        $this->info("Postman collection generated at: $outputPath");
    }
}
