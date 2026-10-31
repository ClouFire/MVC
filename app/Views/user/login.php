<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-7 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 mb-4 text-center"><?= h($title ?? 'Login'); ?></h1>

                    <form action="<?= basePath('login'); ?>" method="post" novalidate>
                        <?= getCsrfField(); ?>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input name="email" type="email" class="form-control <?= getValidationClass('email'); ?>" id="email" value="<?= old('email'); ?>" autocomplete="email" required autofocus>
                            <?= get_errors('email'); ?>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input name="password" type="password" class="form-control <?= getValidationClass('password'); ?>" id="password" autocomplete="current-password" required>
                            <?= get_errors('password'); ?>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Log in</button>
                    </form>

                    <p class="text-center text-body-secondary mt-4 mb-0">
                        Don't have an account?
                        <a href="<?= basePath('register'); ?>">Create one</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
session()->remove('form_errors');
session()->remove('form_data');
?>
