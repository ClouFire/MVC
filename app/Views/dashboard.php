<div class="container py-4">
    <h1 class="h3 mb-4"><?= h($title ?? 'Dashboard'); ?></h1>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <h2 class="h5">Test action log</h2>
            <?php if (empty($testActionLog)): ?>
                <p class="text-body-secondary mb-0">No test actions have been recorded yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Time</th>
                                <th scope="col">User</th>
                                <th scope="col">Action</th>
                                <th scope="col">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testActionLog as $entry): ?>
                                <tr>
                                    <td class="text-nowrap"><?= h($entry['created_at']); ?></td>
                                    <td><?= h($entry['user_name'] ?: 'Guest'); ?></td>
                                    <td><code><?= h($entry['action']); ?></code></td>
                                    <td><small><?= h($entry['details'] ?? ''); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
