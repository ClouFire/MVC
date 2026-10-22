<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= basePath('assets/bootstrap/css/bootstrap.min.css'); ?>">
    <title>PHPFramework :: <?= $title ?? '';?></title>
</head>
<body>
     <?php if($page = cache()->get('menu')) : ?> 
        <?= $page; ?>
    <?php else :?>
        <?= app()->get('menu'); ?>
    <?php endif ?>

    <?php get_alerts(); ?>
    <?=$this->content;?>    

<script src="<?= basePath('assets/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>