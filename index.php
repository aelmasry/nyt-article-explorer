<?php

require_once 'src/Services/NytApiService.php';

use App\Services\NytApiService;

$nytService = new NytApiService();
$result = $nytService->searchArticles('technology', 0);

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);