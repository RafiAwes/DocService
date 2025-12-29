<?php
require 'bootstrap/app.php';
$app = app();

$cart = \App\Models\Cart::with([
    'items.service.questionaries',
    'items.service.requiredDocuments'
])->first();

if (!$cart || $cart->items->isEmpty()) {
    echo "No cart or empty cart\n";
    exit;
}

$item = $cart->items->first();
echo "Service: " . $item->service->title . "\n";
echo "Questionaries: " . $item->service->questionaries->count() . "\n";
echo "Required Documents: " . $item->service->requiredDocuments->count() . "\n";
echo "\n";
echo json_encode([
    'questions' => $item->service->questionaries->pluck('name')->toArray(),
    'documents' => $item->service->requiredDocuments->pluck('title')->toArray(),
], JSON_PRETTY_PRINT);
