<?php
$currentPath = request()->getPath();
$activeClass = static fn(string $path): string => $currentPath === trim($path, '/') ? 'active' : '';
?>

<nav class="navbar navbar-expand-lg bg-dark mb-3" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= basePath(''); ?>">Home</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $activeClass(''); ?>" <?= $currentPath === '' ? 'aria-current="page"' : ''; ?> href="<?= basePath(''); ?>">Home</a>
                </li>
                <?php if (isAuth()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeClass('dashboard'); ?>" <?= $currentPath === 'dashboard' ? 'aria-current="page"' : ''; ?> href="<?= basePath('dashboard'); ?>">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeClass('users'); ?>" <?= $currentPath === 'users' ? 'aria-current="page"' : ''; ?> href="<?= basePath('users'); ?>">Users list</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeClass('register'); ?>" <?= $currentPath === 'register' ? 'aria-current="page"' : ''; ?> href="<?= basePath('register'); ?>">Register</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeClass('login'); ?>" <?= $currentPath === 'login' ? 'aria-current="page"' : ''; ?> href="<?= basePath('login'); ?>">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
            <?php if (isAuth()): ?>
                <ul class="navbar-nav ms-lg-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <form action="<?= basePath('logout'); ?>" method="post" class="d-flex">
                            <?= getCsrfField(); ?>
                            <button type="submit" class="nav-link border-0 bg-transparent">Logout</button>
                        </form>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
