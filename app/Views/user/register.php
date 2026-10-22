<div class="container">
    <h1><?= $title ?? ''; ?></h1>

    <div class="row">
        <div class="col-md-6 offset-md-3">
            <form action="/register" method="post">

                <?= getCsrfField(); ?>

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                        <input name="name" type="text" class="form-control <?= getValidationClass('name'); ?>" id="name" placeholder="Name" value="<?= old('name'); ?>">
                        <?= get_errors('name');?>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                        <input name="email" type="email" class="form-control <?= getValidationClass('email'); ?>" id="email" placeholder="name@example.com" value="<?= old('email'); ?>">  
                        <?= get_errors('email');?>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                        <input name="password" type="password" class="form-control  <?= getValidationClass('password'); ?>" id="password" placeholder="*  *  *  *  *  *  *  *">
                        <?= get_errors('password');?>
                </div>

                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Confirm password</label>
                        <input name="confirmPassword" type="password" class="form-control <?= getValidationClass('confirmPassword'); ?>" id="confirmPassword" placeholder="Confirm password">
                        <?= get_errors('confirmPassword');?>
                </div>

                <button type="submit" class="btn btn-warning">Register</button>

            </form>

            <?php 
            session()->remove('form_errors');
            session()->remove('form_data');
            ?>

        </div>
    </div>
</div>