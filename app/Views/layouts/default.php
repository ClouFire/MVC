<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= basePath('assets/bootstrap/css/bootstrap.min.css'); ?>">
    <title>PHPFramework :: <?= $title ?? '';?></title>
</head>
<body>
<style>
    .app-notification {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 1080;
        min-width: min(20rem, calc(100vw - 2rem));
        max-width: calc(100vw - 2rem);
        margin: 0;
        opacity: .88;
        backdrop-filter: blur(8px);
        box-shadow: 0 .5rem 1.25rem rgba(0, 0, 0, .2);
    }
</style>

    <?= app()->get('menu'); ?>
    <?= view()->renderPartial('incs/test_lab'); ?>

    <?php get_alerts(); ?>
    <?=$this->content;?>

<script src="<?= basePath('assets/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-auto-dismiss]').forEach((notification) => {
            const timeout = Number(notification.dataset.autoDismiss) || 3000;
            const closeNotification = () => {
                notification.classList.remove('show');
                window.setTimeout(() => notification.remove(), 150);
            };

            notification.addEventListener('click', closeNotification);

            window.setTimeout(() => {
                closeNotification();
            }, timeout);
        });
    });
</script>
</body>
</html>
