<div class="container">

    <?php foreach($users as $user): ?>
        <?= h($user['name']); ?><br>
    <?php endforeach; ?>

    <?= $pagination; ?>
</div>
