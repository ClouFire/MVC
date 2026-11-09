<nav aria-label="Page navigation example">
    <ul class="pagination">
        <?php if (!empty($startPage)): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $startPage; ?>" aria-label="Start page">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (!empty($back)): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $back; ?>" aria-label="Previous page">
                    <span aria-hidden="true">&lt;</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (!empty($pagesLeft)): ?>
            <?php foreach ($pagesLeft as $page): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $page['link']; ?>">
                        <?= $page['number']; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <li class="page-item active">
            <a class="page-link"><?= $currentPage; ?></a>
        </li>

        <?php if (!empty($pagesRight)): ?>
            <?php foreach ($pagesRight as $page): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $page['link']; ?>">
                        <?= $page['number']; ?>
                    </a>
                </li>
            <?php endforeach; ?>

            <?php if (!empty($forward)): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $forward; ?>" aria-label="Next page">
                        <span aria-hidden="true">&gt;</span>
                    </a>
                </li>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($lastPage)): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $lastPage; ?>" aria-label="Last page">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
