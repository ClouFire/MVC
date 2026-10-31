<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
    <h1 class="h3 mb-4 text-center"><?= h($title ?? 'Create account'); ?></h1>

    <div class="row">
        <div class="col-md-6 offset-md-3">
            <form action="<?= basePath('register'); ?>" method="post" novalidate>

                <?= getCsrfField(); ?>

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                        <input name="name" type="text" class="form-control <?= getValidationClass('name'); ?>" id="name" placeholder="Name" value="<?= old('name'); ?>" autocomplete="name" required>
                        <?= get_errors('name');?>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                        <input name="email" type="email" class="form-control <?= getValidationClass('email'); ?>" id="email" placeholder="name@example.com" value="<?= old('email'); ?>" autocomplete="email" required>
                        <?= get_errors('email');?>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                        <input name="password" type="password" class="form-control <?= getValidationClass('password'); ?>" id="password" placeholder="At least 6 characters" autocomplete="new-password" required>
                        <?= get_errors('password');?>
                </div>

                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Confirm password</label>
                        <input name="confirmPassword" type="password" class="form-control <?= getValidationClass('confirmPassword'); ?>" id="confirmPassword" placeholder="Confirm password" autocomplete="new-password" required>
                        <?= get_errors('confirmPassword');?>
                </div>

                <button type="submit" class="btn btn-primary">Register</button>

            </form>

            <p class="text-center text-body-secondary mt-4 mb-0">
                Already have an account?
                <a href="<?= basePath('login'); ?>">Log in</a>
            </p>

            <?php
            session()->remove('form_errors');
            session()->remove('form_data');
            ?>

                </div>
            </div>
        </div>
    </div>
</div>
