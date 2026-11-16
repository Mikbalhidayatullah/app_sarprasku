<?php

// Only invoked by MySqlConcurrencyTest on the dedicated *_test database.
use App\Models\School;
use App\Models\Transfer;
use App\Models\User;
use App\Services\TransferService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->loadEnvironmentFrom('.env.testing');
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || ! str_ends_with(config('database.connections.mysql.database'), '_test')) {
    exit(90);
}
try {
    $service = app(TransferService::class);
    $actor = User::findOrFail($argv[3]);
    if ($argv[1] === 'approve') {
        $service->approve(Transfer::findOrFail($argv[2]), $actor);
    } else {
        $service->draft(['asset_id' => $argv[2], 'to_room_id' => (int) $argv[4], 'to_person_id' => (int) $argv[5], 'effective_on' => School::today(), 'reason' => 'Concurrent test request'], $actor);
    }
    echo 'accepted';
    exit(0);
} catch (ValidationException) {
    echo 'conflict';
    exit(2);
}
