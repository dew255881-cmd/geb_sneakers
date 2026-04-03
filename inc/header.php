<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="GEB SNEAKERS - ร้านรองเท้าผ้าใบแบรนด์เนมมือ 1 คุณภาพ Premium">
    <title>GEB SNEAKERS | Premium Sneaker Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/main.css?v=<?php echo time(); ?>">
    <?php
    if (isset($page)) {
        $page_css = "assets/css/pages/" . basename($page) . ".css";
        if (file_exists(__DIR__ . "/../" . $page_css)) {
            echo '<link rel="stylesheet" href="' . BASE_URL . $page_css . '?v=' . time() . '">';
        }
    }
    ?>
</head>
<body>
